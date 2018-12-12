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

use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;

/**
 * Class Package
 * Describes the package data type
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class Package
{
    use ExceptionsTrait;

    const TYPE_PACKAGE = 1,
        TYPE_MAILBOX_PACKAGE = 2,
        TYPE_LETTER = 3;

    private $order_info = [],
        $shipment_data = [],
        $type = self::TYPE_PACKAGE;

    /**
     * Package constructor.
     */
    public function __construct(array $context = [])
    {
        if (!isset($context['order_info'], $context['shipment_data'])) {
            $this->throwConstructorParamsException();
        }
        $this->order_info = $context['order_info'];
        $this->shipment_data = $context['shipment_data'];
        $this->type = $this->shipment_data['package_type'];
    }

    public static function getAllTypes()
    {
        return [
            'addons.sd_myparcel_nl.package_types.package' => self::TYPE_PACKAGE,
            'addons.sd_myparcel_nl.package_types.mailbox_package' => self::TYPE_MAILBOX_PACKAGE,
            'addons.sd_myparcel_nl.package_types.letter' => self::TYPE_LETTER,
        ];
    }

    public function getOnlyRecipientDeliveryOption()
    {
        return isset($this->shipment_data['only_recipient']) ? $this->shipment_data['only_recipient'] : 'N';
    }

    public function getSignatureDeliveryOption()
    {
        return isset($this->shipment_data['signature']) ? $this->shipment_data['signature'] : 'N';
    }

    public function getReturnDeliveryOption()
    {
        return isset($this->shipment_data['return']) ? $this->shipment_data['return'] : 'N';
    }

    public function getLargeFormatDeliveryOption()
    {
        return isset($this->shipment_data['large_format']) ? $this->shipment_data['large_format'] : 'N';
    }

    public function getInsuranceDeliveryOption()
    {
        return isset($this->shipment_data['insurance']) ? $this->shipment_data['insurance'] : 0;
    }

    public function getCountryCode()
    {
        return $this->order_info['s_country'];
    }

    /**
     * @return int
     */
    public function getType()
    {
        return intval($this->type);
    }

    /**
     * @param int $type
     * @return Package
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderInfo()
    {
        return $this->order_info;
    }

    /**
     * @param array $order_info
     * @return Package
     */
    public function setOrderInfo($order_info)
    {
        $this->order_info = $order_info;
        return $this;
    }

    /**
     * @return array
     */
    public function getShipmentData()
    {
        return $this->shipment_data;
    }

    /**
     * @param array $shipment_data
     * @return Package
     */
    public function setShipmentData($shipment_data)
    {
        $this->shipment_data = $shipment_data;
        return $this;
    }
}
