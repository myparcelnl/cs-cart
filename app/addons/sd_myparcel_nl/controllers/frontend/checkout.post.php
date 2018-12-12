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
    return [CONTROLLER_STATUS_OK];

} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($mode == 'checkout') {
        if (empty($cart['chosen_shipping']) || !is_array($cart['chosen_shipping'])) {
            return;
        }

        foreach ($cart['chosen_shipping'] as $product_group => $chosen_shipping) {
            $shipping_info = fn_sd_myparcel_nl_get_shipping_info($chosen_shipping);
            if (!fn_sd_myparcel_nl_is_myparcel_shipping($shipping_info) 
                || (!empty($shipping_info['service_params']['checkout_enable']) && $shipping_info['service_params']['checkout_enable'] == 'N')
            ) {
                if (!empty($cart['delivery_options_update_set'])) {
                    unset($cart['delivery_options_update_set']);
                }
                continue;
            }
            $delivery_options = fn_sd_myparcel_nl_get_delivery_options($context);
            if (empty($delivery_options)) {
                fn_set_notification('W', __('notice'), __('addons.sd_myparcel_nl.empty_delivery_options'));
            }
            fn_sd_myparcel_nl_set_delivery_options_view($delivery_options);
        }
    }
}
