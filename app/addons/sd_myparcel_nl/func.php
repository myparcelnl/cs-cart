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

use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use MyParcelNL\Sdk\src\Model\Repository\MyParcelConsignmentRepository;
use Tygh\Registry;
use Tygh\Shippings\Services\Myparcel\Api;
use Tygh\Shippings\Services\Myparcel\Delivery\Delivery;
use Tygh\Shippings\Services\Myparcel\Delivery\DeliveryOptions;
use Tygh\Shippings\Services\Myparcel\Helper;
use Tygh\Shippings\Services\Myparcel\Insurance;
use Tygh\Shippings\Services\Myparcel\Label;
use Tygh\Shippings\Services\Myparcel\Package;
use Tygh\Shippings\Services\Myparcel\PrintLabel;
use Tygh\Shippings\Services\Myparcel\ShipmentOptions;
use Tygh\Shippings\Services\Myparcel\TariffZone;
use Tygh\Shippings\Services\Myparcel\TrackTraceRequest;
use Tygh\Shippings\Services\Myparcel\Webhooks\ShipmentStatus;
use Tygh\Shippings\Services\Myparcel\Webhooks\Subscription;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (is_file(dirname(__FILE__) . '/service_func.php')) {
    require_once dirname(__FILE__) . '/service_func.php';
}

if (is_file(dirname(__FILE__) . '/hooks.php')) {
    require_once dirname(__FILE__) . '/hooks.php';
}

if (is_file(dirname(__FILE__) . '/overrides_func.php')) {
    require_once dirname(__FILE__) . '/overrides_func.php';
}


/**
 * @return array
 */
function fn_sd_myparcel_nl_schema()
{
    return array(
        MYPARCEL_CARRIER_CODE => array(
            'en' => 'MyParcel ON',
            'status' => 'A',
            'module' => MYPARCEL_CARRIER_CODE,
            'code' => fn_strtolower(Api::MYPARCEL_SERVICE_CODE),
            'sp_file' => '',
            'description' => 'MyParcel.nl'
        ),
    );
}

/**
 * @param $shipment
 * @return string
 */
function fn_sd_myparcel_nl_form_tracking_url($shipment)
{
    if (empty($shipment['order_id'])) {
        return '';
    }
    $order_info = fn_get_order_info($shipment['order_id']);
    $result = str_replace(
        array('[POSTAL_CODE]', '[DESTINATION_COUNTRY]'),
        array(
            fn_sdmpnl_get_delivery_option_field(
                'postal_code',
                $order_info,
                's_zipcode'
            ),
            fn_sdmpnl_get_delivery_option_field(
                'cc',
                $order_info,
                's_country'
            )
        ),
        $shipment['carrier_info']['tracking_url']
    );

    return $result;
}

/**
 * @param $field
 * @param array $order_info
 * @param $default_field
 * @return mixed|string
 */
function fn_sdmpnl_get_delivery_option_field($field, array $order_info, $default_field)
{
    $result = '';
    if (!isset($order_info['shipping'])) {
        return $result;
    }
    foreach ($order_info['shipping'] as $group_index => $shipping) {
        if (isset($shipping['delivery_options'][$field])) {
            $result = $shipping['delivery_options'][$field];

        } else {
            $result = $order_info[$default_field];
        }
    }

    return $result;
}


/**
 * @param $shipment
 * @return array
 */
function fn_sd_myparcel_nl_get_labels($shipment)
{
    $labels_dir = LABELS_DIR . $shipment['shipment_id'] . DIRECTORY_SEPARATOR;
    $result = array();
    if (is_dir($labels_dir)) {
        $url = fn_url('shipments.get_label?shipment_id=' . $shipment['shipment_id']);
        $path = realpath($labels_dir);
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        $directories = [];
        foreach ($objects as $name => $object) {
            if ($name[strlen($name) - 1] === '.') {
                continue;
            }

            if (is_numeric($object->getFilename())) {
                $directories[] = $object->getFilename();
            }
        }

        foreach ($directories as $directory) {
            $url .= '&label_index=' . $directory;
            $dir = $labels_dir . $directory;
            $labels = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($labels as $name => $label_obj) {
                if ($name[strlen($name) - 1] === '.') {
                    continue;
                }
                $result[] = $url . '&filename=' . $label_obj->getFilename();
            }
        }
    }

    return $result;
}

/**
 * @param $shipment_data
 * @param $order_info
 * @return bool|\MyParcelNL\Sdk\src\Model\Repository\MyParcelConsignmentRepository
 */
function fn_sd_myparcel_nl_get_consignment($shipment_data, $order_info, $group_key)
{
    $context = [
        'group_key' => $group_key,
        'shipment_data' => $shipment_data,
        'order_info' => $order_info,
        'consignment' => new MyParcelConsignmentRepository(),
    ];
    $package = new Package($context);
    $delivery = new Delivery($context);
    $insurance = new Insurance(array_merge($context, ['package' => $package]));
    $context['shipment_options'] = new ShipmentOptions([
        'package' => $package,
        'delivery' => $delivery,
        'insurance' => $insurance,
        'order_info' => $order_info,
    ]);
    $helper = new Api($context);
    $consignment = $helper->getConsignment();

    return $consignment;
}

/**
 * @param integer $shipment_id
 * @param $label
 */
function fn_sd_myparcel_nl_save_label($shipment_id, $label)
{
    $folder = fn_sd_myparcel_nl_get_labels_path($shipment_id);
    fn_put_contents($folder . 'label.pdf', $label);
}

/**
 * @param $shipment_id
 * @return string
 */
function fn_sd_myparcel_nl_get_labels_path($shipment_id)
{
    $folder = LABELS_DIR . $shipment_id . DIRECTORY_SEPARATOR . 0 . DIRECTORY_SEPARATOR;
    return $folder;
}

/**
 * @param $shipment_data
 * @param $shipment_id
 */
function fn_sd_myparcel_nl_update_shipment_data($shipment_data, $shipment_id)
{
    $data = $shipment_data;
    $data['shipment_id'] = $shipment_id;
    $data['status'] = !empty($data['status']) ? $data['status'] : fn_sd_myparcel_nl_get_default_shipment_status();

    db_replace_into('shipments', $data);
}

/**
 * @param string $lang_code
 * @return string
 */
function fn_sd_myparcel_nl_get_default_shipment_status($lang_code = CART_LANGUAGE)
{
    $statuses = (new ShipmentStatus())->getAll();
    $status_description = __($statuses[1]);
    $status = db_get_field(
        'SELECT status FROM ?:statuses s
          LEFT JOIN ?:status_descriptions sd ON sd.status_id = s.status_id AND sd.lang_code = ?s
          WHERE sd.description = ?s AND s.type = ?s',
        $lang_code,
        $status_description,
        STATUS_TYPE_SHIPMENT
    );
    return $status;
}

