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

use Tygh\Shippings\Shippings;
use Tygh\Shippings\Services\Myparcel\Helper;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

$request = $_REQUEST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'print_labels') {
        $request['current_url'] = Registry::get('config.current_url');
        fn_sd_myparsel_nl_print_orders_labels($request);
    }

    return [CONTROLLER_STATUS_OK];

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($mode === 'details') {
        fn_sd_myparcel_nl_set_shipment_options([
            'request' => $request,
        ]);
        fn_sd_myparcel_nl_set_tracking_info([
            'request' => $request,
        ]);
        $order = fn_get_order_info($request['order_id']);
        $selected_delivery_options = fn_sdmpnl_get_selected_delivery_options([
            'cart'     => $order,
            'order_id' => $request['order_id'],
        ]);
        Tygh::$app['view']->assign([
            'selected_delivery_options' => $selected_delivery_options,
        ]);

    } elseif ($mode === 'manage') {
        $shippings = fn_get_available_shippings(Registry::get('runtime.company_id'));
        Tygh::$app['view']->assign([
            'carriers' => Shippings::getCarriers(),
            'shippings' => $shippings,
        ]);
        $helper = new Helper();
        $helper->setAllShipmentOptions();
    }
}
