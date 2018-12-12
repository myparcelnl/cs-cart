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
use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// Here is functions that copied from core functions and modified

/**
 * Create/update shipment
 *
 * @param array $shipment_data Array of shipment data.
 * @param int $shipment_id Shipment identifier
 * @param int $group_key Group number
 * @param bool $all_products
 * @param mixed $force_notification user notification flag (true/false), if not set, will be retrieved from status parameters
 * @return int $shipment_id
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_sd_myparcel_nl_update_shipment($shipment_data, $shipment_id = 0, $group_key = 0, $all_products = false, $force_notification = array())
{
    if (!empty($shipment_id)) {

        $fields = [
            'carrier',
            'comments',
            'consignment_id',
            'delivery_type',
            'insurance',
            'label_format',
            'label_position',
            'large_format',
            'only_recipient',
            'package_type',
            'return',
            'signature',
            'timestamp',
            'tracking_number',
        ];
        $shipment_data = array_intersect_key($shipment_data, array_reduce($fields, function ($acc, $item) {
            $acc[$item] = 1;
            return $acc;
        }, []));

        $arow = db_query("UPDATE ?:shipments SET ?u WHERE shipment_id = ?i", $shipment_data, $shipment_id);
        if ($arow === false) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('shipment'))),'','404');
            $shipment_id = false;
        }

        /**
         * Called after new shipment creation.
         *
         * @param array $shipment_data Array of shipment data.
         * @param array $order_info Shipment order info
         * @param int $group_key Group number
         * @param bool $all_products
         * @param int $shipment_id Created shipment identifier
         */
        list($_shipment) = fn_get_shipments_info([
            'shipment_id' => $shipment_id,
            'advanced_info' => true,
        ]);
        $_shipment = reset($_shipment);
        $shipment = array_merge($_shipment, $shipment_data);
        $order_info = fn_get_order_info($shipment['order_id'], false, true, true);
        fn_set_hook('update_shipment_post', $shipment, $order_info, $group_key, $all_products, $shipment_id);

    } else {

        if (empty($shipment_data['order_id']) || empty($shipment_data['shipping_id'])) {
            return false;
        }

        $order_info = fn_get_order_info($shipment_data['order_id'], false, true, true);

        if (empty($shipment_data['tracking_number']) && empty($shipment_data['carrier'])) {
            return false;
        }

        if ($all_products) {
            foreach ($order_info['product_groups'] as $group) {
                foreach ($group['products'] as $item_key => $product) {

                    if (!empty($product['extra']['group_key'])) {
                        if ($group_key == $product['extra']['group_key']) {
                            $shipment_data['products'][$item_key] = $product['amount'];
                        }
                    } elseif ($group_key == $order_info['shipping'][0]['group_key']) {
                        $shipment_data['products'][$item_key] = $product['amount'];
                    }
                }
            }
        }

        if (!empty($shipment_data['products']) && fn_check_shipped_products($shipment_data['products'])) {

            fn_set_hook('create_shipment', $shipment_data, $order_info, $group_key, $all_products);

            foreach ($shipment_data['products'] as $key => $amount) {
                if (isset($order_info['products'][$key])) {
                    $amount = intval($amount);

                    if ($amount > ($order_info['products'][$key]['amount'] - $order_info['products'][$key]['shipped_amount'])) {
                        $shipment_data['products'][$key] = $order_info['products'][$key]['amount'] - $order_info['products'][$key]['shipped_amount'];
                    }
                }
            }

            if (fn_check_shipped_products($shipment_data['products'])) {
                $shipment_data['timestamp']  = isset($shipment_data['timestamp']) ? fn_parse_date($shipment_data['timestamp']) : TIME;

                $shipment_id = db_query("INSERT INTO ?:shipments ?e", $shipment_data);

                if (!empty($shipment_id)) {
                    $is_created = true;
                    foreach ($shipment_data['products'] as $key => $amount) {

                        if ($amount == 0) {
                            continue;
                        }

                        $_data = array(
                            'item_id' => $key,
                            'shipment_id' => $shipment_id,
                            'order_id' => $shipment_data['order_id'],
                            'product_id' => $order_info['products'][$key]['product_id'],
                            'amount' => $amount,
                        );

                        db_query('INSERT INTO ?:shipment_items ?e', $_data);
                    }

                    if (fn_check_permissions('orders', 'update_status', 'admin') && !empty($shipment_data['order_status'])) {
                        fn_change_order_status($shipment_data['order_id'], $shipment_data['order_status']);
                    }
                } else {
                    $is_created = false;
                }

                /**
                 * Called after new shipment creation.
                 *
                 * @param array $shipment_data Array of shipment data.
                 * @param array $order_info Shipment order info
                 * @param int $group_key Group number
                 * @param bool $all_products
                 * @param int $shipment_id Created shipment identifier
                 */
                fn_set_hook('create_shipment_post', $shipment_data, $order_info, $group_key, $all_products, $shipment_id, $is_created);

                if ($is_created) {
                    if (!empty($force_notification['C'])) {
                        /** @var \Tygh\Mailer\Mailer $mailer */
                        $mailer = Tygh::$app['mailer'];

                        $shipment = array(
                            'shipment_id' => $shipment_id,
                            'timestamp' => $shipment_data['timestamp'],
                            'shipping' => db_get_field('SELECT shipping FROM ?:shipping_descriptions WHERE shipping_id = ?i AND lang_code = ?s', $shipment_data['shipping_id'], $order_info['lang_code']),
                            'tracking_number' => $shipment_data['tracking_number'],
                            'carrier_info' => Shippings::getCarrierInfo($shipment_data['carrier'], $shipment_data['tracking_number']),
                            'comments' => !empty($shipment_data['comments']) ? $shipment_data['comments'] : '',
                            'products' => $shipment_data['products'],
                        );
                        if (!empty($shipment_data['carrier']) && $shipment_data['carrier'] == MYPARCEL_CARRIER_CODE && !empty($shipment_data['order_id'])) {
                            $shipment['order_id'] = $shipment_data['order_id'];
                            $shipment['carrier_info']['tracking_url'] = fn_sd_myparcel_nl_form_tracking_url($shipment);
                        }

                        $mailer->send(array(
                            'to' => $order_info['email'],
                            'from' => 'company_orders_department',
                            'data' => array(
                                'shipment' => $shipment,
                                'order_info' => $order_info,
                            ),
                            'template_code' => 'shipment_products',
                            'tpl' => 'shipments/shipment_products.tpl', // this parameter is obsolete and is used for back compatibility
                            'company_id' => $order_info['company_id'],
                        ), 'C', $order_info['lang_code']);
                    }

                    fn_set_notification('N', __('notice'), __('shipment_has_been_created'), '', 'shipment_has_been_created');
                }
            }

        } else {
            fn_set_notification('E', __('error'), __('products_for_shipment_not_selected'));
        }

    }

    return $shipment_id;
}

