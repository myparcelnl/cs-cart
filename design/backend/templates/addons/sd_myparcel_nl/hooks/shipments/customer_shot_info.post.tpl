<h6>{__("addons.sd_myparcel_nl.additional_shipment_info")}</h6>
<p>
    <a href="{$shipment.carrier_info.tracking_url}" target="_blank">{__("addons.sd_myparcel_nl.tracking_link")}</a>
    {if $tracking_info.data}{$tracking_info.data.tracktraces[0].description}{/if}
</p>
{if $shipment.carrier_info.labels}
    {__("addons.sd_myparcel_nl.labels")}:
    {foreach from=$shipment.carrier_info.labels item="label_url" name="labels"}
        <p>{__("addons.sd_myparcel_nl.label")} <a href="{$label_url}" target="_blank">#{$smarty.foreach.labels.index + 1}</a></p>
    {/foreach}
{/if}
{include file="addons/sd_myparcel_nl/components/delivery_options_info.tpl"}
