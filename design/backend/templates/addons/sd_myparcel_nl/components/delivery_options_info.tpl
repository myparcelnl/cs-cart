{if $selected_delivery_options || $shipping.module == $smarty.const.MYPARCEL_CARRIER_CODE}
    {$delivery_date = ['cart' => $order_info]|fn_sd_myparcel_nl_get_delivery_date}
    <div class="well orders-right-pane form-horizontal">
        <div class="control-group shift-top">
            <div class="control-label">
                {include file="common/subheader.tpl" title=__("order_info")}
            </div>
        </div>
        <p class="strong">
            {__("delivery_time")}: {$selected_delivery_options.delivery_datetime}
        </p>
        <p class="strong">
            {__("addons.sd_myparcel_nl.delivery_type")}: {$selected_delivery_options.delivery_type}
        </p>
        {if $selected_delivery_options.pickup_address}
            <p class="strong">
                {__("addons.sd_myparcel_nl.pickup_address")}: {$selected_delivery_options.pickup_address nofilter}
            </p>
        {/if}
    </div>
{/if}
