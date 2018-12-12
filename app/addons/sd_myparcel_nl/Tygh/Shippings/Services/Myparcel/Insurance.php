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

/**
 * Class Insurance
 * @package Tygh\Shippings\Services\Myparcel
 * This option allows a shipment to be insured up to certain amount.
 * Only package type 1 (package) shipments can be insured.
 * NL shipments can be insured for 5000,- euros.
 * EU shipments * must be insured for 500,- euros.
 * Global shipments must be insured for 200,- euros.
 * The following shipment options are mandatory when insuring an NL shipment: only_recipient and * signature.
 */
class Insurance
{
    use TariffZoneTrait;
    use ExceptionsTrait;

    const MAX_INSURANCE_AMOUNT = 5000,
          MAX_INSURANCE_AMOUNT_EU_ZONE = 500,
          MAX_INSURANCE_AMOUNT_GLOBAL = 200;

    private $amount = 0,
        $currency = 'EUR',
        $package = null,
        $package_type = Package::TYPE_PACKAGE,
        $order_info = [],
        $shipment_data = [];

    public function __construct(array $context = [])
    {
        if (!isset($context['order_info'], $context['shipment_data'], $context['package'])) {
            $this->throwConstructorParamsException();
        }
        $this->order_info = $context['order_info'];
        $this->shipment_data = $context['shipment_data'];
        $this->package = $context['package'];
        $this->package_type = $this->package->getType();
        $requested_insurance_amount = isset($this->shipment_data['insurance']) ? $this->shipment_data['insurance'] : 0;
        if ($this->package_type === Package::TYPE_PACKAGE) {
            $tariff_zone = $this->getTariffZone();
            if ($tariff_zone === TariffZone::NL) {
                $this->amount = min($requested_insurance_amount, self::MAX_INSURANCE_AMOUNT);
            } elseif (in_array($tariff_zone, [TariffZone::EUR1, TariffZone::EUR2, TariffZone::EUR3])) {
                $this->amount = min($requested_insurance_amount, self::MAX_INSURANCE_AMOUNT_EU_ZONE);
            } else {
                $this->amount = min($requested_insurance_amount, self::MAX_INSURANCE_AMOUNT_GLOBAL);
            }
        }
        $this->currency = fn_get_secondary_currency();
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Insurance
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return int
     */
    public function getPackageType()
    {
        return $this->package_type;
    }

    /**
     * @param int $package_type
     * @return Insurance
     */
    public function setPackageType($package_type)
    {
        $this->package_type = $package_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return Insurance
     */
    public function setAmount($amount)
    {
        $amount = abs($amount);
        if ($amount <= self::MAX_INSURANCE_AMOUNT) {
            $this->amount = $amount;
        } else {
            fn_set_notification(
                'W',
                __('warning'),
                __('addons.sd_myparcel_nl.insurance_corrected', ['[max_value]' => self::MAX_INSURANCE_AMOUNT])
            );
            $this->amount = self::MAX_INSURANCE_AMOUNT;
        }

        return $this;
    }
}
