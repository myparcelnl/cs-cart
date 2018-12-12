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

use Tygh\Bootstrap;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Shippings\Services\Myparcel\Delivery\Delivery;
use Tygh\Shippings\Services\Myparcel\Package;
use Tygh\Shippings\Services\Myparcel\Webhooks\ShipmentStatus;

if (!defined('BOOTSTRAP')) {
    exit('Access denied');
}

// This functions created for addon service purposes (install/uninstall events)

/**
 * @return bool
 */
function fn_sd_myparcel_nl_install()
{
    $objects = fn_sd_myparcel_nl_schema();

    foreach ($objects as $object) {
        $service = array(
            'status' => $object['status'],
            'module' => $object['module'],
            'code' => $object['code'],
            'sp_file' => $object['sp_file'],
            'description' => $object['description'],
        );

        $service['service_id'] = db_query('INSERT INTO ?:shipping_services ?e', $service);

        foreach (array_keys(Languages::getAll()) as $service['lang_code']) {
            db_query('INSERT INTO ?:shipping_service_descriptions ?e', $service);
        }
    }

    // Add shipment statuses
    db_query('ALTER TABLE ?:shipments MODIFY COLUMN `status` CHAR(2)');
    db_query('ALTER TABLE ?:statuses MODIFY COLUMN `status` CHAR(2)');
    $shipment_statuses = new ShipmentStatus();
    $shipment_statuses->save();

    db_query('ALTER TABLE ?:countries ADD COLUMN tariff_zone CHAR(5) NOT NULL DEFAULT ?s', 'World');
    fn_sd_myparcel_nl_import_tariff_zones();

    db_query(
        'CREATE TABLE IF NOT EXISTS ?:myparcel_webhooks_subscriptions
        (
            id int default 0 not null,
            hook varchar(32) default "" not null,
            url varchar(255) default "" not null,
            account_id int default 0 not null,
            shop_id int default 0 not null,
            shipping_id int default 0 not null,
            token varchar(255) default "" not null,
            primary key (id, hook, shop_id, shipping_id)
        )'
    );

    db_query('ALTER TABLE ?:shipments ADD COLUMN `delivery_type` TINYINT(1) NOT NULL DEFAULT ?i', Delivery::DELIVERY_STANDARD);
    db_query('ALTER TABLE ?:shipments ADD COLUMN `package_type` TINYINT(1) NOT NULL DEFAULT ?i', Package::TYPE_PACKAGE);
    db_query('ALTER TABLE ?:shipments ADD COLUMN `only_recipient` ENUM(?s, ?s) NOT NULL DEFAULT ?s', 'Y', 'N', 'N');
    db_query('ALTER TABLE ?:shipments ADD COLUMN `signature` ENUM(?s, ?s) NOT NULL DEFAULT ?s', 'Y', 'N', 'N');
    db_query('ALTER TABLE ?:shipments ADD COLUMN `return` ENUM(?s, ?s) NOT NULL DEFAULT ?s', 'Y', 'N', 'N');
    db_query('ALTER TABLE ?:shipments ADD COLUMN `large_format` ENUM(?s, ?s) NOT NULL DEFAULT ?s', 'Y', 'N', 'N');
    db_query('ALTER TABLE ?:shipments ADD COLUMN `insurance` decimal(12, 2) NOT NULL DEFAULT ?s', '0.00');
    db_query('ALTER TABLE ?:shipments ADD COLUMN `consignment_id` INT NOT NULL DEFAULT ?i', 0);
    db_query('ALTER TABLE ?:shipments ADD COLUMN `label_position` INT NOT NULL DEFAULT ?i', 2);
    db_query('ALTER TABLE ?:shipments ADD COLUMN `label_format` VARCHAR(2) NOT NULL DEFAULT ?s', 'A4');

    return true;
}

/**
 * @return bool
 */
function fn_sd_myparcel_nl_import_tariff_zones()
{
    $tariff_zones_csv = Registry::get('config.dir.addons') . 'sd_myparcel_nl/resources/tariff_zones.csv';
    if (!is_file($tariff_zones_csv)) {
        return false;
    }
    $csv_file = fopen($tariff_zones_csv, 'rb');
    $max_line_size = MAX_IMPORT_LINE_SIZE;
    $delimiter = ',';
    $import_schema = fgetcsv($csv_file, $max_line_size, $delimiter);
    $schema_size = sizeof($import_schema);
    $skipped_lines = array();
    $line_it = 1;
    while (($data = fn_fgetcsv($csv_file, $max_line_size, $delimiter)) !== false) {
        $line_it++;
        if (fn_is_empty($data)) {
            continue;
        }
        if (sizeof($data) != $schema_size) {
            $skipped_lines[] = $line_it;
            continue;
        }
        $tariff_zone = array_combine($import_schema, Bootstrap::stripSlashes($data));
        if(!empty($tariff_zone['Code']) && !empty($tariff_zone['Zone'])){
            db_query(
                'UPDATE ?:countries SET ?u WHERE code = ?s',
                ['tariff_zone' => $tariff_zone['Zone']],
                $tariff_zone['Code']
            );
        }

    }

    return true;
}

/**
 * @return bool
 */
function fn_sd_myparcel_nl_uninstall()
{
    $objects = fn_sd_myparcel_nl_schema();

    foreach ($objects as $object) {
        $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', $object['module']);

        if (!empty($service_ids)) {
            db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
            db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
        }
    }

    $shipment_statuses = new ShipmentStatus();
    $shipment_statuses->deleteAll();

    db_query('ALTER TABLE ?:countries DROP COLUMN tariff_zone');

    db_query('DROP TABLE IF EXISTS ?:myparcel_webhooks_subscriptions');

    db_query('ALTER TABLE ?:countries DROP COLUMN tariff_zone');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `delivery_type`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `package_type`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `only_recipient`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `signature`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `return`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `large_format`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `insurance`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `consignment_id`');
    db_query('ALTER TABLE ?:shipments DROP COLUMN `label_position`');
    db_query('ALTER TABLE ?:shipments MODIFY COLUMN `status` CHAR(1)');

    db_query('ALTER TABLE ?:statuses MODIFY COLUMN `status` CHAR(1)');

    return true;
}