/**
 * @param $shipment_data
 * @param $myParcelCollection
 * @param $consignment
 * @return array
 */
function fn_sd_myparcel_nl_set_tracking_code($shipment_data, $myParcelCollection, $consignment)
{
    $myParcelCollection
        ->setLinkOfLabels()
        ->getLinkOfLabels();
    $barcode = $consignment->getBarcode();
    if (!empty($barcode)) {
        $shipment_data['tracking_number'] = $barcode;
    }

    return $shipment_data;
}

/**
 * @param \MyParcelNL\Sdk\src\Model\MyParcelConsignment $consignment
 * @param \MyParcelNL\Sdk\src\Helper\MyParcelCollection $myParcelCollection
 * @param array|bool $label_position
 * @return string
 */
function fn_sd_myparcel_nl_get_label(MyParcelNL\Sdk\src\Model\MyParcelConsignment $consignment, MyParcelNL\Sdk\src\Helper\MyParcelCollection $myParcelCollection, $label_position = false)
{
    $label = '';
    try {
        $myParcelCollection->addConsignment($consignment);
        $myParcelCollection->createConcepts();
        $myParcelCollection->setPdfOfLabels($label_position);
        $label = $myParcelCollection->getLabelPdf();
    } catch (\Exception $e) {
        $message = $e->getMessage();
        if (strripos($message, MYPARCEL_BAD_ADDRESS_MARK) !== false) {
            fn_set_notification('E', __('error'), __('addons.sd_myparcel_nl.update_shipment_error_bad_address'));
        } else {
            fn_set_notification('E', __('error'), __('addons.sd_myparcel_nl.update_shipment_error'));
        }
    }
    return $label;
}

/**
 * @param array $params
 * @return bool
 * @throws \Tygh\Exceptions\DeveloperException
 * @todo: refactor for SRP
 */
function fn_sd_myparcel_nl_set_shipment_options(array $params = [])
{
    $helper = new Helper();
    $helper->setAllShipmentOptions();
    if (!isset($params['request']['order_id']) && !isset($params['request']['shipment_id']) && !isset($params['shipment_id'])) {
        return false;
    }
    if ((!empty($params['request']) && !empty($params['request']['shipment_id'])) || !empty($params['shipment_id'])) {
        $shipment_id = !empty($params['shipment_id']) ? $params['shipment_id'] : $params['request']['shipment_id'];
        list($shipments) = fn_get_shipments_info([
            'shipment_id' => $shipment_id,
            'advanced_info' => true,
        ]);
        $shipment = reset($shipments);
        if ($shipment) {
            if (!fn_sd_myparcel_nl_is_myparcel_shipment($shipment)) {
                return false;
            }
            Tygh::$app['view']->assign('is_myparcel_shipment', true);
            $order_id = $shipment['order_id'];
        }
    }
    if (!empty($params['request']) && !empty($params['request']['order_id'])) {
        $order_id = $params['request']['order_id'];
    }

    if (!empty($order_id)) {
        $cart = [];
        $customer_auth = [];
        fn_form_cart($order_id, $cart, $customer_auth);
        $myparcel_shipping_id = fn_sd_myparcel_nl_get_cart_chosen_myparcel_shipping_id($cart);
        if ($myparcel_shipping_id) {
            $delivery_options = fn_sd_myparcel_nl_get_delivery_options([
                'cart' => $cart,
                'request' => $params['request'],
                'no_cache' => defined('NO_CACHE_MYPARCEL_REQUESTS'),
            ]);
            if ($delivery_options) {
                fn_sd_myparcel_nl_set_delivery_options_view($delivery_options);
            }
            $delivery_types = Delivery::getAllTypes();

            $helper = new Helper($myparcel_shipping_id);
            foreach ($delivery_types as $delivery_type_description => $delivery_type) {
                if (in_array($delivery_type, $helper->getExcludedDeliveryTypes())) {
                    unset($delivery_types[$delivery_type_description]);
                }
            }

            $selected_delivery_options = fn_sdmpnl_get_selected_delivery_options([
                'cart'     => $cart,
                'order_id' => $order_id,
            ]);
            Tygh::$app['view']->assign([
                'delivery_types' => $delivery_types,
                'selected_delivery_options' => $selected_delivery_options,
            ]);
        }
    }

    return true;
}

