<!-- Overridden by the sd_myparcel_nl addon [shipping_method.override.tpl] -->
{capture name="original_rate"}
    {if $shipping.original_rate && $shipping.original_rate != $shipping.rate}
        {$_rate = $shipping.original_rate}
    {else}
        {$_rate = $shipping.rate}
    {/if}
    {include file="common/price.tpl" value=$_rate}
{/capture}

<div class="ty-shipping-options__method">
    <input type="radio" class="ty-valign ty-shipping-options__checkbox" id="sh_{$group_key}_{$shipping.shipping_id}" name="shipping_ids[{$group_key}]" value="{$shipping.shipping_id}" onclick="fn_calculate_total_shipping_cost();" {$checked} />
    <div class="ty-shipping-options__group">
        <label for="sh_{$group_key}_{$shipping.shipping_id}" class="ty-valign ty-shipping-options__title">
            <bdi>
                {if $shipping.image}
                    <div class="ty-shipping-options__image">
                        {include file="common/image.tpl" obj_id=$shipping_id images=$shipping.image class="ty-shipping-options__image"}
                    </div>
                {/if}

                {$shipping.shipping} {$delivery_time}
                {$smarty.capture.original_rate nofilter}
           </bdi>
        </label>
    </div>
</div>

{if $shipping.description}
    <div class="ty-checkout__shipping-tips">
        <p>{$shipping.description nofilter}</p>
    </div>
{/if}
{$file_path = "addons/sd_myparcel_nl/views/checkout/components"}
{include file="`$file_path`/delivery_options.tpl"}
<!-- /Overridden by the sd_myparcel_nl addon [shipping_method.override.tpl] -->
