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

namespace Tygh\Shippings\Services\Myparcel;

use \Tygh as Tygh;
use Tygh\Shippings\Services\Myparcel\Delivery\Delivery;
use Tygh\Registry;

/**
 * Class Helper
 * Contains some methods, used from different places
 *
 * @todo move all functions from func.php here
 * @package Tygh\Shippings\Services\Myparcel
 */
class Helper
{
    private $settings = [];

    private $shipping_id = 0;

    private $view = '';

    /**
     * Helper constructor.
     * @param int $shipping_id
     */
    public function __construct($shipping_id = 0)
    {
        $this->shipping_id = $shipping_id;
        $shipping_service_params = fn_get_shipping_params($this->shipping_id);
        if ($shipping_service_params) {
            $this->settings = $shipping_service_params;
        }
        $this->view = Tygh::$app['view'];
    }

    /**
     * @return array
     */
    public function getWeekDays()
    {
        $days = range(1, 6);
        $days[] = 0;
        $result = array_reduce($days, function ($acc, $current) {
            $acc[$current] = __("weekday_$current");
            return $acc;
        }, []);
        return $result;
    }

    /**
     * @return array
     */
    public function getDeliveryTypes()
    {
        $result = Delivery::getAllTypes();
        return $result;
    }

    /**
     * @return string
     */
    public function getCutoffTime()
    {
        return isset($this->settings['cutoff_time']) ? $this->settings['cutoff_time'] : '';
    }

    /**
     * @return array
     */
    public function getDropoffDays()
    {
        return isset($this->settings['dropoff_days']) ? $this->settings['dropoff_days'] : [];
    }

    /**
     * @return int
     */
    public function getDeliveryDaysWindow()
    {
        return isset($this->settings['deliverydays_window']) ? $this->settings['deliverydays_window'] : 0;
    }

    /**
     * @return int
     */
    public function getDropoffDelay()
    {
        return isset($this->settings['dropoff_delay']) ? $this->settings['dropoff_delay'] : 0;
    }

    /**
     * @return array
     */
    public function getExcludedDeliveryTypes()
    {
        return isset($this->settings['excluded_delivery_types']) ? $this->settings['excluded_delivery_types'] : [];
    }

    /**
     * @return Helper
     */
    public function setAllShipmentOptions()
    {
        $params = Registry::get('addons.sd_myparcel_nl');
        $package_types = Package::getAllTypes();
        $delivery_types = Delivery::getAllTypes();
        $label_formats = Label::getAllFormats();
        $label_positions = Label::getAllPositions();

        $this->view->assign([
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

        return $this;
    }

    public function getWebhooksSubscriiptions()
    {
        $result = db_get_array('SELECT * FROM ?:myparcel_webhooks_subscriptions WHERE shipping_id = ?i', $this->shipping_id);
        return $result;
    }
}
