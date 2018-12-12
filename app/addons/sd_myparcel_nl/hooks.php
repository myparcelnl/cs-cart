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

use Tygh\Registry;

/**
 * Prepare params for getting countries SQL query
 *
 * @param array $params Params list
 * @param int $items_per_page Countries per page
 * @param str $lang_code Language code
 * @param array $fields Fields list
 * @param array $joins Joins list
 * @param str $condition Conditions query
 * @param str $group Group condition
 * @param str $sorting Sorting condition
 * @param str $limit Limit condition
 */
function fn_sd_myparcel_nl_get_countries($params, $items_per_page, $lang_code, &$fields, $joins, $condition, $group, $sorting, $limit)
{
    $fields[] = 'tariff_zone';
}

/**
 * Get and save labels during create shipments
 *
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 * @param $shipment_id
 * @return bool
 *
 * @see fn_update_shipment
 */
function fn_sd_myparcel_nl_create_shipment_post(&$shipment_data, $order_info, $group_key, $all_products, $shipment_id, &$is_created)
{
    if ($is_created) {
        $shipment_data = fn_sdmpnl_update_shipment_and_save_label(compact('shipment_data', 'order_info', 'shipment_id', 'group_key'));
        if (Registry::get('skip_customer_notification')) {
            $is_created = false;

            fn_delete_shipments(array($shipment_id));
        }
        Registry::set('skip_customer_notification', false);
    }

    return true;
}

/**
 * Get and save labels during update shipments (same as create)
 * @todo DRY
 *
 * @param $shipment_data
 * @param $order_info
 * @param $group_key
 * @param $all_products
 * @param $shipment_id
 * @return bool
 *
 * @see fn_update_shipment
 */
function fn_sd_myparcel_nl_update_shipment_post(&$shipment_data, $order_info, $group_key, $all_products, $shipment_id)
{
    $shipment_data = fn_sdmpnl_update_shipment_and_save_label(compact('shipment_data', 'order_info', 'shipment_id', 'group_key'));

    return true;
}

/**
 * @param array $shipments
 * @param array $params
 *
 * @see fn_get_shipments_info
 */
function fn_sd_myparcel_nl_get_shipments_info_post(&$shipments, $params)
{
    $settings = Registry::get('addons.sd_myparcel_nl');
    foreach ($shipments as &$shipment) {
        if (empty($shipment['tracking_number']) || empty($shipment['carrier_info'])) {
            continue;
        }
        $shipment['carrier_info']['tracking_url'] = fn_sd_myparcel_nl_form_tracking_url($shipment);
        if ($settings['get_tracking_info_via_webhooks'] == 'N') {
            $shipment['carrier_info']['tracking_info'] = fn_sd_myparcel_nl_get_tracking_info($shipment);
        } else if (isset($shipment['carrier_info']['tracking_info'])) {
            $shipment['carrier_info']['track-ing_info'] = $shipment['carrier_info']['tracking_info'];
        } else {
            $shipment['carrier_info']['tracking_info'] = [];
            $status = fn_get_status_data($shipment['status'], STATUS_TYPE_SHIPMENT);
            $shipment['carrier_info']['tracking_info']['data']['tracktraces'] = [
                [
                    'shipment_id' => $shipment['consignment_id'],
                    'code' => '',
                    'final' => false,
                    //'description' => isset($status['description']) ? $status['description'] : '',
                    'time' => date('Y-mm-dd H:i'),
                    'history' => [],
                ],
            ];
        }
        $shipment['carrier_info']['labels'] = fn_sd_myparcel_nl_get_labels($shipment);
    }
}

/**
 * @param $params
 * @param $fields_list
 * @param $joins
 * @param $condition
 * @param $group
 *
 * @see fn_get_shipments_info
 */
function fn_sd_myparcel_nl_get_shipments($params, &$fields_list, $joins, $condition, $group)
{
    $fields_list[] = '?:shipments.delivery_type';
    $fields_list[] = '?:shipments.package_type';
    $fields_list[] = '?:shipments.only_recipient';
    $fields_list[] = '?:shipments.signature';
    $fields_list[] = '?:shipments.return';
    $fields_list[] = '?:shipments.label_format';
    $fields_list[] = '?:shipments.large_format';
    $fields_list[] = '?:shipments.insurance';
    $fields_list[] = '?:shipments.shipping_id';
    $fields_list[] = '?:shipments.consignment_id';
    $fields_list[] = '?:shipments.label_position';
}

