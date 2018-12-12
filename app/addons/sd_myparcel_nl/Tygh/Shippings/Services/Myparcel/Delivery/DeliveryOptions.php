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

namespace Tygh\Shippings\Services\Myparcel\Delivery;

use MyParcelNL\Sdk\src\Model\MyParcelRequest;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;
use Tygh\Shippings\Services\Myparcel\Traits\ExtraHeaderTrait;

/**
 * Class DeliveryOptions
 * Receive delivery options from Myparcel API
 *
 * @package Tygh\Shippings\Services\Myparcel\Delivery
 */
class DeliveryOptions
{
    use ExceptionsTrait;
    use ExtraHeaderTrait;

    const CARRIER = 'POSTNL',
        SIX_DROPOFF_DAYS = 6,
        SATURDAY_CUTOFF_TIME = '15:00',
        DATE_FORMAT = 'm-d-Y',
        TIME_FORMAT = 'H:i',
        REQUEST_TYPE_DELIVERY_OPTIONS = 'delivery_options';

    private
        /**
         * @var string
         * Data type: country_code
         * Required: yes
         * The country code for which to fetch the delivery options.
         */
        $country_code = 'NL',

        /**
         * @var string
         * Data type: string
         * Required: yes.
         * The postal code for which to fetch the delivery options.
         */
        $postal_code = '',

        /**
         * @var string
         * Data type: string
         * Required: yes
         * The street number for which to fetch the delivery options.
         */
        $street_number = '',

        /**
         * @var string
         * Data type: carrier_string
         * Required: yes.
         * The carrier for which to fetch the delivery options.
         */
        $carrier = '',

        /**
         * @var string
         * Data type: date
         * Required: no
         * The date on which the package has to be delivered.
         */
        $delivery_date = '',

        /**
         * @var string
         * Data type: time
         * Required: no
         * The time on which a package has to be delivered.
         * Note: This is only an indication of time the package will be delivered on the selected date.
         */
        $delivery_time = '',

        /**
         * Data type: time
         * Required: no
         * This option allows the Merchant to indicate the latest cut-off time before which a consumer order will still be picked,
         * packed and dispatched on the same/first set dropoff day, taking into account the dropoff-delay.
         * Default time is 15h30. For example, if cutoff time is 15h30,
         * Monday is a delivery day and there's no delivery delay;
         * all orders placed Monday before 15h30 will be dropped of at PostNL on that same Monday in time for the Monday collection.
         */
        $cutoff_time = '',

        /**
         * @var int
         * Data type: weekday_digit
         * Required: no
         * This options allows the Merchant to set the days she normally goes to PostNL to hand in her parcels.
         * By default Saturday and Sunday are excluded.
         */
        $dropoff_days = 0,

        /**
         * @var bool
         * Required: no
         * Monday delivery is only possible when the package is delivered before 15.00 on Saturday at the designated PostNL locations.
         * Click https://blog.myparcel.nl/maandagbezorging for more information concerning Monday delivery and the locations.
         * Note: To activate Monday delivery, value 6 must be given with dropoff_days, value 1 must be given by monday_delivery.
         * And on Saturday the cutoff_time must be before 15:00 (14:30 recommended) so that Monday will be shown.
         */
        $monday_delivery = false,

        /**
         * @var
         * Required: no
         * This options allows the Merchant to set the number of days it takes her to pick,
         * pack and hand in her parcels at PostNL when ordered before the cutoff time.
         * By default this is 0 and max is 14.
         */
        $dropoff_delay = 0,

        /**
         * @var int
         * Data type: integer
         * Required: no
         * This options allows the Merchant to set the number of days into the future for which she wants to show her consumers delivery options.
         * For example, if set to 3 in her check-out, a consumer ordering on Monday will see possible delivery options for
         * Tuesday, Wednesday and Thursday (provided there is no drop-off delay, it's before the cut-off time and she goes to PostNL on Mondays).
         * Min is 1. and max. is 14.
         */
        $deliverydays_window = 1,

