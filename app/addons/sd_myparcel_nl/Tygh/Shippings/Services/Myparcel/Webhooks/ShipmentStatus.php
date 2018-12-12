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


/**
 * Class ShipmentStatus
 * @package Tygh\Shippings\Services\Myparcel\Webhooks
 */
class ShipmentStatus
{
    /**
     * @var array
     */
    private $statuses = [
        1 => 'addons.sd_myparcel_nl.shipment_status.pending-concept',
        2 => 'addons.sd_myparcel_nl.shipment_status.pending-registered',
        3 => 'addons.sd_myparcel_nl.shipment_status.enroute-handed_to_carrier',
        4 => 'addons.sd_myparcel_nl.shipment_status.enroute-sorting',
        5 => 'addons.sd_myparcel_nl.shipment_status.enroute-distribution',
        6 => 'addons.sd_myparcel_nl.shipment_status.enroute-customs',
        7 => 'addons.sd_myparcel_nl.shipment_status.delivered-at_recipient',
        8 => 'addons.sd_myparcel_nl.shipment_status.delivered-ready_for_pickup',
        9 => 'addons.sd_myparcel_nl.shipment_status.delivered-package_picked_up',
        10 => 'addons.sd_myparcel_nl.shipment_status.delivered-return_shipment_ready_for_pickup',
        11 => 'addons.sd_myparcel_nl.shipment_status.delivered-return_shipment_package_picked_up',
        12 => 'addons.sd_myparcel_nl.shipment_status.printed-letter',
        13 => 'addons.sd_myparcel_nl.shipment_status.credit',
        30 => 'addons.sd_myparcel_nl.shipment_status.inactive-concept',
        31 => 'addons.sd_myparcel_nl.shipment_status.inactive-registered',
        32 => 'addons.sd_myparcel_nl.shipment_status.inactive-enroute-handed_to_carrier',
        33 => 'addons.sd_myparcel_nl.shipment_status.inactive-enroute-sorting',
        34 => 'addons.sd_myparcel_nl.shipment_status.inactive-enroute-distribution',
        35 => 'addons.sd_myparcel_nl.shipment_status.inactive-enroute-customs',
        36 => 'addons.sd_myparcel_nl.shipment_status.inactive-delivered-at_recipient',
        37 => 'addons.sd_myparcel_nl.shipment_status.inactive-delivered-ready_for_pickup',
        38 => 'addons.sd_myparcel_nl.shipment_status.inactive-delivered-package_picked up',
        99 => 'addons.sd_myparcel_nl.shipment_status.inactive-unknown',
        100 => 'addons.sd_myparcel_nl.shipment_status.credit_rejected',
        101 => 'addons.sd_myparcel_nl.shipment_status.credit_approved',
    ];

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->statuses;
    }

    /**
     * @param int $code
     * @return string
     */
    public function getByCode($code = 0)
    {
        return isset($this->statuses[$code]) ? $this->statuses[$code] : '';
    }

    /**
     * Update status info in DB
     */
    public function save()
    {
        foreach ($this->statuses as $code => $description) {
            $status = [
                'type' => STATUS_TYPE_SHIPMENT,
                'description' => __($description),
            ];
            $status['params'] = [
                'myparcel_code' => $code,
            ];
            $status_id = db_get_row('SELECT status_id FROM ?:status_data WHERE param = ?s AND value = ?i', 'myparcel_code', $code);
            $status['char'] = '';
            if ($status_id) {
                $status['status_id'] = $status_id;
                $status['char'] = db_get_field('SELECT status FROM ?:statuses WHERE status_id = ?i AND type = ?s', $status_id, $status['type']);
            }
            $status_code = fn_sd_myparcel_nl_update_status($status['char'], $status, $status['type']);
            if (!$status_code) {
                fn_set_notification('E', __('unable_to_create_status'), __('maximum_number_of_statuses_reached'));
            }
        }
    }

    /**
     * Delete all the statuses from DB
     */
    public function deleteAll()
    {
        foreach ($this->statuses as $code => $description) {
            $status = [
                'type' => STATUS_TYPE_SHIPMENT,
                'description' => __($description),
            ];
            $status['params'] = [
                'myparcel_code' => $code,
            ];
            $status_id = db_get_field('SELECT status_id FROM ?:status_data WHERE param = ?s AND value = ?i', 'myparcel_code', $code);
            if ($status_id) {
                $status['char'] = db_get_field('SELECT status FROM ?:statuses WHERE status_id = ?i AND type = ?s', $status_id, $status['type']);
                fn_delete_status($status['char'], $status['type']);
            }
        }
    }
}