/**
 * @param $params
 * @param $orders
 *
 * @return bool
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_sd_myparcel_nl_get_orders_post($params, &$orders)
{
    if (Registry::get('runtime.controller') !== 'orders') {
        return false;
    }
    $is_shipment_options_assigned = false;
    foreach ($orders as &$order) {
        $order = fn_get_order_info($order['order_id'], false, true, false, false);
        if (!isset($order['shipping'])) {
            continue;
        }
        foreach ($order['shipping'] as $products_group_key => &$shipping) {
            if (empty($shipping['delivery_options'])) {
                if (!fn_sd_myparcel_nl_is_myparcel_shipping($shipping)) {
                    continue;
                }
                $shipping['delivery_options'] = fn_sd_myparcel_nl_get_selected_delivery_options([
                    'cart'     => $order,
                    'order_id' => $order['order_id'],
                ]);
            }
        }

        list($shipments) = fn_get_shipments_info([
            'order_id' => $order['order_id'],
            'advanced_info' => true,
        ]);
        if (!fn_check_user_access(Tygh::$app['session']['auth']['user_id'], 'edit_order')) {
            $order['need_shipment'] = false;
        }
        foreach ($shipments as $shipment_key => $shipment) {
            $order['shipping'][$shipment['group_key']]['shipment_keys'][] = $shipment_key;
        }
        $order['shipments'] = $shipments;
        if (!$is_shipment_options_assigned) {
            fn_sd_myparcel_nl_set_shipment_options([
                'request' => [
                    'order_id' => $order['order_id'],
                ],
            ]);
            $is_shipment_options_assigned = true;
        }
    }
    return true;
}

/**
 * @param $shipping_data
 * @param $shipping_id
 * @param $lang_code
 * @return bool
 *
 * @see fn_update_shipping
 */
function fn_sd_myparcel_nl_update_shipping($shipping_data, $shipping_id, $lang_code)
{
    $company_id = fn_get_runtime_company_id();
    if (!$company_id) {
        return false;
    }
    $shipping_data['shipping_id'] = $shipping_id;
    $webhook = 'shipment_status_change';
    $subscription = db_get_row('SELECT id FROM ?:myparcel_webhooks_subscriptions WHERE shipping_id = ?i AND hook = ?s', $shipping_id, $webhook);
    if (empty($subscription)) {
        fn_sd_myparcel_nl_create_webhook_subscription([
            'shipping' => $shipping_data,
            'company_id' => $company_id,
        ]);
    }
}

/**
 * @param $shipping_id
 * @param $result
 * @return bool
 */
function fn_sd_myparcel_nl_delete_shipping($shipping_id, $result)
{
    $company_id = fn_get_runtime_company_id();
    if (!$company_id) {
        return false;
    }
    fn_sd_myparcel_nl_delete_webhook_subscription([
        'shipping_id' => $shipping_id,
        'company_id' => $company_id,
    ]);
}

/**
 * @param $status
 * @param $status_data
 * @param $type
 * @param $lang_code
 * @param $can_continue
 */
function fn_sd_myparcel_nl_update_status_pre(&$status, &$status_data, $type, $lang_code, &$can_continue)
{
    if ($can_continue) {
        return;
    }

    $generate_random_string = function ($length = 2) {
        $characters = implode('', range('A','Z'));
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $string;
    };

    $get_status_code = function () use (&$status, $type, &$get_status_code, $generate_random_string) {
        $status_code = $generate_random_string();
        $existing_status = db_get_row('SELECT * FROM ?:statuses WHERE status = ?s AND type = ?s', $status_code, $type);
        if ($existing_status) {
            return $get_status_code();
        } else {
            return $status_code;
        }
    };

    $status_data['status'] = $status_data['char'] = $get_status_code();

    // Create status to have its identifier
    if (empty($status_data['status_id'])) {
        $status_data['type'] = $type;
        $status_data['status_id'] = db_query('INSERT INTO ?:statuses ?e', $status_data);
    }

    $can_continue = true;
}