        /**
         * @var string
         * Data type: delivery_type
         * Required: no
         * This options allows the Merchant to exclude delivery types from the available delivery options.
         * You can specify multiple delivery types by semi-colon separating them.
         * The standard delivery type cannot be excluded.
         */
        $exclude_delivery_types = '',

        /**
         * @var string
         */
        $api_key = '',

        $shipping = [],

        /**
         * @var array
         */
        $context;

    /**
     * @param array $context
     * @throws \Exception
     */
    public function __construct(array $context = [])
    {
        $required_params = ['country_code', 'postal_code', 'street_number'];
        $all_required_params_passed = count(array_intersect(array_keys($context), $required_params)) == count($required_params);
        if (!$all_required_params_passed) {
            $this->throwConstructorParamsException();
        }
        $settings                     = Registry::get('addons.sd_myparcel_nl');
        $this->api_key                = $settings['api_key'];
        $this->context                = $context;
        $this->country_code           = $context['country_code'];
        $this->cutoff_time            = $context['cutoff_time'];
        $this->deliverydays_window    = $context['delivery_days_window'];
        $this->dropoff_days           = $context['dropoff_days'];
        $this->dropoff_delay          = $context['dropoff_delay'];
        $this->exclude_delivery_types = $context['excluded_delivery_types'];
        $this->monday_delivery = $this->getDropoffDays() == self::SIX_DROPOFF_DAYS && (strtotime($this->getCutoffTime()) < strtotime(self::SATURDAY_CUTOFF_TIME));
        $this->postal_code            = $context['postal_code'];
        $this->shipping               = $context['shipping'];
        $this->street_number          = $context['street_number'];
    }

    /**
     * @param array $shipping
     * @return DeliveryOptions
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;
        return $this;
    }

    /**
     * @param string $api_key
     * @return DeliveryOptions
     */
    public function setApiKey($api_key = '')
    {
        if (empty($api_key)) {
            $api_key = Registry::get('addons.sd_myparcel_nl.api_key');
        }
        $this->api_key = $api_key;

        return $this;
    }

    /**
     * @return array
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @param string $postal_code
     * @return DeliveryOptions
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
        return $this;
    }

    /**
     * @param string $street_number
     * @return DeliveryOptions
     */
    public function setStreetNumber($street_number)
    {
        $this->street_number = $street_number;
        return $this;
    }

    /**
     * @param string $cutoff_time
     * @return DeliveryOptions
     */
    public function setCutoffTime($cutoff_time)
    {
        $this->cutoff_time = $cutoff_time;
        return $this;
    }

    /**
     * @param int $dropoff_days
     * @return DeliveryOptions
     */
    public function setDropoffDays($dropoff_days)
    {
        $this->dropoff_days = $dropoff_days;
        return $this;
    }

    /**
     * @param int $deliverydays_window
     * @return DeliveryOptions
     */
    public function setDeliverydaysWindow($deliverydays_window)
    {
        $this->deliverydays_window = $deliverydays_window;
        return $this;
    }

    /**
     * @param int $dropoff_delay
     * @return DeliveryOptions
     */
    public function setDropoffDelay($dropoff_delay)
    {
        $this->dropoff_delay = $dropoff_delay;
        return $this;
    }

    /**
     * @param string $exclude_delivery_type
     * @return DeliveryOptions
     */
    public function setExcludeDeliveryTypes($exclude_delivery_type)
    {
        $this->exclude_delivery_types = $exclude_delivery_type;
        return $this;
    }

    /**
     * @param bool $monday_delivery
     * @return DeliveryOptions
     */
    public function setMondayDelivery($monday_delivery)
    {
        $this->monday_delivery = $monday_delivery;
        return $this;
    }

