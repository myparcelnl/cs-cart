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

namespace Tygh\Shippings\Services\Myparcel;


use MyParcelNL\Sdk\src\Model\Repository\MyParcelConsignmentRepository;
use Tygh\Registry;
use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;
use Tygh\Shippings\Services\Myparcel\Delivery\Delivery;

/**
 * Class Api
 * The class forms consignment for Myparcel service
 * @package Tygh\Shippings\Services\Myparcel
 */
class Api
{
    use ExceptionsTrait;

    const MYPARCEL_SERVICE_CODE = 'MYPARCEL';
    const POSTNL_TRACKING_URL = 'https://postnl.nl/tracktrace/';

    private $city,
        $consignment,
        $country,
        $full_street,
        $order_info = [],
        $postal_code,
        $shipment_data = [],
        $delivery_options = [],
        $group_key,
        $shipment_options;
    /**
     * Countries that can be processed by the Myparcel API
     * @var array
     */
    private static $servicedDestinationCountries = ['NL'];//TODO Add BE when there is integration with Belgium

    /**
     * Api constructor.
     * @param array $context
     * @throws \Exception
     */
    public function __construct(array $context = [])
    {
        if (!isset(
                $context['order_info'],
                $context['shipment_data'],
                $context['shipment_options'],
                $context['consignment']
            )
            || !is_a(
                $context['shipment_options'],
                '\Tygh\Shippings\Services\Myparcel\ShipmentOptions'
            )
        ) {
            $this->throwConstructorParamsException();
        }

        if (isset($context['order_info']['shipping'])) {
            foreach ($context['order_info']['shipping'] as $group_index => $shipping) {
                if (!fn_sd_myparcel_nl_is_myparcel_shipping($shipping) || empty($shipping['delivery_options'])) {
                    continue;
                }
                $delivery_options = $shipping['delivery_options'];
                if (!empty($delivery_options['cc'])) {
                    $this->country = $delivery_options['cc'];
                }
                if (!empty($delivery_options['city'])) {
                    $this->city = $delivery_options['city'];
                }
                if (!empty($delivery_options['street'])) {
                    $this->full_street = $delivery_options['street'];
                }
                if (!empty($delivery_options['number'])) {
                    $this->full_street .= ' ' . $delivery_options['number'];
                }
                if (!empty($delivery_options['postal_code'])) {
                    $this->postal_code .= ' ' . $delivery_options['postal_code'];
                }
                $this->delivery_options[$group_index] = $delivery_options;
            }
        }
        
        if (isset($context['group_key'])) {
            $this->group_key = $context['group_key'];
        }

        $this->order_info = $context['order_info'];
        $this->shipment_data = $context['shipment_data'];
        $this->shipment_options = $context['shipment_options'];
        $this->consignment = $context['consignment'];
    }

    /**
     * @param mixed $consignment
     * @return Api
     */
    public function setConsignment(MyParcelConsignmentRepository $consignment)
    {
        $this->consignment = $consignment;
        return $this;
    }

    /**
     * @param ShipmentOptions $shipment_options
     * @return Api
     */
    public function setShipmentOptions(ShipmentOptions $shipment_options)
    {
        $this->shipment_options = $shipment_options;
        return $this;
    }

