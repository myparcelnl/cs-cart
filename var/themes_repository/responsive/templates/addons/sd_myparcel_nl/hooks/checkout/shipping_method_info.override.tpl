<!-- Overridden by the sd_myparcel_nl add-on -->
{hook name="checkout:shipping_method_info"}
    <div class="cm-reload" id="selected_shipping_info">
    {$shipping = $product_groups[$group_key].shippings[$shipping_id]}
    {$shipping.shipping}
    {if $shipping.delivery_options.type == "pickup"}
        {include file="addons/sd_myparcel_nl/components/pickup_info.tpl" location=$shipping.delivery_options no_tooltip=true display_pickup_at_time=true}
    {else}
        {include file="addons/sd_myparcel_nl/components/delivery_info.tpl" interval=$shipping.delivery_options}
    {/if}
    <!--selected_shipping_info--></div>
{/hook}
<!-- /Overridden by the sd_myparcel_nl add-on -->
