{* Checkout enable *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_checkout_enable">{__("addons.sd_myparcel_nl.checkout_enable")}:</label>
    <div class="controls">
        <select id="myparcel_nl_checkout_enable" name="shipping_data[service_params][checkout_enable]" class="input-mini">
            <option value="Y" {if $shipping.service_params.checkout_enable == "Y"}selected{/if}>{__("yes")}</option>
            <option value="N" {if $shipping.service_params.checkout_enable == "N"}selected{/if}>{__("no")}</option>
        </select>
    </div>
</div>

{* Cutoff time *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_cutoff_time">{__("addons.sd_myparcel_nl.cutoff_time")}:</label>
    <div class="controls">
        <input id="myparcel_nl_cutoff_time" type="text" name="shipping_data[service_params][cutoff_time]" value="{$shipping.service_params.cutoff_time}" placeholder="hh:mm" class="input-mini" />
    </div>
</div>

{* Dropoff days *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_dropoff_days">{__("addons.sd_myparcel_nl.dropoff_days")}:</label>
    <div class="controls">
        <select id="myparcel_nl_dropoff_days" name="shipping_data[service_params][dropoff_days][]"
                multiple
                class="input-medium toll-select">
            {foreach from=$week_days item="week_day" key="week_day_num"}
                <option value="{$week_day_num}"{if $week_day_num|in_array:$shipping.service_params.dropoff_days}selected="true"{/if}>{$week_day}</option>
            {/foreach}
        </select>
    </div>
</div>

{* Dropoff delay *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_dropoff_delay">{__("addons.sd_myparcel_nl.dropoff_delay")}:</label>
    <div class="controls">
        <select id="myparcel_nl_dropoff_delay" name="shipping_data[service_params][dropoff_delay]"
                class="input-mini">
            {foreach from=range(0, 14) item="days"}
                <option value="{$days}"{if $days == $shipping.service_params.dropoff_delay}selected="true"{/if}>{$days}</option>
            {/foreach}
        </select>
    </div>
</div>

{* Deliverydays window *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_deliverydays_window">{__("addons.sd_myparcel_nl.deliverydays_window")}:</label>
    <div class="controls">
        <select id="myparcel_nl_deliverydays_window" name="shipping_data[service_params][deliverydays_window]"
                class="input-mini">
            {foreach from=range(1, 14) item="days"}
                <option value="{$days}"{if $days == $shipping.service_params.deliverydays_window}selected="true"{/if}>{$days}</option>
            {/foreach}
        </select>
    </div>
</div>

{* Excluded delivery types *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_excluded_delivery_types">{__("addons.sd_myparcel_nl.excluded_delivery_types")}:</label>
    <div class="controls">
        <select id="myparcel_nl_excluded_delivery_types" name="shipping_data[service_params][excluded_delivery_types][]"
                multiple
                class="input-medium toll-select">
            {foreach from=$delivery_types key="type_description" item="delivery_type"}
                {if $delivery_type != 2} {* exclude type standart *}
                    <option value="{$delivery_type}" {if $delivery_type|in_array:$shipping.service_params.excluded_delivery_types}selected="true"{/if}>
                        {__($type_description)}
                    </option>
                {/if}
            {/foreach}
        </select>
    </div>
</div>

{* Max height *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_height">{__("addons.sd_myparcel_nl.ship_max_height")}:</label>
    <div class="controls">
        <input id="myparcel_nl_height" type="text" name="shipping_data[service_params][max_height]" size="30" value="{$shipping.service_params.max_height|default:50}" class="input-micro"/>
    </div>
</div>

{* Max width *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_width">{__("addons.sd_myparcel_nl.ship_max_width")}:</label>
    <div class="controls">
        <input id="myparcel_nl_width" type="text" name="shipping_data[service_params][max_width]" value="{$shipping.service_params.max_width|default:50}" class="input-micro"/>
    </div>
</div>

{* Max length *}
<div class="control-group">
    <label class="control-label" for="myparcel_nl_length">{__("addons.sd_myparcel_nl.ship_max_length")}:</label>
    <div class="controls">
        <input id="myparcel_nl_length" type="text" name="shipping_data[service_params][max_length]" value="{$shipping.service_params.max_length|default:100}" class="input-micro"/>
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.sd_myparcel_nl.shipping_rates")}
<table class="table table-middle ">
    <thead>
    <th width="10%">{__("addons.sd_myparcel_nl.destination_zone")}</th>
    {foreach from=$weights item="weight"}
        <th>{$weight}</th>
    {/foreach}
    </thead>
    <tbody>
    {foreach from=$rates key="zone" item="zone_rates"}
        <tr>
            <td>{$zone}</td>
            {foreach from=$zone_rates key="weight_range" item="rate"}
                <td><input type="text" name="shipping_data[service_params][myparcel_nl_rates][{$zone}][{$weight_range}]" value="{$shipping.service_params.myparcel_nl_rates.$zone.$weight_range}" class="input-mini"/></td>

            {/foreach}
        </tr>
    {/foreach}
    </tbody>
</table>
