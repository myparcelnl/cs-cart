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

use Tygh\Shippings\Services\Myparcel\Webhooks\ShipmentStatus;
use Tygh\Shippings\Services\Myparcel\Webhooks\ShipmentStatusChangeEvent;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

if ($mode === 'import_tariff_zones') {
    fn_sd_myparcel_nl_import_tariff_zones();

} else if ($mode === 'update_statuses') {
    $shipment_statuses = new ShipmentStatus();
    $shipment_statuses->save();

} else if ($mode === 'delete_statuses') {
    $shipment_statuses = new ShipmentStatus();
    $shipment_statuses->deleteAll();

} else if ($mode === 'test_update_status') {
    $myparcel_status = new ShipmentStatus();
    $statuses = array_keys($myparcel_status->getAll());
    $shipment_changed_event = new ShipmentStatusChangeEvent(
        [
            'shipment_id' => 151,
            'account_id' => 0,
            'shop_id' => 1,
            'status' => $statuses[mt_rand(0, count($statuses) - 1)],
            'barcode' => '',
        ]
    );
    $shipment_changed_event->updateStatus();
    return [CONTROLLER_STATUS_REDIRECT, 'shipments.manage'];
}
