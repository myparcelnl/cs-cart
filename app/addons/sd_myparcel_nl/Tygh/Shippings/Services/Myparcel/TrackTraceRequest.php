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
use Tygh\Http;

/**
 * Class TrackTraceRequest
 * Perform track&trace requests
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class TrackTraceRequest
{
    use ExceptionsTrait;
    use ExtraHeaderTrait;

    private $sort_orders = [
        SORT_ASC => 'ASC',
        SORT_DESC => 'DESC',
    ];

    private
        /**
         * @var int[]
         */
        $shipment_ids = [],
        /**
         * @var string
         */
        $api_key,
        /**
         * @var int
         */
        $page,
        /**
         * @var int
         */
        $size,
        /**
        * @var string ['shipment_id', 'code', 'final', 'description', 'time', 'history']
        */
        $sort,
        /**
        * @var string ['ASC', 'DESC']
        */
        $sort_order;

    const TRACKING_ENDPOINT_URL = 'https://api.myparcel.nl/tracktraces/';

    public function __construct(array $params)
    {
        if (!isset($params['shipment_ids'], $params['api_key'])) {
            $this->throwConstructorParamsException();
        }

        $this->shipment_ids = $params['shipment_ids'];
        $this->api_key = $params['api_key'];
        $default_page_number = 1;
        $max_page_number = 1000;
        if (isset($params['page']) && $default_page_number <= $params['page'] && $params['page'] <= $max_page_number) {
            $this->page = intval($params['page']);
        } else {
            $this->page = $default_page_number;
        }
        $min_size = 30;
        $max_size = 200;
        if (isset($params['size']) && $min_size <= $params['size'] && $params['size'] <= $max_size) {
            $this->size = intval($params['size']);
        } else {
            $this->size = $min_size;
        }
        if (isset($params['sort']) && in_array(strval($params['sort']), ['shipment_id', 'code', 'final', 'description', 'time', 'history'])) {
            $this->sort = strval($params['sort']);
        } else {
            $this->sort = 'time';
        }
        if (isset($params['sort_order'])) {
            $this->sort_order = strval($params['sort_order']);
        } else {
            $this->sort_order = $this->sort_orders[SORT_ASC];
        }
    }

    public function getShipmentsInfo()
    {
        $query_string = implode(';', $this->shipment_ids);
        $query_string .= '?page=' . $this->page;
        $query_string .= '&size=' . $this->size;
        $query_string .= '&sort=' . $this->sort;
        $query_string .= '&sort_order=' . $this->sort_order;

        $url = self::TRACKING_ENDPOINT_URL . $query_string;
        $extra = $this->getExtraHeaders();

        $response = json_decode(Http::get($url, [], $extra), true);

        return $response;
    }

    /**
     * @used in ExtraHeaderTrait
     * @return string
     *
     */
    private function getApiKey()
    {
        return $this->api_key;
    }
}
