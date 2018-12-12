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

use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;

/**
 * Class Delivery
 * Describes the delivery data type
 *
 * @package Tygh\Shippings\Services\Myparcel\Delivery
 */
class Delivery
{
    use ExceptionsTrait;

    const DELIVERY_MORNING = 1,
        DELIVERY_STANDARD = 2,
        DELIVERY_NIGHT = 3,
        DELIVERY_PICKUP = 4,
        DELIVERY_PICKUP_EXPRESS = 5;

    private $date = TIME,
        $type = self::DELIVERY_STANDARD,
        $remark = '',
        $cooled = false,
        $order_info = [],
        $shipment_data = [];

    public function __construct(array $context = [])
    {
        if (!isset($context['order_info'], $context['shipment_data'])) {
            $this->throwConstructorParamsException();
        }

        $this->order_info = $context['order_info'];
        $delivery_date = fn_sd_myparcel_nl_get_delivery_date(['cart' => $this->order_info]);
        if (empty($this->order_info['delivery_date'])) {
            $this->order_info['delivery_date'] = strtotime($delivery_date['date'] . ' ' . $delivery_date['start']);
        }
        if (!isset($this->order_info['cooled_delivery'])) {
            $this->order_info['cooled_delivery'] = false;
        }
        if (!isset($this->order_info['delivery_remark'])) {
            $this->order_info['delivery_remark'] = '';
        }
        if (!isset($this->order_info['delivery_type'])) {
            $this->order_info['delivery_type'] = self::DELIVERY_STANDARD;
        }
        $this->shipment_data = $context['shipment_data'];
        $this->date = $this->order_info['delivery_date'];
        $this->cooled = $this->order_info['cooled_delivery'];
        $this->remark = $this->order_info['delivery_remark'];
        $this->type = $this->order_info['delivery_type'];
    }

    /**
     * @param bool $cooled
     * @return Delivery
     */
    public function setCooled($cooled)
    {
        $this->cooled = $cooled;
        return $this;
    }

    /**
     * @param string $remark
     * @return Delivery
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }

    /**
     * @param int $type
     * @return Delivery
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public static function getAllTypes()
    {
        return [
            'addons.sd_myparcel_nl.delivery_types.morning' => self::DELIVERY_MORNING,
            'addons.sd_myparcel_nl.delivery_types.standard' => self::DELIVERY_STANDARD,
            'addons.sd_myparcel_nl.delivery_types.night' => self::DELIVERY_NIGHT,
            'addons.sd_myparcel_nl.delivery_types.pickup' => self::DELIVERY_PICKUP,
            'addons.sd_myparcel_nl.delivery_types.express' => self::DELIVERY_PICKUP_EXPRESS,
        ];
    }

    /**
     * @return mixed
     */
    public function getOrderInfo()
    {
        return $this->order_info;
    }

    /**
     * @param mixed $order_info
     * @return Delivery
     */
    public function setOrderInfo($order_info)
    {
        $this->order_info = $order_info;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getShipmentData()
    {
        return $this->shipment_data;
    }

    /**
     * @param mixed $shipment_data
     * @return Delivery
     */
    public function setShipmentData($shipment_data)
    {
        $this->shipment_data = $shipment_data;
        return $this;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     * @return Delivery
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return intval($this->type);
    }

    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @return bool
     */
    public function isCooled()
    {
        return $this->cooled;
    }

}
