<div class="control-group">
    <div class="control-label">
        {if $shipment.carrier_info}
            {$shipment.carrier_info.name}
        {else}
            {__("tracking_number")}
        {/if}
    </div>
    <div class="controls">
        <a class="hand cm-tooltip icon-edit cm-combination tracking-number-edit-link" title="{__("edit")}" id="sw_tracking_number_{$shipment_key}"></a>
        {if $shipment.carrier_info.tracking_url}
            <a href="{$shipment.carrier_info.tracking_url nofilter}" target="_blank" id="on_tracking_number_{$shipment_key}_{$order_info.order_id}">{if $shipment.tracking_number}{$shipment.tracking_number}{else}&mdash;{/if}</a>
        {else}
            <span id="on_tracking_number_{$shipment_key}_{$order_info.order_id}">{$shipment.tracking_number}</span>
        {/if}
        {if $shipment.carrier_info.tracking_info.data}{$shipment.carrier_info.tracking_info.data.tracktraces[0].description}{/if}
        <div class="hidden" id="tracking_number_{$shipment_key}">
            <input class="input-small" type="text" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][tracking_number]" size="45" value="{$shipment.tracking_number}" />
            <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
            <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][carrier]" value="{$shipment.carrier}" />
        </div>
    </div>
</div>
