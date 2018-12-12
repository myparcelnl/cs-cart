{if fn_sd_myparcel_nl_is_myparcel_shipping(fn_sd_myparcel_nl_get_shipping_info($shipping.shipping_id))}
<div id="content_delivery_options">
    <input type="hidden" name="shipping_id" value="{$id}">
    <input type="hidden" name="selected_section" value="delivery_options">
    {include file="common/subheader.tpl" title=__("addons.sd_myparcel_nl.delivery")}
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="delivery_morning">{__("addons.sd_myparcel_nl.delivery_types.morning")}</label>
            <div class="controls">
                <input id="delivery_morning" type="text" name="shipping_data[service_params][delivery_type_price][{$delivery_types['addons.sd_myparcel_nl.delivery_types.morning']}]" value="{$shipping.service_params.delivery_type_price[$delivery_types['addons.sd_myparcel_nl.delivery_types.morning']]}" class="input-mini" >
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="delivery_standard">{__("addons.sd_myparcel_nl.delivery_types.standard")}</label>
            <div class="controls">
                <input id="delivery_standard" type="text" name="shipping_data[service_params][delivery_type_price][{$delivery_types['addons.sd_myparcel_nl.delivery_types.standard']}]" value="{$shipping.service_params.delivery_type_price[$delivery_types['addons.sd_myparcel_nl.delivery_types.standard']]}" class="input-mini" >
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="delivery_evening">{__("addons.sd_myparcel_nl.delivery_types.night")}</label>
            <div class="controls">
                <input id="delivery_evening" type="text" name="shipping_data[service_params][delivery_type_price][{$delivery_types['addons.sd_myparcel_nl.delivery_types.night']}]" value="{$shipping.service_params.delivery_type_price[$delivery_types['addons.sd_myparcel_nl.delivery_types.night']]}" class="input-mini" >
            </div>
        </div>
    </fieldset>
    {include file="common/subheader.tpl" title=__("addons.sd_myparcel_nl.pickup")}
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="pickup_express">{__("addons.sd_myparcel_nl.delivery_types.express")}</label>
            <div class="controls">
                <input id="pickup_express" type="text" name="shipping_data[service_params][delivery_type_price][{$delivery_types['addons.sd_myparcel_nl.delivery_types.express']}]" value="{$shipping.service_params.delivery_type_price[$delivery_types['addons.sd_myparcel_nl.delivery_types.express']]}" class="input-mini" >
            </div
        </div>
        <div class="control-group">
            <label class="control-label" for="pickup">{__("addons.sd_myparcel_nl.delivery_types.pickup")}</label>
            <div class="controls">
                <input id="pickup" type="text" name="shipping_data[service_params][delivery_type_price][{$delivery_types['addons.sd_myparcel_nl.delivery_types.pickup']}]" value="{$shipping.service_params.delivery_type_price[$delivery_types['addons.sd_myparcel_nl.delivery_types.pickup']]}" class="input-mini" >
            </div
        </div>
    </fieldset>
<!--content_delivery_options--></div>
{/if}
