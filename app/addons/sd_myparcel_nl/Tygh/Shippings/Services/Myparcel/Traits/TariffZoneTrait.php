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

namespace Tygh\Shippings\Services\Myparcel\Traits;

use Tygh\Shippings\Services\Myparcel\Rate;
use Tygh\Shippings\Services\Myparcel\TariffZone;

/**
 * Trait TariffZoneTrait
 * Contained getTariffZone from package or order info method
 *
 * @package Tygh\Shippings\Services\Myparcel\Traits
 */
trait TariffZoneTrait
{
    public function getTariffZone()
    {
        $country = isset($this->package) ? $this->package->getCountryCode() : $this->order_info['s_country'];
        $tariff_zones = (new Rate())->getDestinations();
        $result = TariffZone::World;
        foreach ($tariff_zones as $zone_name => $countries) {
            if (in_array($country, $countries['countries'])) {
                $result = $zone_name;
                break;
            }
        }

        return $result;
    }

}
