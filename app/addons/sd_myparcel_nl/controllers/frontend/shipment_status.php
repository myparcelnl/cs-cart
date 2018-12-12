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

use Tygh\Shippings\Services\Myparcel\Webhooks\ShipmentStatusChangeEvent;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

$request = $_REQUEST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '.manage';

    $_request = fn_get_contents('php://input');

    if ($mode === 'update') {
        $raw_request = fn_get_contents('php://input');
        $data = json_decode($raw_request, true);
        if (!is_array($data)) {
            return [CONTROLLER_STATUS_OK];
        }
        $request = array_merge($request, $data);
        $is_token_valid = fn_sd_myparcel_nl_is_request_valid($request);
        if (!$is_token_valid) {
            return [CONTROLLER_STATUS_OK];
        }
        $shipment_changed_event = new ShipmentStatusChangeEvent($data);
        $shipment_changed_event->updateStatus();
        // TODO: remove from release
        fn_put_contents(
            Registry::get('config.dir.root') . '/var/webhooks.log',
            var_export(
                [
                    date('Y_M_d_H:i:s') => [
                        'request' => $request,
                        'data' => $data,
                    ],
                ], true
            ) . PHP_EOL,
            '',
            DEFAULT_FILE_PERMISSIONS,
            true
        );

        exit;

    }

    return [CONTROLLER_STATUS_OK];
}
