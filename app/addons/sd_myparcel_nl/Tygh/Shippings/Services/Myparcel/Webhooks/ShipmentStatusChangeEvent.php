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

namespace Tygh\Shippings\Services\Myparcel\Webhooks;

use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;


/**
 * Class ShipmentStatusChangeEvent
 * Implements update status via webhook requests logic
 * @package Tygh\Shippings\Services\Myparcel\Webhooks
 */
class ShipmentStatusChangeEvent
{
    use ExceptionsTrait;

    /**
     * @var integer
     */
    private $shipment_id = 0;

    /**
     * @var integer
     */
    private $account_id = 0;

    /**
     * @var integer
     */
    private $shop_id = 0;

    /**
     * @var integer
     */
    private $status = 0;

    /**
     * @var string
     */
    private $barcode = '';

    /**
     * ShipmentStatusChangeEvent constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (!empty($params['shipment_id'])) {
            $this->shipment_id = intval($params['shipment_id']);
        } else {
            $this->throwConstructorParamsException();
        }

        if (!empty($params['account_id'])) {
            $this->account_id = intval($params['account_id']);
        }

        if (!empty($params['shop_id'])) {
            $this->shop_id = intval($params['shop_id']);
        }

        if (!empty($params['status'])) {
            $this->status = intval($params['status']);
        } else {
            $this->throwConstructorParamsException();
        }

        if (!empty($params['barcode'])) {
            $this->barcode = strval($params['barcode']);
        }
    }

    /**
     * @return $this
     */
    public function updateStatus()
    {
        $old_status = db_get_field('SELECT status FROM ?:shipments WHERE consignment_id = ?i', $this->shipment_id);
        $status = fn_get_status_by_id($this->getStatusId());
        if ($status['status'] != $old_status) {
            fn_tools_update_status(
                [
                    'table' => 'shipments',
                    'id_name' => 'consignment_id',
                    'id' => $this->shipment_id,
                    'status' => $status['status'],
                ]
            );
        }

        return $this;
    }

    /**
     * @return int
     */
    private function getStatusId()
    {
        $result = (int) db_get_field('SELECT status_id FROM ?:status_data WHERE param = ?s AND value = ?s', 'myparcel_code', $this->status);
        return $result;
    }
}