    /**
     * @param array $shipment_data
     * @return Api
     */
    public function setShipmentData($shipment_data)
    {
        $this->shipment_data = $shipment_data;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderInfo()
    {
        return $this->order_info;
    }

    /**
     * @param array $order_info
     * @return Api
     */
    public function setOrderInfo($order_info)
    {
        $this->order_info = $order_info;
        return $this;
    }

    /**
     * @return array
     */
    public function getShipmentData()
    {
        return $this->shipment_data;
    }

    /**
     * @return ShipmentOptions
     */
    public function getShipmentOptions()
    {
        return $this->shipment_options;
    }

    /**
     * @return MyParcelConsignmentRepository
     */
    public function getConsignment()
    {
        if (!isset($this->order_info, $this->shipment_data)) {
            return false;
        }
        $consignmentRepo = $this->consignment;
        $insurance = $this->shipment_options->getInsurance();
        $country = (!empty($this->order_info['s_country']) ? $this->order_info['s_country'] : ($this->order_info['b_country']) ? $this->order_info['b_country'] : '');
        $api_key = Registry::get('addons.sd_myparcel_nl.api_key');
        $is_checkout_enable = fn_sdmpnl_is_checkout_enable($this->shipment_data['shipping_id'], $this->order_info['shipping']);
        $group_index = (isset($this->group_key) && $this->group_key !== null) ? $this->group_key : fn_sdmpnl_get_group_index($this->order_info);

        try {
            $consignmentRepo
                ->setApiKey($api_key)
                ->setReferenceId($this->order_info['order_id'])
                ->setShopId($this->order_info['company_id'])
                ->setCountry($country)
                ->setPerson($this->order_info['s_firstname'] . ' ' . $this->order_info['s_lastname'])
                ->setCompany($this->order_info['company'])
                ->setFullStreet((!empty($this->order_info['s_address']) ? $this->order_info['s_address'] : ($this->order_info['b_address']) ? $this->order_info['b_address'] : ''))
                ->setPostalCode((!empty($this->order_info['s_zipcode']) ? $this->order_info['s_zipcode'] : ($this->order_info['b_zipcode']) ? $this->order_info['b_zipcode'] : ''))
                ->setPackageType($this->shipment_options->getPackageType())
                ->setCity((!empty($this->order_info['s_city']) ? $this->order_info['s_city'] : ($this->order_info['b_city']) ? $this->order_info['b_city'] : ''))
                ->setEmail($this->order_info['email'])
                ->setPhone((!empty($this->order_info['s_phone']) ? $this->order_info['s_phone'] : ($this->order_info['b_phone']) ? $this->order_info['b_phone'] : ''))
                ->setLargeFormat($this->shipment_options->getLargeFormat())
                ->setOnlyRecipient($this->shipment_options->getOnlyRecipient())
                ->setSignature($this->shipment_options->getSignature())
                ->setReturn($this->shipment_options->getReturn())
                ->setInsurance($insurance->getAmount())
                ->setLabelDescription($this->shipment_options->getLabelDescription());
            if ((fn_sd_myparcel_nl_destination_country_is_NL($this->order_info) || $country === TariffZone::NL) && $is_checkout_enable) {
                $consignmentRepo->setDeliveryDate(date('Y-m-d H:i:s', $this->shipment_options->getDeliveryDate()));
            }
            if ($is_checkout_enable) {
                $delivery_type = $this->shipment_options->getDeliveryType();
                $consignmentRepo->setDeliveryType($delivery_type);
            }
            if (!empty($this->shipment_data['consignment_id'])) {
                $consignmentRepo->setMyParcelConsignmentId($this->shipment_data['consignment_id']);
            }

            if ($is_checkout_enable
                && !empty($delivery_type)
                && ($delivery_type == Delivery::DELIVERY_PICKUP || $delivery_type == Delivery::DELIVERY_PICKUP_EXPRESS)
                && !empty($this->delivery_options[$group_index])
            ) {
                $consignmentRepo->setPickupPostalCode($this->delivery_options[$group_index]['postal_code'])
                    ->setPickupStreet($this->delivery_options[$group_index]['street'])
                    ->setPickupCity($this->delivery_options[$group_index]['city'])
                    ->setPickupNumber($this->delivery_options[$group_index]['number'])
                    ->setPickupLocationName($this->delivery_options[$group_index]['location']);
            }
        } catch (\Exception $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        }

        return $consignmentRepo;
    }

    /**
     * @return array
     */
    public static function getServicedDestinationCountries()
    {
        return self::$servicedDestinationCountries;
    }

    /**
    * @return array
    */
    public function getDeliveryOptions($group_index = 0)
    {
        return $this->delivery_options[$group_index];
    }

    /**
    * @param array $delivery_options
    * @param int $group_index
    * @return Api
    */
    public function setDeliveryOptions($delivery_options, $group_index = 0)
    {
        $this->delivery_options[$group_index] = $delivery_options;
        return $this;
    }
    
        /**
    * @return int
    */
    public function getGroupKey()
    {
        return $this->group_key;
    }

    /**
    * @param int $group_key
    * @return Api
    */
    public function setGroupKey($group_key)
    {
        $this->group_key = $group_key;
        return $this;
    }
}