function fn_sd_myparcel_nl_set_all_shipment_options()
{
    $params = Registry::get('addons.sd_myparcel_nl');
    $package_types = Package::getAllTypes();
    $delivery_types = Delivery::getAllTypes();
    $label_formats = Label::getAllFormats();
    $label_positions = Label::getAllPositions();
    Tygh::$app['view']->assign([
        'package_types' => $package_types,
        'package_type_package' => Package::TYPE_PACKAGE,
        'default_package_type' => $params['package_type'],
        'delivery_types' => $delivery_types,
        'default_delivery_type' => Delivery::DELIVERY_STANDARD,
        'label_formats' => $label_formats,
        'default_label_format' => $params['bulk_print_label_page_format'],
        'label_positions' => $label_positions,
        'default_label_position' => $params['label_position']
    ]);
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_open_label_file(array $params = [])
{
    if (!isset($params['shipment_id'], $params['filename'])) {
        return false;
    }
    $file_path = fn_sd_myparcel_nl_get_labels_path($params['shipment_id']) . DIRECTORY_SEPARATOR . $params['filename'];
    if (is_file($file_path)) {
        fn_get_file($file_path);
    }

    return true;
}

/**
 * @param array $shipping
 * @return bool
 */
function fn_sd_myparcel_nl_is_myparcel_shipping(array $shipping = [])
{
    return isset($shipping['service_code']) && fn_strtolower($shipping['service_code']) === fn_strtolower(Api::MYPARCEL_SERVICE_CODE);
}

/**
 * @param array $params
 * @return array
 */
function fn_sd_myparcel_nl_update_cart(array $params = [])
{
    if (!isset($params['cart'], $params['request'], $params['request']['shipping'])) {
        return isset($params['cart']) ? $params['cart'] : Tygh::$app['session']['cart'];
    }
    $cart = $params['cart'];
    $request = $params['request'];
    $shipping = $request['shipping'];
    foreach ($shipping as $product_group_key => $shippings) {
        foreach ($shippings as $shipping_id => $shipping) {
            if (!isset($shipping['delivery_date'])) {
                continue;
            }
            $delivery_date = fn_date_to_timestamp($shipping['delivery_date']);
            $cart['product_groups'][$product_group_key]['shippings'][$shipping_id]['delivery_date'] = $delivery_date;
            foreach ($cart['product_groups'][$product_group_key]['products'] as $cart_id => $product) {
                $cart['products'][$cart_id]['extra']['delivery_date'] = $delivery_date;
            }
        }
    }

    return $cart;
}

/**
 * @param array $params
 * @return array
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_sd_myparcel_nl_get_delivery_options(array $params = [])
{
    static $cache_inited = false;
    $params['cart'] = !empty($params['cart']) ? $params['cart'] : array();
    $key = fn_crc32(json_encode($params['cart']));
    $cache_name = 'myparcel_delivery_options_cache';
    $half_hour = 30 * 60;
    $cache_ttl = $half_hour;
    $cache_key = $cache_name . '.' . $key;
    if (!$cache_inited) {
        Registry::registerCache($cache_name, $cache_ttl, Registry::cacheLevel('time'), true);
        $cache_inited = true;
    }
    $result = (array) Registry::get($cache_key);
    $delivery_options = [];
    if (!empty($params['cart']['product_groups']) && (empty($result) || !empty($params['no_cache']))) {
        foreach ($params['cart']['product_groups'] as $group_index => $product_group) {
            list($params, $is_myparcel_shipping, $is_destination_country_valid) = fn_sd_myparcel_nl_prepare_parameters($group_index, $product_group, $params);
            if (!$is_myparcel_shipping || !$is_destination_country_valid) {
                continue;
            }
            $query_params = fn_sd_myparcel_nl_get_query_params($params);

            $delivery_options[$group_index] = new DeliveryOptions($query_params);
            $result[$group_index] = $delivery_options[$group_index]->getOptionsFromApi();
            if (empty($result[$group_index]['data']) || !isset($product_group['chosen_shippings'][$group_index]['service_params']['delivery_type_price'])) {
                continue;
            }
            if (isset($product_group['chosen_shippings'][$group_index]['service_params']['delivery_type_price'])) {
                $predefined_prices = $product_group['chosen_shippings'][$group_index]['service_params']['delivery_type_price'];
            } else {
                $predefined_prices = [];
            }

            foreach ($result[$group_index]['data'] as $delivery_type => &$delivery_variants) {
                if (!empty($delivery_variants) && is_array($delivery_variants)) {
                    foreach ($delivery_variants as &$date) {
                        foreach ($date['time'] as &$delivery_variant) {
                            if (empty($delivery_variant['type']) || empty($predefined_prices) || !is_numeric($predefined_prices[$delivery_variant['type']])) {
                                continue;
                            }
                            $delivery_variant['price']['amount'] = $predefined_prices[$delivery_variant['type']];
                        }
                    }
                }
            }
        }
        if (fn_sd_myparcel_nl_is_result_valid($result)) {
            Registry::set($cache_key, $result);
        }
    }

    if (isset($params['request']['order_id'])) {
        $result['order_id'] = $params['request']['order_id'];
    }

    return $result;
}

/**
 * @param array $response
 * @return bool
 */
function fn_sd_myparcel_nl_is_result_valid(array $response)
{
    $result = true;
    foreach ($response as $group_index => $delivery_options) {
        if (isset($delivery_options['errors'])) {
            $result = false;
            break;
        }
    }

    return $result;
}

/**
 * @param array $params
 * @return array
 */
function fn_sd_myparcel_nl_get_query_params(array $params)
{
    if (fn_sd_myparcel_nl_is_change_shipping($params)) {
        $shipping = fn_sd_myparcel_nl_get_shipping_info($params['request']['shipping_ids'][$params['group_index']]);
    } else if (isset($params['group_index'], $params['product_group']['chosen_shippings'][$params['group_index']])) {
        $shipping = $params['product_group']['chosen_shippings'][$params['group_index']];
    } else {
        $shipping = reset($params['product_group']['shippings']);
    }

    $helper = new Helper($shipping['shipping_id']);
    $delivery_date = fn_sd_myparcel_nl_get_delivery_date($params);
    $query_params = [
        'country_code'            => fn_sd_myparcel_nl_get_country_code($params),
        'cutoff_time'             => $helper->getCutoffTime(),
        'delivery_days_window'    => $helper->getDeliveryDaysWindow(),
        'dropoff_days'            => $helper->getDropoffDays(),
        'dropoff_delay'           => $helper->getDropoffDelay(),
        'excluded_delivery_types' => $helper->getExcludedDeliveryTypes(),
        'postal_code'             => fn_sd_myparcel_nl_get_postal_code($params),
        'selected_delivery_date'  => strtotime($delivery_date['date'] . ' ' . $delivery_date['start']),
        'shipping'                => $shipping,
        'street_number'           => fn_sd_myparcel_nl_get_street_number($params),
    ];

    return $query_params;
}

/**
 * @param $group_index
 * @param $product_group
 * @param array $params
 * @return array
 */
function fn_sd_myparcel_nl_prepare_parameters($group_index, $product_group, array $params)
{
    $default_params = [
        'lang_code' => CART_LANGUAGE,
    ];
    $params['group_index'] = $group_index;
    $params['product_group'] = $product_group;
    $params = array_merge($default_params, $params);
    $is_change_shipping_selected = fn_sd_myparcel_nl_is_change_shipping($params);
    $chosen_shipping = $is_change_shipping_selected ?
        fn_sd_myparcel_nl_get_shipping_info($params['request']['shipping_ids'][$group_index]) :
        (isset($product_group['chosen_shippings']) ? $product_group['chosen_shippings'][$group_index] : []);
    if (empty($chosen_shipping)) {
        $shippings = !empty($product_group['shippings']) ? $product_group['shippings'] : fn_get_shippings(false, $params['lang_code']);
        if (count($shippings) === 1) {
            $_chosen_shipping = reset($shippings);
            $chosen_shipping = fn_sd_myparcel_nl_get_shipping_info(isset($_chosen_shipping['shipping_id']) ? $_chosen_shipping['shipping_id'] : 0);
        }
    }
    $is_parameters_valid = isset($chosen_shipping['service_code']) || isset(
            $product_group['chosen_shippings'][$group_index]['service_code']
        ) || $is_change_shipping_selected;
    $is_myparcel_shipping = false;
    if (!empty($chosen_shipping)) {
        $is_myparcel_shipping = $is_parameters_valid && isset($chosen_shipping['service_code']) && fn_sd_myparcel_nl_is_myparcel_shipping($chosen_shipping);
    }
    $is_destination_country_valid = in_array(fn_sd_myparcel_nl_get_country_code($params), Api::getServicedDestinationCountries());

    return array($params, $is_myparcel_shipping, $is_destination_country_valid);
}

/**
 * @param $group_index
 * @param array $params
 * @return array
 */
function fn_sd_myparcel_nl_is_change_shipping(array $params)
{
    $is_change_shipping_selected = isset(
        $params['request'],
        $params['request']['shipping_ids'],
        $params['request']['shipping_ids']
    );
    return $is_change_shipping_selected;
}

/**
 * @param $shipping_id
 * @return array
 */
function fn_sd_myparcel_nl_get_shipping_info($shipping_id)
{
    $result = fn_get_shipping_info($shipping_id);
    if (!empty($result['service_id'])) {
        $service_info = fn_get_shipping_service_data($result['service_id']);
        $result['service_code'] = $service_info['code'];
    }

    return $result;
}

/**
 * @param array $params
 * @return string
 */
function fn_sd_myparcel_nl_get_country_code(array $params = [])
{
    $result = '';
    if (isset($params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'])) {
        $delivery_options = $params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'];
    } else {
        $delivery_options = [];
    }
    if (isset($delivery_options['type']['time']['type'], $delivery_options['cc'])
        && in_array(
            $delivery_options['type']['time']['type'],
            [Delivery::DELIVERY_PICKUP, Delivery::DELIVERY_PICKUP_EXPRESS]
        )
    ) {
        $result = $delivery_options['cc'];

    } elseif (isset($params['request']['user_data']['b_country'], $params['request']['ship_to_another'])) {
        if ($params['request']['ship_to_another'] == 0) {
            $result = $params['request']['user_data']['b_country'];

        } else {
            $result = $params['request']['user_data']['s_country'];
        }

    } else if (isset($params['product_group']['package_info']['location']['country'])) {
        $result = $params['product_group']['package_info']['location']['country'];

    } else if (isset($params['cart']['user_data']['s_country'])) {
        $result = $params['cart']['user_data']['s_country'];

    }

    return $result;
}

/**
 * @param array $params
 * @return string
 */
function fn_sd_myparcel_nl_get_postal_code(array $params = [])
{
    $result = '';
    if (isset($params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'])) {
        $delivery_options = $params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'];
    } else {
        $delivery_options = [];
    }
    if (isset($delivery_options['type']['time']['type'], $delivery_options['postal_code'])
        && in_array(
            $delivery_options['type']['time']['type'],
            [Delivery::DELIVERY_PICKUP, Delivery::DELIVERY_PICKUP_EXPRESS]
        )
    ) {
        $result = $delivery_options['postal_code'];

    } elseif (isset($params['request']['user_data']['b_zipcode'], $params['request']['ship_to_another'])) {
        if ($params['request']['ship_to_another'] == 0) {
            $result = $params['request']['user_data']['b_zipcode'];
        } else {
            $result = $params['request']['user_data']['s_zipcode'];
        }
    } else if (isset($params['product_group']['package_info']['location']['zipcode'])) {
        $result = $params['product_group']['package_info']['location']['zipcode'];

    }

    return $result;
}

/**
 * @param array $params
 * @return int|mixed
 */
function fn_sd_myparcel_nl_get_street_number(array $params = [])
{
    $result = 0;
    if (isset($params['request']['user_data']['b_address'], $params['request']['ship_to_another'])) {
        if ($params['request']['ship_to_another'] == 0) {
            $address = $params['request']['user_data']['b_address'];
        } else {
            $address = $params['request']['user_data']['s_address'];
        }
    } else if (isset($params['product_group']['package_info']['location']['address'])) {
        $address = $params['product_group']['package_info']['location']['address'];
    }

    if (isset($address)) {
        try {
            $parsed_address = fn_sd_myparcel_nl_parse_full_street_address($address);
            $result = $parsed_address['number'];

        } catch (\Exception $exception) {
            fn_set_notification('E', __('error'), $exception->getMessage());
        }
    }

    if (isset($params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'])) {
        $delivery_options = $params['product_group']['chosen_shippings'][$params['group_index']]['delivery_options'];
    } else {
        $delivery_options = [];
    }
    if (isset($delivery_options['type']['time']['type'], $delivery_options['number'])
        && in_array(
            $delivery_options['type']['time']['type'],
            [Delivery::DELIVERY_PICKUP, Delivery::DELIVERY_PICKUP_EXPRESS]
        )
    ) {
        $result = $delivery_options['number'];
    }

    return $result;
}

/**
 * @param string $full_street_address
 * @return array
 * @throws Exception
 */
function fn_sd_myparcel_nl_parse_full_street_address($full_street_address = '')
{
    $matches = [];
    $parse_result = preg_match(MyParcelConsignmentRepository::SPLIT_STREET_REGEX, $full_street_address, $matches);
    if (!$parse_result || !is_array($matches) || $full_street_address != $matches[0]) {
        if (isset($matches[0]) && $full_street_address != $matches[0]) {
            // Characters are gone by preg_match
            throw new \Exception('Something went wrong with splitting up address ' . $full_street_address);
        } else {
            // Invalid full street supplied
            throw new \Exception('Invalid full street supplied: ' . $full_street_address);
        }
    }

    //for address type: street - "Plein 1945", housenumber - "27"
    $extra_street = fn_get_schema('sdmn', 'extra_street');
    if (!empty($matches['street']) 
        && !empty($matches['number'])
        && in_array($matches['street'] . ' ' . $matches['number'], $extra_street) !== false
        && !empty($matches['number_suffix']) && is_numeric(trim($matches['number_suffix']))
    ) {
        $street = $matches['street'] . ' ' . $matches['number'];
        $number = trim($matches['number_suffix']);
        $number_suffix = '';
    } else {
        if (isset($matches['street'])) {
            $street = $matches['street'];
        }

        if (isset($matches['number'])) {
            $number = $matches['number'];
        }

        if (isset($matches['number_suffix'])) {
            $number_suffix = trim($matches['number_suffix']);
        }
    }

    $streetData = array(
        'street' => $street,
        'number' => $number,
        'number_suffix' => $number_suffix,
    );

    return $streetData;
}

/**
 * @param $params
 * @return int
 */
function fn_sd_myparcel_nl_get_delivery_date(array $params = [])
{
    $result = [
        'date'  => '',
        'start' => '',
        'end'   => '',
    ];
    if (!isset($params['cart']) && !isset($params['group_index'])) {
        return $result;
    }
    if (empty($result['date']) && isset($params['cart'])) {
        $group_index = isset($params['group_index']) ? $params['group_index'] : 0;
        $result = fn_sd_myparcel_nl_get_delivery_date_from_cart($params['cart'], $group_index);
    }

    return $result;
}

/**
 * @param array $cart
 * @param int $group_index
 * @return array
 */
function fn_sd_myparcel_nl_get_delivery_date_from_cart(array $cart, $group_index = 0)
{
    $result = [
        'date'  => '',
        'start' => '',
        'end'   => '',
    ];
    if (!isset($cart['shipping'][$group_index]['delivery_options']['date'])) {
        return $result;
    }
    $date = $cart['shipping'][$group_index]['delivery_options']['date'];
    $datetime = $date;
    if (isset($cart['shipping'][$group_index]['delivery_options']['time']['start'])) {
        $time = $cart['shipping'][$group_index]['delivery_options']['time']['start'];
        $datetime .= ' ' . $time;

    } elseif (isset($cart['shipping'][$group_index]['delivery_options']['start_time'])) {
        $time = $cart['shipping'][$group_index]['delivery_options']['start_time'];
        $datetime .= ' ' . $time;
    }

    if (isset($cart['shipping'][$group_index]['delivery_options']['time']['end'])) {
        $end = $cart['shipping'][$group_index]['delivery_options']['time']['end'];
    } else {
        $end = '';
    }

    $result = [
        'date'  => $date,
        'start' => $time,
        'end'   => $end,
    ];

    return $result;
}

/**
 * @param array $delivery_options [['delivery' => [], 'pickup' => []]] || [['errors' => []]]
 * @return bool
 */
function fn_sd_myparcel_nl_set_delivery_options_view(array $delivery_options = [])
{
    if (count($delivery_options) != 1) {
        return false;
    }

    if (isset($delivery_options['order_id'])) {
        $order_id = $delivery_options['order_id'];
        unset($delivery_options['order_id']);
    }

    $option = reset($delivery_options);

    if (empty($option['errors'])) {
        if (!empty($option['data'])) {
            foreach ($option['data'] as $delivery_type => &$options) {
                foreach ($options as &$variants) {
                    $min_amount = array_reduce($variants['time'], function ($acc, $variant) {
                        if ($variant['price']['amount'] < $acc) {
                            $acc = $variant['price']['amount'];
                        }

                        return $acc;
                    }, 1e6);
                    foreach ($variants['time'] as &$variant) {
                        $variant['is_min_amount'] = $variant['price']['amount'] == $min_amount;
                    }
                }
            }
            $delivery = $option['data']['delivery'];
            $pickup = $option['data']['pickup'];
            $message = isset($option['message']) ? $option['message'] : '';
            Tygh::$app['view']->assign([
                'delivery_options' => $delivery,
                'pickup_options' => $pickup,
                'message' => $message,
            ]);
        }

    } else {
        foreach ($option['errors'] as $error) {
            $message = isset($error['human']) ? $error['human'] : (isset($error['message']) ? $error['message'] : '');
            if (isset($order_id)) {
                $message .= '<br>' . __('order') . ': ' . fn_url('orders.details?order_id=' . $order_id);
                $message .= '<br>' . __('addons.sd_myparcel_nl.order_was_created_with_obsolete_shipping_api_key');
            }
            fn_set_notification('E', __('error'), $message ?: '');
        }
    }

    return true;
}

/**
 * @param array $cart
 * @return array
 */
function fn_sd_myparcel_nl_get_delivery_date_for_product_groups(array $cart)
{
    $result = [];
    if (empty($cart)) {
        return $result;
    }
    foreach ($cart['product_groups'] as $group_key => $group)  {
        $result[$group_key] = fn_sd_myparcel_nl_get_delivery_date_from_products($group['products']);
    }
    return $result;
}

/**
 * @param array $products
 * @return int
 */
function fn_sd_myparcel_nl_get_delivery_date_from_products(array $products = [])
{
    $result = 0;
    foreach ($products as $product) {
        if (empty($product['extra']['delivery_date'])) {
            continue;
        }
        $result = $product['extra']['delivery_date'];
        break;
    }
    return $result;
}

/**
 * @param array $cart
 * @return bool
 */
function fn_sd_myparcel_nl_destination_country_is_NL($cart)
{
    $result = false;

    if (!isset($cart['user_data'], $cart['user_data']['s_country'])) {
        return $result;
    }

    $result = $cart['user_data']['s_country'] === TariffZone::NL;

    return $result;
}

/**
 * @param array $shipment_data
 * @param MyParcelConsignmentRepository $consignment
 * @return array
 */
function fn_sd_myparcel_nl_set_consignment_data(array $shipment_data, MyParcelConsignmentRepository $consignment)
{
    $shipment_data['consignment_id'] = $consignment->getMyParcelConsignmentId();
    return $shipment_data;
}

/**
 * @return mixed
 */
function fn_sd_myparcel_nl_get_view()
{
    $result = Tygh::$app['view'];
    if (defined('AJAX_REQUEST')) {
        $result = Tygh::$app['ajax'];
    }

    return $result;
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_set_tracking_info(array $params)
{
    if (!isset($params['request'], $params['request']['shipment_id'])) {
        return false;
    }
    list($shipments) = fn_get_shipments_info(['shipment_id' => $params['request']['shipment_id']]);
    $shipment = reset($shipments);
    $result = fn_sd_myparcel_nl_get_tracking_info($shipment);
    $view = fn_sd_myparcel_nl_get_view();
    $view->assign('tracking_info', $result);

    return true;
}

/**
 * @param $shipment
 * @return mixed
 */
function fn_sd_myparcel_nl_get_tracking_info(array $shipment)
{
    $tracking = new TrackTraceRequest([
        'shipment_ids' => [$shipment['consignment_id']],
        'api_key' => Registry::get('addons.sd_myparcel_nl.api_key'),
    ]);
    $result = $tracking->getShipmentsInfo();
    return $result;
}

/**
 * @param array $cart
 * @return int
 */
function fn_sd_myparcel_nl_get_cart_chosen_myparcel_shipping_id(array $cart)
{
    $result = 0;
    if (!isset($cart['chosen_shipping'])) {
        return $result;
    }
    $is_shipping_myparcel = function ($shipping_id) {
        $shipping = fn_sd_myparcel_nl_get_shipping_info($shipping_id);
        return fn_sd_myparcel_nl_is_myparcel_shipping($shipping);
    };
    foreach ($cart['chosen_shipping'] as $product_group_key => $shipping_id) {
        if ($is_shipping_myparcel($shipping_id)) {
            $result = $shipping_id;
            break;
        }
    }

    return $result;
}

/**
 * @param array $shipping
 * @return int
 */
function fn_sd_myparcel_nl_get_selected_shipment_date(array $order)
{
    $delivery_datetime = fn_sd_myparcel_nl_get_delivery_date_from_products($order['products']);
    return $delivery_datetime;
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_is_myparcel_shipping_data(array $params)
{
    $result = false;
    if (!isset($params['shipment_data'], $params['shipment_data']['shipping_id'], $params['shipment_data']['carrier'])) {
        return $result;
    }
    $result = fn_sd_myparcel_nl_is_myparcel_shipping(fn_sd_myparcel_nl_get_shipping_info($params['shipment_data']['shipping_id']));
    $result = $result && fn_sd_myparcel_nl_is_myparcel_carrier($params['shipment_data']['carrier']);

    return $result;
}

/**
 * @param string $carrier
 * @return bool
 */
function fn_sd_myparcel_nl_is_myparcel_carrier($carrier)
{
    return strtolower(Api::MYPARCEL_SERVICE_CODE) === $carrier;
}

/**
 * @param array $shipment
 * @return bool
 */
function fn_sd_myparcel_nl_is_myparcel_shipment(array $shipment)
{
    $result = false;
    if (!isset($shipment['shipment_id']) && !isset($shipment['shipping_id'])) {
        return $result;
    }
    if (isset($shipment['shipment_id'])) {
        $shipment = fn_sd_myparcel_nl_get_shipment($shipment['shipment_id']);
        if (!isset($shipment['carrier'], $shipment['shipping_id'])) {
            return $result;
        }
    }
    $shipping = fn_sd_myparcel_nl_get_shipping_info(
        $shipment['shipping_id']
    );
    $result = fn_sd_myparcel_nl_is_myparcel_shipping($shipping) && fn_sd_myparcel_nl_is_myparcel_carrier($shipment['carrier']);

    return $result;
}

/**
 * @param int $shipment_id
 * @return array|mixed
 */
function fn_sd_myparcel_nl_get_shipment($shipment_id = 0)
{
    $result = [];
    if (empty($shipment_id)) {
        return $result;
    }
    list($shipments) = fn_get_shipments_info([
        'shipment_id' => $shipment_id,
        'advanced_info' => true,
    ]);
    $result = reset($shipments);

    return $result;
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_print_labels(array $params)
{
    $result = '';
    if (!isset($params['shipment_ids'])) {
        return $result;
    }
    if (empty($params['api_key'])) {
        $params['api_key'] = Registry::get('addons.sd_myparcel_nl.api_key');
    }
    $print_labels = new PrintLabel($params);
    list($result) = $print_labels->printLabels();

    return [$result, $print_labels];
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_create_webhook_subscription(array $params)
{
    if (empty($params['company_id'])) {
        return false;
    }
    $api_key = Registry::get('addons.sd_myparcel_nl.api_key');
    if (empty($api_key) || !fn_sd_myparcel_nl_is_myparcel_shipping(fn_sd_myparcel_nl_get_shipping_info($params['shipping']['shipping_id']))) {
        return false;
    }

    list($base_url, $webhooks_callback_url) = fn_sd_myparcel_nl_get_webhooks_notifications_url();

    $subscription = new Subscription([
        'id' => 0,
        'api_key' => $api_key,
        'hook' => 'shipment_status_change',
        'token' => fn_sd_myparcel_nl_get_token($base_url),
        'url' => $base_url . $webhooks_callback_url,
        'shop_id' => $params['company_id'],
        'shipping_id' => $params['shipping']['shipping_id'],
    ]);
    $subscription->add();
    $response = $subscription->getResponse();
    if (isset($response['errors'])) {
        foreach ($response['errors'] as $error) {
            if (!isset($error['human'])) {
                continue;
            }
            foreach ($error['human'] as $message) {
                fn_set_notification('E', __('addons.sd_myparcel_nl.create_webhook_subscription_error'), $message);
            }
        }
    } else {
        fn_set_notification('N', __('notice'), __('addons.sd_myparcel_nl.notifications.webhooks_subscription_added'));
        $subscription->save();
    }

    return true;
}

/**
 * @return string
 */
function fn_sd_myparcel_nl_get_webhooks_notifications_url()
{
    $base_url = 'https://' . Registry::get('config.https_host') . Registry::get('config.https_path');
    $token = fn_sd_myparcel_nl_get_token($base_url);
    $webhooks_callback_url = '/?dispatch=shipment_status.update&service_token=' . $token;

    return [$base_url, $webhooks_callback_url];
}

/**
 * @param $base_url
 * @return string
 */
function fn_sd_myparcel_nl_get_token($base_url = '')
{
    $token = hash_hmac('sha1', $base_url, Registry::get('addons.sd_myparcel_nl.webhooks_password'));
    return $token;
}

/**
 * @param $params
 * @todo: find subscription_id
 * @return bool
 */
function fn_sd_myparcel_nl_delete_webhook_subscription(array $params)
{
    if (empty($params['company_id']) || !isset($params['subscription_id'], $params['shipping_id'])) {
        return false;
    }
    $api_key = !empty($params['api_key']) ? $params['api_key'] :  Registry::get('addons.sd_myparcel_nl.api_key');
    if (empty($api_key) || !fn_sd_myparcel_nl_is_myparcel_shipping(fn_sd_myparcel_nl_get_shipping_info($params['shipping_id']))) {
        return false;
    }

    $subscription = new Subscription([
        'id' => $params['subscription_id'],
        'api_key' => $api_key,
        'hook' => '',
        'url' => '',
        'shop_id' => $params['company_id'],
    ]);
    $subscription->delete();
    fn_set_notification('N', __('notice'), __('addons.sd_myparcel_nl.notifications.webhooks_subscription_added'));

    return true;
}

/**
 * @param array $params
 * @return bool
 */
function fn_sd_myparcel_nl_is_request_valid(array $params)
{
    $result = false;
    if (!isset($params['service_token'], $params['shipment_id'])) {
        return $result;
    }
    $result = (bool) db_get_field('SELECT COUNT(*) FROM ?:shipments WHERE consignment_id = ?i', $params['shipment_id']);
    $result = $result && (bool) db_get_field(
        'SELECT COUNT(*) FROM ?:myparcel_webhooks_subscriptions WHERE hook = ?s AND token = ?s',
        'shipment_status_change',
        $params['service_token']
    );

    return $result;
}

/**
 * Print shipment labels by order ids
 *
 * @param array $params
 */
function fn_sd_myparsel_nl_print_orders_labels(array $params)
{
    $order_ids = !empty($params['print_label_order_ids']) ? $params['print_label_order_ids'] : [];
    $addon_settings = Registry::get('addons.sd_myparcel_nl');
    $force_notification = fn_get_notification_rules(['notify_user' => $addon_settings['notify_user'] == 'Y']);
    $shipments = [];
    foreach ($order_ids as $order_id) {
        list($order_shipments) = fn_get_shipments_info([
            'order_id' => $order_id,
        ]);
        if (empty($order_shipments)) {
            $order_info = fn_get_order_info($order_id);
            $shipment_data = fn_prepare_shipment_data_for_bulk_print($order_info, $addon_settings);
            if (fn_sd_myparcel_nl_is_myparcel_shipping_data(['shipment_data' => $shipment_data])) {
                $shipment_id = fn_sd_myparcel_nl_update_shipment($shipment_data, 0, 0, true, $force_notification);
            }
            if (!empty($shipment_id)) {
                list($order_shipments) = fn_get_shipments_info([
                    'order_id' => $order_id,
                ]);
            }
        }
        $shipments = array_merge_recursive($shipments, $order_shipments);
    }
    $shipments = array_filter($shipments, 'fn_sd_myparcel_nl_is_myparcel_shipment');
    $results_url = !empty($params['current_url']) ? $params['current_url'] : '';
    if (!empty($shipments)) {
        list($results_url, $print_labels_object) = fn_sd_myparcel_nl_print_labels([
            'shipment_ids' => fn_array_column($shipments, 'shipment_id'),
            'current_url' => $results_url,
            'silent' => true,
        ]);
        $errors = $print_labels_object->getErrors();
        if ($errors) {
            fn_sd_myparcel_nl_process_orders_labels_errors($errors);
        }
    }
    fn_redirect($results_url, true);
}

function fn_prepare_shipment_data_for_bulk_print($order_info, $addon_settings)
{
    if (empty($order_info)) {
        return array();
    }
    $delivery_type = fn_sdmpnl_get_delivery_type_code(['cart' => $order_info]);
    $insurance = 0;
    $only_recipient = $signature = $return = $large_format = 'N';
    if ($addon_settings['package_type'] == Package::TYPE_PACKAGE) {
        if ($addon_settings['only_recipient'] == 'Y' || $delivery_type == Delivery::DELIVERY_MORNING || $delivery_type == Delivery::DELIVERY_NIGHT) {
            $only_recipient = 'Y';
        }
        if ($addon_settings['signature'] == 'Y' || $delivery_type == Delivery::DELIVERY_PICKUP || $delivery_type == Delivery::DELIVERY_PICKUP_EXPRESS) {
            $signature = 'Y';
        }
        $return = $addon_settings['return'];
        $large_format = $addon_settings['large_format'];
        $insurance = $addon_settings['insurance'];
    }
    $shipment_data = [
        'shipping_id' => $order_info['shipping_ids'],
        'order_id' => $order_info['order_id'],
        'carrier' => MYPARCEL_CARRIER_CODE,
        'label_format' => $addon_settings['bulk_print_label_page_format'],
        'label_position' => $addon_settings['label_position'],
        'package_type' => $addon_settings['package_type'],
        'delivery_type' => !empty($delivery_type) ? $delivery_type : Delivery::DELIVERY_STANDARD,
        'only_recipient' => $only_recipient,
        'signature' => $signature,
        'return' => $return,
        'large_format' => $large_format,
        'insurance' => $insurance,
    ];
    return $shipment_data;
}

/**
 * @param array $errors
 * @return bool
 */
function fn_sd_myparcel_nl_process_orders_labels_errors(array $errors = [])
{
    $error_text = '';
    foreach ($errors as $error) {
        if (!empty($error['code'])) {
            $error_text .= fn_sdmpnl_get_error_message($error['code']);
        }
        if (!empty($error['message'])) {
            $error_text .= $error['message'];
        }
        if (!empty($error['account_shipment_ids'])) {
            $order_ids = db_get_fields(
                'SELECT o.order_id FROM ?:orders o
                  LEFT JOIN ?:shipment_items si ON si.order_id = o.order_id
                  LEFT JOIN ?:shipments s ON s.shipment_id = si.shipment_id
                  WHERE s.consignment_id IN (?n)',
                $error['account_shipment_ids']
            );
            $error_text .= __('addons.sd_myparcel_nl.order_ids') . ': ' . implode(',', $order_ids);
        }
        $error_text .= '<br>';
    }
    return fn_set_notification('E', __('error'), $error_text);
}

/**
 * @param $currency_code
 * @return array
 */
function fn_sd_myparcel_nl_get_currency_by_code($currency_code)
{
    $result = [];
    if (empty($currency_code)) {
        return $result;
    }
    $currencies = fn_get_currencies_list();

    $result = isset($currencies[$currency_code]) ? $currencies[$currency_code] : [];

    return $result;
}

/**
 * @param array $cart
 * @return array
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_sd_myparcel_nl_get_selected_delivery_options(array $cart)
{
    $result = [];
    if (empty($cart['delivery_options_update_set'])
        || empty($cart['delivery_options_update_set']['selected_delivery_type'])
    ) {
        return $result;
    }
    $update_set = $cart['delivery_options_update_set'];
    $type = $update_set['selected_delivery_type'];
    if ($type == 'delivery') {
        if (!isset($update_set['delivery_date'])) {
            $update_set['delivery_date'] = array_keys($update_set['delivery'])[0];
        }
    } elseif ($type == 'pickup') {
        if (!isset($update_set['pickup_location_code'])) {
            $update_set['pickup_location_code'] = array_keys($update_set['pickup'])[0];
        }
    }

    $delivery_options_from_service = fn_sd_myparcel_nl_get_delivery_options([
        'cart'     => $cart,
        'no_cache' => defined('NO_CACHE_MYPARCEL_REQUESTS'),
    ]);
    foreach ($delivery_options_from_service as $group_index => $group) {
        if (empty($group['data'])) {
            continue;
        }
        $options = $group['data'];
        $selected_options = array_filter($options[$type], function ($item) use ($type, $update_set) {
            if ($type == 'delivery') {
                return $item['date'] == $update_set['delivery_date'];

            } elseif ($type == 'pickup') {
                return $item['location_code'] == $update_set['pickup_location_code'];
            }
        });
        $result[$group_index] = reset($selected_options);
        if (isset($result[$group_index]['time']) && is_array($result[$group_index]['time'])) {
            if ($type == 'delivery') {
                if (count($result[$group_index]['time']) <= 1) {
                    $filtered_times = $result[$group_index]['time'];

                } else {
                    $filtered_times = array_filter($result[$group_index]['time'], function ($item) use ($update_set) {
                        if (!isset($update_set['delivery'][$update_set['delivery_date']])) {
                            $update_set['delivery'][$update_set['delivery_date']] = Delivery::DELIVERY_STANDARD;
                        }
                        return $item['type'] == $update_set['delivery'][$update_set['delivery_date']];
                    });
                }

            } elseif ($type == 'pickup') {
                if (count($result[$group_index]['time']) <= 1) {
                    $filtered_times = $result[$group_index]['time'];

                } else {
                    $filtered_times = array_filter($result[$group_index]['time'], function ($item) use ($update_set) {
                        if (!isset($update_set['pickup'][$update_set['pickup_location_code']])) {
                            $update_set['pickup'][$update_set['pickup_location_code']] = Delivery::DELIVERY_STANDARD;
                        }
                        return $item['type'] == $update_set['pickup'][$update_set['pickup_location_code']];
                    });
                }
            }

        } else {
            $filtered_times = [];
        }

        $result[$group_index]['time'] = reset($filtered_times);
        $result[$group_index]['time']['checked'] = true;
        $result[$group_index]['type'] = $type;
    }

    return $result;
}

/**
 * @param array $params
 * @return array
 */
function fn_sdmpnl_get_selected_delivery_options_from_db(array $params)
{
    $result = [];
    if (empty($params['order_id'])) {
        return $result;
    }
    $result_json = db_get_field(
        'SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s',
        $params['order_id'],
        DELIVERY_OPTIONS_DATA_TYPE
    );
    if (!empty($result_json)) {
        $result = (array) json_decode($result_json, true);
    }

    return $result;
}



/**
 * @param array $params
 * @return array
 */
function fn_sdmpnl_get_selected_delivery_options(array $params)
{
    $result = [];
    if (empty($params['cart']['shipping']) && empty($params['order_id'])) {
        return $result;
    }
    if (!empty($params['order_id'])) {
        $result = fn_sdmpnl_get_selected_delivery_options_from_db($params);
    }

    $params['cart']['shipping'][0]['delivery_options'] = !empty($params['cart']['shipping'][0]['delivery_options'])
        ? $params['cart']['shipping'][0]['delivery_options']
        : $result;

    $delivery_datetime = fn_sd_myparcel_nl_get_delivery_date($params);
    $result = [
        'delivery_datetime' => $delivery_datetime['date']
            . (!empty($delivery_datetime['start']) ? ' ' . date('H:i', strtotime($delivery_datetime['start'])) : '')
            . (!empty($delivery_datetime['end']) ? ' - ' . date('H:i', strtotime($delivery_datetime['end'])) : ''),
        'delivery_type'     => fn_sdmpnl_get_delivery_type($params),
        'price_comment'     => fn_sdmpnl_get_price_comment($params),
        'pickup_address'    => fn_sdmpnl_get_pickup_address($params),
    ];

    return $result;
}

/**
 * @param $params
 * @return string
 */
function fn_sdmpnl_get_delivery_type($params)
{
    $result = '';
    if (!isset($params['cart']['shipping'][0]['delivery_options']['time']['type'])) {
        return $result;
    }
    $result = __(array_search(fn_sdmpnl_get_delivery_type_code($params), Delivery::getAllTypes()));

    return $result;
}

/**
 * @param $params
 * @return string
 */
function fn_sdmpnl_get_delivery_type_code($params)
{
    $result = '';
    if (!isset($params['cart']['shipping'][0]['delivery_options']['time']['type'])) {
        return $result;
    }
    $result = $params['cart']['shipping'][0]['delivery_options']['time']['type'];

    return $result;
}

/**
 * @param $params
 * @return string
 */
function fn_sdmpnl_get_price_comment($params)
{
    $result = '';
    if (!empty($params['cart']['shipping'][0]['delivery_options']['time']['price_comment'])) {
        $result = $params['cart']['shipping'][0]['delivery_options']['time']['price_comment'];

    } elseif (!empty($params['cart']['shipping'][0]['delivery_options']['price_comment'])) {
        $result = $params['cart']['shipping'][0]['delivery_options']['price_comment'];
    }

    return $result;
}

/**
 * @param $params
 * @return string
 */
function fn_sdmpnl_get_pickup_address($params)
{
    $result = '';
    if (!isset($params['cart']['shipping'][0]['delivery_options']['city'])) {
        return $result;
    }
    $options = $params['cart']['shipping'][0]['delivery_options'];
    $result = $options['city'];
    if (!empty($options['location'])) {
        $result .= ' (' . $options['location'] . ')';
    }
    if (!empty($options['street'])) {
        $result .= '<br>' . $options['street'];
    }
    if (!empty($options['number'])) {
        $result .= ', ' . $options['number'];
    }
    if (!empty($options['phone_number'])) {
        $result .= '<br>' . $options['phone_number'];
    }

    return $result;
}

/**
 * @param array $params
 * @return array
 */
function fn_sdmpnl_update_shipment_and_save_label(array $params)
{
    $shipment_data = isset($params['shipment_data']) ? $params['shipment_data'] : [];
    $order_info    = isset($params['order_info']) ? $params['order_info'] : [];
    $shipment_id   = isset($params['shipment_id']) ? $params['shipment_id'] : 0;
    $group_key     = isset($params['group_key']) ? $params['group_key'] : 0;
    Registry::set('skip_customer_notification', false);
    if (empty($order_info['product_groups'][0]) || !fn_sd_myparcel_nl_is_myparcel_shipment($shipment_data)) {
        return false;
    }
    if (isset($order_info['shipping'][$group_key]['delivery_options']['time']['type']) ) {
        $order_info['delivery_type'] = $order_info['shipping'][$group_key]['delivery_options']['time']['type'];
    }
    if (isset($shipment_data['delivery_type'])) {
        $order_info['delivery_type'] = $shipment_data['delivery_type'];
    }
    $consignment = fn_sd_myparcel_nl_get_consignment($shipment_data, $order_info, $group_key);

    $myParcelCollection = new MyParcelCollection();
    $shipment_data['insurance'] = $consignment->getInsurance();
    $label_position = isset($shipment_data['label_position']) ? $shipment_data['label_position'] : false;
    $label = fn_sd_myparcel_nl_get_label($consignment, $myParcelCollection, $label_position);

    if (!empty($label)) {

        $shipment_data = fn_sd_myparcel_nl_set_tracking_code($shipment_data, $myParcelCollection, $consignment);

        $shipment_data = fn_sd_myparcel_nl_set_consignment_data($shipment_data, $consignment);

        fn_sd_myparcel_nl_update_shipment_data($shipment_data, $shipment_id);

        fn_sd_myparcel_nl_save_label($shipment_id, $label);

    } else {
        Registry::set('skip_customer_notification', true);
    }
    return $shipment_data;
}

function fn_sdmpnl_is_checkout_enable($shipping_id, $shipping_list)
{
    $result = true;
    if (!empty($shipping_id) && !empty($shipping_list)) {
        $_shipping = array();
        foreach ($shipping_list as $shipping) {
            if ($shipping['shipping_id'] == $shipping_id) {
                $_shipping = $shipping;
                break;
            }
        }
        if (!empty($_shipping['service_params']['checkout_enable'])) {
            $result = ($_shipping['service_params']['checkout_enable'] == 'Y');
        }
    }
    return $result;
}

function fn_sdmpnl_get_error_message($error_code, $default_message = '')
{
    $message = !empty($default_message) ? $default_message : __('addons.sd_myparcel_nl.undefined_error');
    if (!empty($error_code)) {
        $error_list = fn_get_schema('sdmn', 'error_list');
        $message = !empty($error_list[$error_code]) ? $error_list[$error_code] : __('addons.sd_myparcel_nl.print_label_error_default', array('[error code]' => $error_code));
    }
    return $message;
}

function fn_sdmpnl_get_group_index($order_info)
{
    if (!empty($order_info['company_id']) && !empty($order_info['product_groups'])) {
        $company_id = $order_info['company_id'];
        foreach ($order_info['product_groups'] as $key => $group) {
            if ($group['company_id'] == $company_id) {
                $group_index = $key;
                break;
            }
        }
    }
    return !empty($group_index) ? $group_index : 0;
}

function fn_sd_myparcelnl_get_price_comment_text($price_comment = '')
{
    $result = $price_comment;
    if ($price_comment) {
        $lang_var = 'addons.sd_myparcelnl.price_comment.' . $price_comment;
        if (__($lang_var) != '_' . $lang_var) {
            $result = __($lang_var);
        }
    }
    return $result;
}