    /**
     * @return int
     */
    public function getDropoffDays()
    {
        return $this->dropoff_days;
    }

    /**
     * @return string
     */
    public function getCutoffTime()
    {
        return $this->cutoff_time;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     * @return DeliveryOptions
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function getOptionsFromApi()
    {
        $query_data = [
            'carrier' => fn_strtolower(self::CARRIER),
        ];
        if ($this->getCountryCode()) {
            $query_data['cc'] = $this->getCountryCode();
        }
        if ($this->getPostalCode()) {
            $query_data['postal_code'] = $this->getPostalCode();
        }
        if ($this->getStreetNumber()) {
            $query_data['number'] = $this->getStreetNumber();
        }
        if ($this->getCutoffTime()) {
            $query_data['cutoff_time'] = $this->getCutoffTime() . ':00';
        }
        if ($this->getDropoffDays()) {
            $query_data['dropoff_days'] = implode(';', $this->getDropoffDays());
        }
        if ($this->getDropoffDelay()) {
            $query_data['dropoff_delay'] = $this->getDropoffDelay();
        }
        if ($this->getExcludeDeliveryTypes()) {
            $query_data['exclude_delivery_type'] = implode(';', $this->getExcludeDeliveryTypes());
        }
        if ($this->getDeliverydaysWindow()) {
            $query_data['deliverydays_window'] = $this->getDeliverydaysWindow();
        }

        ksort($query_data);
        $key = fn_crc32(json_encode($query_data));
        $cache_name = 'myparcel_get_options_from_api';

        $cache_key = $cache_name . '.' . $key;
        $cache_ttl = 30 * 60;
        Registry::registerCache($cache_name, $cache_ttl, Registry::cacheLevel('time'), true);

        $response = (array) Registry::get($cache_key);
        if (empty($response)) {
            // https://api.myparcel.nl/delivery_options?cc=NL&postal_code=2132WT&number=66&carrier=postnl;
            $url = MyParcelRequest::REQUEST_URL . '/' . self::REQUEST_TYPE_DELIVERY_OPTIONS;
            $response = json_decode(Http::get($url, $query_data, $this->getExtraHeaders()), true);
            Registry::set($cache_key, $response);
        }

        $result = $response;

        if (isset($response['data']['pickup']) && is_array($response['data']['pickup'])) {
            $pickup = array_map(function ($item) {
                if (!isset($item['time']) || !is_array($item['time'])) {
                    return $item;
                }
                $item['time'] = array_values(fn_sort_array_by_key($item['time'], 'start'));
                return $item;
            }, $response['data']['pickup']);
            $result['data']['pickup'] = $pickup;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @param string $country_code
     * @return DeliveryOptions
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @return string
     */
    public function getStreetNumber()
    {
        return $this->street_number;
    }

    /**
     * @return int
     */
    public function getDropoffDelay()
    {
        return $this->dropoff_delay;
    }

    /**
     * @return string
     */
    public function getExcludeDeliveryTypes()
    {
        return $this->exclude_delivery_types;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @return string
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param string $carrier
     * @return DeliveryOptions
     */
    public function setCarrier($carrier = self::CARRIER)
    {
        $this->carrier = $carrier;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->delivery_date;
    }

    /**
     * @param string $delivery_date
     * @return DeliveryOptions
     */
    public function setDeliveryDate($delivery_date)
    {
        $this->delivery_date = date(self::DATE_FORMAT, strtotime($delivery_date));
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * @param string $delivery_time
     * @return DeliveryOptions
     */
    public function setDeliveryTime($delivery_time)
    {
        $this->delivery_time = date(self::TIME_FORMAT, strtotime($delivery_time));
        return $this;
    }

    /**
     * @return bool
     */
    public function isMondayDelivery()
    {
        return $this->monday_delivery;
    }

    /**
     * @return int
     */
    public function getDeliverydaysWindow()
    {
        return $this->deliverydays_window;
    }
}
