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

use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;
use Tygh\Shippings\Services\Myparcel\Traits\ExtraHeaderTrait;
use Tygh\Registry;
use Tygh\Http;

/**
 * Class PrintLabel
 * Bulk print labels
 * @package Tygh\Shippings\Services\Myparcel
 */
class PrintLabel
{
    use ExceptionsTrait;
    use ExtraHeaderTrait;

    const BASE_URL = 'https://api.myparcel.nl';
    const LABELS_URL = self::BASE_URL . '/shipment_labels/';

    /**
     * @var array
     */
    private $cscart_shipment_ids = [];

    /**
     * @var array
     */
    private $myparcel_shipment_ids = [];

    /**
     * @var string
     */
    private $page_format = Label::FORMAT_A4;

    /**
     * @var string
     */
    private $api_key = '';

    /**
     * @var string
     */
    private $response = '';

    /**
     * @var string
     */
    private $current_url = '';

    /**
     * @var bool
     */
    private $silent_mode = false;

    /**
     * PrintLabel constructor.
     * @param array $params
     */
    public function __construct(array $params)
    {
        if (!isset($params['shipment_ids'])) {
            $this->throwConstructorParamsException();
        }
        if (isset($params['current_url'])) {
            $this->current_url = $params['current_url'];
        }
        if (isset($params['silent'])) {
            $this->silent_mode = (bool) $params['silent'];
        }
        $this->cscart_shipment_ids = (array) $params['shipment_ids'];
        $this->myparcel_shipment_ids = $this->getMyparcelShipmentIds();
        $this->page_format = Registry::get('addons.sd_myparcel_nl.bulk_print_label_page_format');
        $this->api_key = isset($params['api_key']) ? $params['api_key'] : Registry::get('addons.sd_myparcel_nl.api_key');
    }

    /**
     * @return array
     */
    public function getMyparcelShipmentIds()
    {
        $result = db_get_fields('SELECT consignment_id FROM ?:shipments WHERE shipment_id IN (?n)', $this->cscart_shipment_ids);

        return array_values(array_filter($result));
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return isset($this->response['errors']) ? $this->response['errors'] : [];
    }

    /**
     * @return bool
     */
    public function printLabels()
    {
        $url = self::LABELS_URL . implode(';', $this->myparcel_shipment_ids) . '/?format=' . $this->page_format;
        $extra = $this->getExtraHeaders();
        $extra['headers'][] = 'Accept: application/json; charset=utf-8';
        $raw_response = Http::get($url, [], $extra);
        $this->response = json_decode($raw_response, true);
        $this->processErrors();
        $redirect_url = !empty($this->current_url) ? $this->current_url : fn_url('');
        if (isset($this->response['data'], $this->response['data']['pdfs'], $this->response['data']['pdfs']['url'])) {
            $redirect_url = self::BASE_URL . $this->response['data']['pdfs']['url'];
        }

        return [$redirect_url, $this];
    }

    private function processErrors()
    {
        $response = $this->response;
        if (!isset($response['errors']) || $this->silent_mode) {
            return;
        }
        $error_text = '';
        if (!empty($response['message'])) {
            $error_text .= $response['message'] . ' ';
        }
        foreach ($response['errors'] as $error) {
            if (!empty($error['code'])) {
                $error_text .= __('exception_error_code') . ' ' . $error['code'] . ': ';
            }
            if (!empty($error['message'])) {
                $error_text .= $error['message'];
            }
            if (!empty($error['account_shipment_ids'])) {
                $error_text .= __('addons.sd_myparcel_nl.account_shipment_ids') . ': ' . implode(',', $error['account_shipment_ids']);
            }
            $error_text .= '<br>';
        }
        fn_set_notification('E', __('error'), $error_text);
    }

    /**
     * @return mixed|string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }
}