/**
 * Updates statuses
 * @param string $status One letter status code that should be updated
 * @param array $status_data Status information
 * @param string $type One letter status type
 * @param string $lang_code Language code
 * @return array Updated status data
 */
function fn_sd_myparcel_nl_update_status($status, $status_data, $type, $lang_code = DESCR_SL)
{
    if (empty($status_data['status'])) {
        // Generate new status code
        $existing_codes = db_get_fields('SELECT status FROM ?:statuses WHERE type = ?s GROUP BY status', $type);
        $existing_codes[] = 'N'; // STATUS_INCOMPLETED_ORDER
        $existing_codes[] = 'T'; // STATUS_PARENT_ORDER
        $codes = array_diff(range('A', 'Z'), $existing_codes);
        $status_data['status'] = reset($codes);
    } else {
        $is_default = !empty($status_data['is_default']) && $status_data['is_default'] == 'Y';
        $status_data['status_id'] = !empty($status_data['status_id']) ? $status_data['status_id'] : fn_get_status_id($status_data['status'], $type, $is_default);
    }

    $can_continue = !empty($status_data['status']);

    // Create status to have its identifier
    if ($can_continue && empty($status_data['status_id'])) {
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];
        /** @var \Tygh\Template\Mail\Service $service */
        $service = Tygh::$app['template.mail.service'];

        $status_data['type'] = $type;
        $status_data['status_id'] = db_query("INSERT INTO ?:statuses ?e", $status_data);

        if ($type == STATUSES_ORDER) {
            foreach (array('A', 'C') as $email_templates_area) {
                $email_template = $repository->findByCodeAndArea('order_notification_default', $email_templates_area);

                if ($email_template) {
                    $service->cloneTemplate(
                        $email_template,
                        array(
                            'code' => 'order_notification.' . strtolower($status_data['status']),
                            'area' => $email_templates_area
                        )
                    );
                }
            }
        }
    }

    /**
     * Performs additional actions before status description and data updated
     *
     * @param string $status       One-letter status code
     * @param array  $status_data  Status description and properties
     * @param string $type         One-letter status type
     * @param string $lang_code    Two-letter language code
     * @param bool   $can_continue If true, status description and data will be updated
     */
    // During the addon installation we need launch this hook, so add call of function here
    fn_sd_myparcel_nl_update_status_pre($status, $status_data, $type, $lang_code, $can_continue);
    fn_set_hook('update_status_pre', $status, $status_data, $type, $lang_code, $can_continue);

    SecurityHelper::sanitizeObjectData('status', $status_data);

    if ($can_continue) {
        if (empty($status)) {
            $status = $status_data['status'];
            foreach (fn_get_translation_languages() as $status_data['lang_code'] => $_v) {
                db_replace_into('status_descriptions', $status_data);
            }
        } else {
            db_query("UPDATE ?:statuses SET ?u WHERE status_id = ?i", $status_data, $status_data['status_id']);
            db_query('UPDATE ?:status_descriptions SET ?u WHERE status_id = ?i AND lang_code = ?s', $status_data, $status_data['status_id'], $lang_code);
        }

        if (!empty($status_data['params'])) {
            foreach ((array) $status_data['params'] as $param_name => $param_value) {
                $_data = array(
                    'status_id' => $status_data['status_id'],
                    'param' => $param_name,
                    'value' => $param_value
                );
                db_replace_into('status_data', $_data);
            }
        }
    }

    fn_set_hook('update_status_post', $status, $status_data, $type, $lang_code);

    return $status_data['status'];
}
