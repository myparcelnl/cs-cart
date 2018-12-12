{$delivery_datetime = $order_info.products|fn_sd_myparcel_nl_get_delivery_date_from_products}
{if $delivery_datetime}
    <tr class="ty-orders-summary__row">
        <td>{__('delivery_time')}:&nbsp;</td>
        <td>{$delivery_datetime|date_format:"`$settings.Appearance.date_format`"}, {$delivery_datetime|date_format:"`$settings.Appearance.time_format`"}</td>
    </tr>
{/if}
