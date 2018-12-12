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
    exit('Access denied');
}

$request = $_REQUEST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    return;

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($mode === 'details') {
        fn_sd_myparcel_nl_set_shipment_options([
            'request' => $request,
        ]);
        fn_sd_myparcel_nl_set_tracking_info([
            'request' => $request,
        ]);

    } else if ($mode === 'get_label') {
        if (empty($request['shipment_id']) || !isset($request['label_index']) || empty($request['filename'])) {
            exit;
        }

        fn_sd_myparcel_nl_open_label_file($request);

        exit;

    }
}
