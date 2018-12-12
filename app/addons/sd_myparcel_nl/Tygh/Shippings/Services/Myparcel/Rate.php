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


use Tygh\Registry;

/**
 * Class Rate
 * Use for generate/get postnl tariffs table
 *
 * @package Tygh\Shippings\Services\Myparcel
 */
class Rate
{
    // https://www.postnl.nl/Images/Postal-Rates-sheet-january-2016-PostNL_tcm10-71860.pdf
    private $destination_zones = [
        TariffZone::NL => [
            'countries' => [
                'NL',
            ],
        ],
        TariffZone::EUR1 => [
            'countries' => [
                'BE',
                'DK',
                'DE',
                'FR', //(incl. Corsica and Monaco],
                'IT', //(excluding San Marino and Vatican City],
                'LU',
                'AT',
                'ES', //(including The Balearic Islands, but excluding the Canary Islands],
                'UK', //(excluding Gibraltar, the Channel Islands],
                'SE',
            ],
        ],
        TariffZone::EUR2 => [
            'countries' => [
                'BG',
                'EE',
                'FI',
                'HU',
                'IE',
                'HR',
                'LV',
                'LT',
                'PL',
                'PT', //(incl. the Azores and Madeira],
                'RO',
                'SI',
                'SK',
                'CZ',
                // Next countries from EUR3
                'AL',
                'AD',
                'BA',
                'CY',
                'FO',
                'GI',
                'GR',
                'GL',
                'IS',
                'XK',
                'LI',
                'MK',
                'MT',
                'MD',
                'ME',
                'NO',
                'UA',
                'SM',
                'RS',
                'CS',
                'GB',
                'TR',
                'VA',
                'BY',
                'CH',
            ],
        ],
        TariffZone::World => [

        ],
    ],

    $weights = [
        '0-2kg',
        '2-5kg',
        '5-10kg',
        '10-20kg',
        '20-30kg',
    ],

    $zones_positions = [
        TariffZone::NL => 1,
        TariffZone::EUR1 => 2,
        TariffZone::EUR2 => 3,
        TariffZone::EUR3 => 4,
        TariffZone::World => 5,
    ],

    $rates = [];

    public function __construct()
    {
        list($countries) = fn_get_countries([]);
        foreach ($countries as $country) {
            $destination_zones[$country['tariff_zone']]['countries'][] = $country['code'];
        }
        $this->setDestinations($destination_zones);
    }

    public function getDestinations()
    {
        return $this->destination_zones;
    }

    public function getWeights()
    {
        return $this->weights;
    }

    public function setDestinations(array $zones = [])
    {
        $this->destination_zones = $this->sortByPositions($zones);
        return $this;
    }

    private function sortByPositions(array $zones = [])
    {
        if (empty($zones)) {
            return $zones;
        }
        $zones_positions = array_filter(array_keys($this->zones_positions), function ($zone) use ($zones) {
            return array_key_exists($zone, $zones);
        });
        array_multisort($zones, $zones_positions);

        return $zones;
    }

    public function setWeights(array $weights = [])
    {
        $this->weights = $weights;
        return $this;
    }

    public function setRates(array $rates)
    {
        $this->rates = $rates;
        return $this;
    }

    public function getRates()
    {
        if (empty($this->rates)) {
            $rates = json_decode(Registry::get('addons.sd_myparcel_nl.rates'), true);
        } else {
            $rates = $this->rates;
        }
        if (empty($rates)) {
            $rates = $this->generateEmptyRatesTable();
        }
        return $rates;
    }

    private function generateEmptyRatesTable()
    {
        $result = [];
        foreach (array_keys($this->getDestinations()) as $d_zone) {
            foreach ($this->weights as $weight) {
                $result[$d_zone][$weight] = 0;
            }
        }
        return $result;
    }

    public function saveRates(array $rates = [])
    {
        if (empty($rates)) {
            $rates = $this->getRates();
        }
        Registry::set('addons.sd_myparcel_nl.rates', json_encode($rates));
        return $this;
    }
}
