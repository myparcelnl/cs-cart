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

use Tygh\Shippings\Services\Myparcel\Label;
use Tygh\Shippings\Services\Myparcel\Package;

function fn_settings_variants_addons_sd_myparcel_nl_label_position()
{
    $label_postions = array_flip(Label::getAllPositions());
    foreach ($label_postions as $position => &$decription) {
        $decription = __('addons.sd_myparcel_nl.label_positions.' . $decription);
    }
    return $label_postions;
}

function fn_settings_variants_addons_sd_myparcel_nl_package_type()
{
    $package_types = array_flip(Package::getAllTypes());
    foreach ($package_types as $type => &$decription) {
        $decription = __($decription);
    }
    return $package_types;
}

