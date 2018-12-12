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

use Tygh\Shippings\Services\Myparcel\Api;

$schema['myparcel_nl'] = array(
    // Info: https://www.postnl.nl/businessportal/en/Images/step-by-step-card-track-trace_tcm16-66060.pdf
    'tracking_url_template' => Api::POSTNL_TRACKING_URL . '?L=[language]&B=[tracking_number]&P=[postal_code]&D=[destination_country]&T=[transaction_type]',
);

return $schema;
