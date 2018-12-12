<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services;


use Tygh\Shippings\IService;
use Tygh\Shippings\Services\Myparcel\Rate;
use Tygh\Shippings\Services\Myparcel\TariffZone;
use Tygh\Shippings\Services\Myparcel\Api;
use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use Tygh\Registry;

/**
 * Class Myparcel
 * Main class that interoperation with CS-Cart
 *
 * @package Tygh\Shippings\Services
 */
class Myparcel implements IService
{
    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = false;

    /**
     * Stack for errors occured during the preparing rates process
     *
     * @var array $_error_stack
     */
    private $_error_stack = array();

    /**
     * Configured rates for this shipping
     *
     * @var array
     */
    private $myparcel_rates_table = array();

    /**
     * Rates table object
     *
     * @var Rate
     */
    private $rate;

    /**
     * local copy of $_REQUEST
     *
     * @var Request
     */
    private $request;

    private $client;

    public function __construct()
    {
        $this->client = new MyParcelCollection();
        $this->rate = new Rate();
        $this->request = $_REQUEST;
    }

    /**
     * Sets data to internal class variable
     *
     * @param array $shipping_info
     * @return array|void
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

    /**
     * Gets shipping cost and information about possible errors
     *
     * @param  string $response Response from Shipping service server
     * @return array  Shipping cost and errors
     */
    public function processResponse($response = '')
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
        );

        $cost = $this->_getRates($response);
        if (empty($this->_error_stack) && !empty($cost)) {
            $return['cost'] = $cost;
        } else {
            $return['error'] = $this->processErrors($response);
        }

        return $return;
    }

    /**
     * Gets error message from shipping service server
     *
     * @param  string $response Response from Shipping service server
     * @return string Text of error or false if no errors
     */
    public function processErrors($response = '')
    {
        $error = '';
        if (!empty($this->_error_stack)) {
            foreach ($this->_error_stack as $_error) {
                $error .= '; ' . $_error;
            }
        }

        return $error;
    }

    /**
     * Checks if shipping service allows to use multithreading
     *
     * @return bool true if allow
     */
    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    /**
     * Prepare request information
     *
     * @return array Prepared data
     */
    public function getRequestData()
    {
        $weight_data = fn_expand_weight($this->_shipping_info['package_info']['W']);
        $packages = $this->_shipping_info['package_info']['packages'];
        $origination = $this->_shipping_info['package_info']['origination'];
        $location = $this->_shipping_info['package_info']['location'];
        $cost = $this->_shipping_info['package_info']['C'];
        $volume = $this->getPackageValues();
        $data = array(
            'weight' => $weight_data,
            'origination' => $origination,
            'location' => $location,
            'cost' => $cost,
            'packages' => $packages,
            'volume' => $volume,
        );
        $url = '';
        $request_data = array(
            'method' => 'get',
            'url' => $url,
            'data' => $data,
        );

        return $request_data;
    }

    /**
     * Process simple request to shipping service server
     *
     * @return string Server response
     */
    public function getSimpleRates()
    {
        $result = $this->rates_table = $this->_shipping_info['service_params']['myparcel_nl_rates'];

        return $result;
    }

    /**
     * Process simple calculate volume
     *
     * @return array
     */
    public function getPackageValues()
    {
        $packages = $this->_shipping_info['package_info']['packages'];
        $is_test_calculation = isset($this->request['shipping_data']) && isset($this->request['shipping_data']['test_weight']);
        foreach ($packages as $key => $pack) {
            if (!isset($pack['shipping_params']) && !$is_test_calculation) {
                unset($packages[$key]);
            }
        }
        $count = count($packages);
        $service_params = $this->_shipping_info['service_params'];
        $volume = 0;
        if ($count > 0 && !$is_test_calculation) {
            foreach ($packages as $key => $value) {
                $ship_params = $value['shipping_params'];
                $length = !empty($ship_params['box_length']) ? $ship_params['box_length'] : 0;
                $width = !empty($ship_params['box_width']) ? $ship_params['box_width'] : 0;
                $height = !empty($ship_params['box_height']) ? $ship_params['box_height'] : 0;
                $size_empty = $length === 0 || $width === 0 || $height === 0;
                if ($size_empty || ($length > $service_params['max_length'] || $width > $service_params['max_width'] || $height > $service_params['max_height'])) {
                    $this->_internalError(__('addons.sd_myparcel_nl.package_size_error'));
                    unset($packages[$key]);
                } else {
                    $volume += round($length * $width * $height, 2);
                }
            }

        } else {
            $volume = 1;
        }

        return $volume;
    }

    private function _getRates($response)
    {
        $result = 0;
        $delivery_zone = $this->getDeliveryZone();
        $zone_rates = $this->rates_table[$delivery_zone];
        $weight_ranges = $this->createRanges(array_keys($zone_rates));
        $weight = $this->getWeightInKg($this->_shipping_info['package_info']['W']);
        foreach ($weight_ranges as $range_index => $weight_range) {
            if ($this->checkIsWeightInRange($weight, $weight_range)) {
                $result = $this->getRateByIndex($zone_rates, $range_index);
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $rates
     * @param int $weight_index
     * @return int|float
     */
    private function getRateByIndex($rates, $weight_index)
    {
        $result = 0;
        foreach ($rates as $weight_range => $price) {
            if ($this->parseWeight($weight_range) == $weight_index) {
                $result = $price;
                break;
            }
        }

        return $result;
    }

    private function getDeliveryZone()
    {
        $result = TariffZone::World;
        $destinations = $this->rate->getDestinations();
        $current_destination = $this->_shipping_info['package_info']['location'];
        foreach ($destinations as $zone_name => $zone_params) {
            foreach ($zone_params as $destination_area => $destinations) {
                if (in_array($current_destination['country'], $destinations)) {
                    $result = $zone_name;
                    break 2;
                }
            }
        }

        return $result;
    }

    private function createRanges($weights)
    {
        $weights_cnt = count($weights);
        $result = array();
        for ($i = 0; $i < $weights_cnt; $i += 1) {
            $weights[$i] = $this->parseWeight($weights[$i]);
            $result[$weights[$i]] = array(
                'from' => $i === 0 ? 0 : floatval($weights[$i - 1]) + 1e-6,
                'to' => floatval($weights[$i]),
            );
        }

        return $result;
    }

    /**
     * @param string $string
     * @return int
     */
    private function parseWeight($string = '')
    {
        $result = 0;
        $pattern = "/^\d{1,2}\-(\d{1,2})kg$/";
        preg_match($pattern, $string, $matches);
        if (isset($matches[1])) {
            $result = $matches[1];
        }

        return $result;
    }

    private function checkIsWeightInRange($_weight, $range)
    {
        $weight = floatval($_weight);
        return ($range['from'] < $weight) && ($weight <= $range['to']);
    }

    /**
     * Returns shipping service information
     *
     * @todo We need calculate the D and P parameters (destination) dynamically
     * @see  https://www.postnl.nl/businessportal/en/Images/step-by-step-card-track-trace_tcm16-66060.pdf
     *
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_myparcel'),
            'tracking_url' => Api::POSTNL_TRACKING_URL . '?L='.strtoupper(DESCR_SL).'&B=%s&P=[POSTAL_CODE]&D=[DESTINATION_COUNTRY]&T=C'
        );
    }

    private function _internalError($error)
    {
        $this->_error_stack[] = $error;
    }

    /**
     * Converts weight to pounds or kilograms depending on the origination country.
     *
     * @param float  $weight Weight of the package in the primary weight unit
     *
     * @return float Weight in the selected unit
     */
    private function getWeightInKg($weight)
    {
        if (Registry::get('settings.General.weight_symbol') == 'lbs') {
            $weight = fn_expand_weight($weight);
            $kilograms_in_lbs = Registry::get('settings.General.weight_symbol_grams') / 1000;
            $weight = $weight['full_pounds'] * $kilograms_in_lbs;
        }

        return $weight;
    }
}
