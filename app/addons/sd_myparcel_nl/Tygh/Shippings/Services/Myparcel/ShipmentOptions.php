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

use Tygh\Shippings\Services\Myparcel\Traits\TariffZoneTrait;
use Tygh\Shippings\Services\Myparcel\Traits\ExceptionsTrait;
use Tygh\Shippings\Services\Myparcel\Delivery\Delivery;

/**
 * Class ShipmentOptions
 * Describes the shipment options data type
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class ShipmentOptions
{
    use TariffZoneTrait;
    use ExceptionsTrait;

    /**
     * The package type. For international shipment only package type 1 (package) is allowed.
     * Data type: package_type
     * Required: yes
     */
    private $package_type,


        /**
         * @var integer
         * Data type: delivery_type
         * Required: Yes if delivery_date has been specified.
         * The delivery type for the package.
         */
        $delivery_type = Delivery::DELIVERY_STANDARD,

        /**
         * @var integer
         * Data type: timestamp
         * Required: Yes if delivery type has been specified.
         * The delivery date time for this shipment.
         */
        $delivery_date = TIME,

        /**
         * @var string
         * Data type: string
         * Required: No.
         * The delivery remark.
         */
        $delivery_remark = '',

        /**
         * @var boolean
         * Data type: boolean
         * Required: No.
         * Deliver the package to the recipient only.
         */
        $only_recipient = false,

        /**
         * @var boolean
         * Data type: boolean
         * Required: No.
         * Package must be signed for.
         */
        $signature = false,

        /**
         * @var boolean
         * Data type: boolean
         * Required: No.
         * Return the package if the recipient is not home.
         */
        $return = false,

        /**
         * @var integer
         * Data type: price
         * Required: No.
         * Insurance price for the package.
         * This option allows a shipment to be insured up to certain amount.
         * Only package type 1 (package) shipments can be insured.
         * NL shipments can be insured for 5000,- euros. EU shipments * must be insured for 500,- euros.
         * Global shipments must be insured for 250,- euros.
         * The following shipment options are mandatory when insuring an NL shipment: only_recipient and * signature.
         */
        $insurance = 0,

        /**
         * @var boolean
         * Data type: boolean
         * Required: No.
         * Large format package.
         */
        $large_format = false,

        /**
         * @var boolean
         * Data type: boolean
         * Required: No.
         */
        $cooled_delivery = false,

        /**
         * @var string
         * Data type: string
         * Required: No.
         * This description will appear on the shipment label.
         * Note: This will be overridden for return shipment by the following: Retour – 3SMYPAMYPAXXXXXX
         */
        $label_description = '',

        /**
         * @var array
         */
        $order_info;

    /**
     * ShipmentOptions constructor.
     * @param array $context
     * @throws \Exception
     */
    public function __construct(array $context = [])
    {
        if (!isset($context['order_info'], $context['insurance'], $context['delivery'], $context['package'])) {
            $this->throwConstructorParamsException();
        }
        $this->order_info = $context['order_info'];
        $this->insurance = $context['insurance'];
        $this->insurance->setAmount($context['package']->getInsuranceDeliveryOption());
        $this->package_type = $context['package']->getType();
        $this->delivery_date = $context['delivery']->getDate();
        $this->delivery_type = $context['delivery']->getType();
        $this->delivery_remark = $context['delivery']->getRemark();
        $this->cooled_delivery = $context['delivery']->isCooled();
        if ($this->package_type == Package::TYPE_PACKAGE) {
            $this->only_recipient = $context['package']->getOnlyRecipientDeliveryOption() === 'Y' || $this->delivery_type === Delivery::DELIVERY_MORNING || $this->delivery_type === Delivery::DELIVERY_NIGHT;
            $this->signature = $context['package']->getSignatureDeliveryOption() === 'Y' || $this->delivery_type === Delivery::DELIVERY_PICKUP || $this->delivery_type === Delivery::DELIVERY_PICKUP_EXPRESS;
            $this->return = $context['package']->getReturnDeliveryOption() === 'Y';
            $this->large_format = $context['package']->getLargeFormatDeliveryOption() === 'Y';
        }
    }

    /**
     * @param boolean $signature
     * @return ShipmentOptions
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @param boolean $only_recipient
     * @return ShipmentOptions
     */
    public function setOnlyRecipient($only_recipient)
    {
        $this->only_recipient = $only_recipient;
        return $this;
    }

    /**
     * @param mixed $return
     * @return ShipmentOptions
     */
    public function setReturn($return)
    {
        $this->return = $return;
        return $this;
    }

    /**
     * @param mixed $insurance
     * @return ShipmentOptions
     */
    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;
        return $this;
    }

    /**
     * @param mixed $large_format
     * @return ShipmentOptions
     */
    public function setLargeFormat($large_format)
    {
        $this->large_format = $large_format;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeliveryDate()
    {
        return $this->delivery_date;
    }

    /**
     * @param mixed $delivery_date
     * @return ShipmentOptions
     */
    public function setDeliveryDate($delivery_date)
    {
        $this->delivery_date = $delivery_date;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeliveryRemark()
    {
        return $this->delivery_remark;
    }

    /**
     * @param mixed $delivery_remark
     * @return ShipmentOptions
     */
    public function setDeliveryRemark($delivery_remark)
    {
        $this->delivery_remark = $delivery_remark;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCooledDelivery()
    {
        return $this->cooled_delivery;
    }

    /**
     * @param mixed $cooled_delivery
     * @return ShipmentOptions
     */
    public function setCooledDelivery($cooled_delivery)
    {
        $this->cooled_delivery = $cooled_delivery;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabelDescription()
    {
        return $this->label_description;
    }

    /**
     * @param mixed $label_description
     * @return ShipmentOptions
     */
    public function setLabelDescription($label_description)
    {
        $this->label_description = $label_description;
        return $this;
    }

    /**
     * Deliver the package only at address of the intended recipient. This option is required for Morning and Evening delivery types.
     *
     * @return bool
     */
    public function getOnlyRecipient()
    {
        return $this->only_recipient;
    }

    /**
     * Recipient must sign for the package. This option is required for Pickup and Pickup express delivery types.
     *
     * @return bool
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Return the package to the sender when the recipient is not home.
     *
     * @return bool
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @return int
     */
    public function getPackageType()
    {
        return $this->package_type;
    }

    /**
     * @param mixed $package_type
     * @return ShipmentOptions
     */
    public function setPackageType($package_type)
    {
        $this->package_type = $package_type;
        return $this;
    }

    /**
     * This option must be specified if the dimensions of the package are between 100 x 70 x 50 and 175 x 78 x 58 cm.
     * If the scanned dimensions from the carrier indicate that this package is large format and it has not been specified
     * then it will be added to the shipment in the billing process. This option is also available for EU shipments.
     *
     * @return bool
     */
    public function getLargeFormat()
    {
        return $this->large_format;
    }

    /**
     * This option allows a shipment to be insured up to certain amount.
     * Only package type 1 (package) shipments can be insured.
     * NL shipments can be insured for 5000,- euros.
     * EU shipments must be insured for 500,- euros.
     * Global shipments must be insured for 200,- euros.
     * The following shipment options are mandatory when insuring an NL shipment: only_recipient and signature.
     *
     * @return int
     */
    public function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * @return mixed
     */
    public function getDeliveryType()
    {
        return $this->delivery_type;
    }

    /**
     * @param mixed $delivery_type
     */
    public function setDeliveryType($delivery_type)
    {
        $this->delivery_type = $delivery_type;
    }
}
