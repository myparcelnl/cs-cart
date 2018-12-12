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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$request = $_REQUEST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '.manage';

    if (!fn_allowed_for('ULTIMATE:FREE')) {
        if ($mode == 'add' && fn_sd_myparcel_nl_is_myparcel_shipping_data($request)) {
            $force_notification = fn_get_notification_rules($request);
            fn_sd_myparcel_nl_update_shipment($request['shipment_data'], 0, 0, false, $force_notification);
            if (empty($request['shipment_data']['tracking_number']) && empty($request['shipment_data']['carrier'])) {
                fn_set_notification('E', __('notice'), __('error_shipment_not_created'));
            }
            if (empty($request['return_url'])) {
                $suffix = '.details?order_id=' . $request['shipment_data']['order_id'];
            } else {
                $suffix = $request['return_url'];
            }

            return array(CONTROLLER_STATUS_REDIRECT, 'orders' . $suffix);

        } elseif ($mode == 'update' && fn_sd_myparcel_nl_is_myparcel_shipment(
            fn_sd_myparcel_nl_get_shipment(
                $request['shipment_id']
            )
        )) {
            $shipment_data = $request['shipment_data'];
            if (!empty($shipment_data['date'])) {
                $shipment_data['timestamp'] = fn_parse_datetime($shipment_data['date']['date'] . ' ' . $shipment_data['date']['time']);
            }
            fn_sd_myparcel_nl_update_shipment($shipment_data, $request['shipment_id']);

            return array(CONTROLLER_STATUS_OK, 'shipments.details?shipment_id=' . $request['shipment_id']);

        } elseif ($mode === 'print_labels' && !empty($request['shipment_ids'])) {
            list($redirect_url, $print_labels) = fn_sd_myparcel_nl_print_labels($request);
            $errors = $print_labels->getErrors();
            if ($errors) {
                fn_sd_myparcel_nl_process_orders_labels_errors($errors);
            }
            fn_redirect($redirect_url, true);
        }
    }

    return [CONTROLLER_STATUS_OK];
}
