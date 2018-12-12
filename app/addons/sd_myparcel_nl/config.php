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

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

use Tygh\Registry;

fn_define('LABELS_DIR', Registry::get('config.dir.files') . 'myparcel_labels' . DIRECTORY_SEPARATOR);
fn_define('MAX_IMPORT_LINE_SIZE',65535);
fn_define('MYPARCEL_CARRIER_CODE', 'myparcel');
fn_define('STATUS_TYPE_SHIPMENT', 'S');
fn_define('SD_MYPARCEL_NL_VERSION', '0.1.0');
fn_define('DELIVERY_OPTIONS_DATA_TYPE', 'D');

fn_define('MYPARCEL_BAD_ADDRESS_MARK', 'Shipment validation error Url');