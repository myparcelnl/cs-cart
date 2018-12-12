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

$cart = & Tygh::$app['session']['cart'];
$request = $_REQUEST;
$context = [
    'cart' => & $cart,
    'request' => $request,
    'no_cache' => defined('NO_CACHE_MYPARCEL_REQUESTS'),
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update_steps') {
        $cart = fn_sd_myparcel_nl_update_cart($context);
        if ($request['next_step'] == 'step_three') {
            $delivery_options = fn_sd_myparcel_nl_get_delivery_options($context);
            fn_sd_myparcel_nl_set_delivery_options_view($delivery_options);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($mode == 'checkout' && !empty($request['delivery_options'])) {
        $cart['delivery_options_update_set'] = $request['delivery_options'];
        uksort($cart['delivery_options_update_set'], 'strcasecmp');
    }
}
