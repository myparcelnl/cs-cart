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

fn_register_hooks(
    'calculate_cart_post',
    'calculate_cart_taxes_pre',
    'create_shipment_post',
    'delete_shipping',
    'get_countries',
    'get_order_info',
    'get_orders_post',
    'get_product_price_pre',
    'get_shipments',
    'get_shipments_info_post',
    'order_placement_routines',
    'update_shipment_post',
    'update_shipping',
    'update_status_pre'
);

require_once dirname(__FILE__) . '/lib/vendor/autoload.php';