/**
 * @param $cart
 * @param $cart_products
 * @param $product_groups
 * @param $calculate_taxes
 * @param $auth
 * @return bool
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_sd_myparcel_nl_calculate_cart_taxes_pre(&$cart, $cart_products, &$product_groups, $calculate_taxes, $auth)
{
    if (empty($cart['calculate_shipping']) || empty($cart['chosen_shipping'])) {
        return false;
    }
    // Recalculate shipping cost with selected options
    $cart['display_shipping_cost'] = $cart['shipping_cost'] = 0;
    foreach ($product_groups as $group_index => &$group) {
        foreach ($group['chosen_shippings'] as &$shipping) {
            if (!fn_sd_myparcel_nl_is_myparcel_shipping($shipping) || $shipping['shipping_id'] != $cart['chosen_shipping'][$shipping['group_key']]) {
                continue;
            }
            $delivery_options = fn_sd_myparcel_nl_get_selected_delivery_options($cart);
            if (isset($shipping['shipping_id'], $delivery_options[$shipping['group_key']])) {
                $group['shippings'][$shipping['shipping_id']]['delivery_options']
                    = $shipping['delivery_options']
                    = $delivery_options[$shipping['group_key']];
            }
            if (isset($delivery_options[$shipping['group_key']]['time']['price'])) {
                $add_rate = fn_convert_price(
                    $delivery_options[$shipping['group_key']]['time']['price']['amount'] / 100,
                    $delivery_options[$shipping['group_key']]['time']['price']['currency']
                );
                $group['shippings'][$shipping['shipping_id']]['original_rate'] = $group['shippings'][$shipping['shipping_id']]['rate'];
                $shipping['original_rate'] = $shipping['rate'];
                $group['shippings'][$shipping['shipping_id']]['rate'] += $add_rate;
                $shipping['rate'] += $add_rate;
            }
        }
        foreach ($group['shippings'] as $shipping_id => $shipping) {
            if (isset($cart['chosen_shipping'][$group_index]) && $cart['chosen_shipping'][$group_index] == $shipping_id) {
                $cart['shipping_cost'] += $shipping['rate'];
            }
        }

        if (!empty($group['shippings']) && isset($cart['chosen_shipping'][$group_index])) {
            $shipping = $group['shippings'][$cart['chosen_shipping'][$group_index]];
            $shipping_id = $shipping['shipping_id'];
            if (empty($cart['shipping'][$shipping_id])) {
                $cart['shipping'][$shipping_id] = $shipping;
                $cart['shipping'][$shipping_id]['rates'] = array();
            }
            $cart['shipping'][$shipping_id]['original_rates'] = $cart['shipping'][$shipping_id]['rates'];
            $cart['shipping'][$shipping_id]['rates'][$group_index] = $shipping['rate'];
        }
    }
    $cart['display_shipping_cost'] = $cart['shipping_cost'];
    fn_apply_stored_shipping_rates($cart);
}

/**
 * @param $cart
 * @param $auth
 * @param $calculate_shipping
 * @param $calculate_taxes
 * @param $options_style
 * @param $apply_cart_promotions
 * @param $cart_products
 * @param $product_groups
 *
 * @see fn_calculate_cart_content
 */
function fn_sd_myparcel_nl_calculate_cart_post(&$cart, $auth, $calculate_shipping, $calculate_taxes, $options_style, $apply_cart_promotions, $cart_products, &$product_groups)
{
    if (isset(Tygh::$app['view'])) {
        Tygh::$app['view']->assign('delivery_date_in_cart', fn_sd_myparcel_nl_get_delivery_date(['cart' => $cart]));
    }
}

/**
 * Save delivery options information
 *
 * @param $order_id
 * @param $force_notification
 * @param $order_info
 * @param $_error
 */
function fn_sd_myparcel_nl_order_placement_routines($order_id, $force_notification, $order_info, $_error)
{
    if (is_array($order_info['shipping'])) {
        foreach ($order_info['shipping'] as $products_group_key => $shipping) {
            if (!empty($shipping['delivery_options'])) {
                $_data[] = array(
                    'data' => json_encode($shipping['delivery_options']),
                    'order_id' => $order_id,
                    'type' => DELIVERY_OPTIONS_DATA_TYPE,
                );
                db_query('REPLACE INTO ?:order_data ?m', $_data);
                break;
            }
        }
    }
}

/**
 * Fix notice on orders.search page
 *
 * @param $product_id
 * @param $amount
 * @param $auth
 */
function fn_sd_myparcel_nl_get_product_price_pre($product_id, $amount, &$auth)
{
    if (!isset($auth['usergroup_ids'])) {
        $auth['usergroup_ids'] = [];
    }
}

/**
 * @param array $order
 * @param $additional_data
 * @return bool
 */
function fn_sd_myparcel_nl_get_order_info(&$order, $additional_data)
{
    if (!isset($order['shipping']) || !is_array($order['shipping'])) {
        return false;
    }
    foreach ($order['shipping'] as $products_group_key => &$shipping) {
        if (!fn_sd_myparcel_nl_is_myparcel_shipping($shipping)) {
            continue;
        }
        if (empty($shipping['delivery_options'])) {
            $shipping['delivery_options'] = fn_sdmpnl_get_selected_delivery_options_from_db([
                'order_id' => $order['order_id'],
            ]);
        }
    }

    return false;
}
