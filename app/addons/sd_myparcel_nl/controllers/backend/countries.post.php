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

use Tygh\Shippings\Services\Myparcel\Rate;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return;
}

if ($mode === 'manage') {
    $rate = new Rate();
    $tariff_zones_full_info = $rate->getDestinations();
    $tariff_zones = array_combine(array_keys($tariff_zones_full_info), array_keys($tariff_zones_full_info));

    Tygh::$app['view']->assign([
        'tariff_zones' => $tariff_zones,
    ]);
}
