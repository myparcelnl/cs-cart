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
use Tygh\Shippings\Services\Myparcel\Rate;
use Tygh\Shippings\Services\Myparcel\Label;
use Tygh\Shippings\Services\Myparcel\Helper;
use Tygh\Shippings\Services\Myparcel\Webhooks\Subscription;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

$request = $_REQUEST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'delete_webhook') {
        if (isset($request['webhook_id'], $request['shipping_id'])) {
            $subscription = Subscription::getFromDb($request['webhook_id']);
            $subscription['api_key'] = Registry::get('addons.sd_myparcel_nl.api_key');
            $webhook_subscription = new Subscription($subscription);
            $result = $webhook_subscription->delete()->getResponse();
            if (!empty($result['errors'])) {
                if (isset($result['message'])) {
                    fn_set_notification('E', __('error'), $result['message']);
                }
            } else {
                fn_set_notification('N', __('notice'), __('addons.sd_myparcel_nl.notifications.webhook_subscription_deleted'));
            }
        }
        return [CONTROLLER_STATUS_OK, 'shippings.configure?shipping_id=' . $request['shipping_id'] . '&module=myparcel&code=myparcel'];
    }
    return;

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $helper = new Helper($request['shipping_id']);
    $delivery_types = $helper->getDeliveryTypes();

    if ($mode === 'configure') {
        if (!empty($request['shipping_id'])) {
            $rate = new Rate();
            $rates = Registry::get('addons.sd_myparcel_nl.rates');
            if (!$rates || array_filter($rates) == []) {
                $rates = $rate->getRates();
                Registry::set('addons.sd_myparcel_nl.rates', $rates);
            }
            $weights = $rate->getWeights();
            $label_formats = Label::getAllFormats();
            $week_days = $helper->getWeekDays();
            $webhooks = $helper->getWebhooksSubscriiptions();
            Tygh::$app['view']->assign([
                'webhooks' => $webhooks,
                'shipping_id' => $request['shipping_id'],
                'rates' => $rates,
                'weights' => $weights,
                'label_formats' => $label_formats,
                'default_label_format' => Label::FORMAT_A4,
                'week_days' => $week_days,
                'delivery_types' => $delivery_types,
            ]);
        }

    } elseif ($mode == 'update') {
        if (empty($request['shipping_id']) || !fn_sd_myparcel_nl_is_myparcel_shipping(fn_sd_myparcel_nl_get_shipping_info($request['shipping_id']))) {
            return [CONTROLLER_STATUS_OK];
        }
        $tabs = Registry::get('navigation.tabs');
        $tabs['delivery_options'] = [
            'title' => __('addons.sd_myparcel_nl.delivery_options'),
            'js' => true,
        ];
        Registry::set('navigation.tabs', $tabs);
        Tygh::$app['view']->assign([
            'delivery_types' => $delivery_types,
        ]);
    }
}
