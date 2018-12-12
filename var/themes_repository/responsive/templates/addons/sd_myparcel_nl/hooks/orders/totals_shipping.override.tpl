{if $use_shipments}
    <ul>
        {foreach from=$order_info.shipping item="shipping_method"}
            <li>{if $shipping_method.shipping} {$shipping_method.shipping} {else} – {/if}</li>
        {/foreach}
    </ul>
{else}
    {foreach from=$order_info.shipping item="shipping" name="f_shipp"}
        {$shipment = $shipments[$shipping.group_key]}
        {if $shipment.carrier && $shipment.tracking_number}
            {$shipping.shipping}&nbsp;({__("tracking_number")}: <a target="_blank" href="{$shipment.carrier_info.tracking_url nofilter}">{$shipment.tracking_number}</a>)
            {$shipment.carrier_info.info nofilter}
        {elseif $shipment.tracking_number}
            {$shipping.shipping}&nbsp;({__("tracking_number")}: {$shipment.tracking_number})
            {$shipment.carrier_info.info nofilter}
        {elseif $shipment.carrier}
            {$shipping.shipping}&nbsp;({__("carrier")}: {$shipment.carrier_info.name nofilter})
            {$shipment.carrier_info.info nofilter}
        {else}
            {$shipping.shipping}
        {/if}

        {if $shipment.carrier_info.tracking_info.data}{$shipment.carrier_info.tracking_info.data.tracktraces[0].description}{/if}
        {if !$smarty.foreach.f_shipp.last}<br>{/if}
    {/foreach}
{/if}
{if $shipping.delivery_options.type == 'delivery'}
    {include file="addons/sd_myparcel_nl/components/delivery_info.tpl" interval=$shipping.delivery_options}
{else}
    {include file="addons/sd_myparcel_nl/components/pickup_info.tpl" location=$shipping.delivery_options display_pickup_at_time=true no_tooltip=true}
{/if}
