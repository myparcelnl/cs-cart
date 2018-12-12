{if $location.street && $location.number && $location.postal_code}
    {if $shipping.service_params.delivery_type_price[$pickup_time.type]}
        {$amount = $shipping.service_params.delivery_type_price[$pickup_time.type]}
    {else}
        {$amount = $location.time.price.amount / 100}
    {/if}

    <div class="delivery-type-content_pickup-options_pickup-info_address">
        {if $display_pickup_at_time && $location.time.start}
            {__("addons.sd_myparcel_nl.pickup_at_time")}&nbsp;{$location.time.start|date_format:$settings.Appearance.time_format}{if $amount > 0}&nbsp;+&nbsp;{include file="common/price.tpl" value=fn_format_price_by_currency($amount, $location.time.price.currency, $smarty.const.CART_PRIMARY_CURRENCY)}{/if}
        {/if}
        <div class="delivery-type-content_pickup-options_pickup-info_address_street-number info_street">
            <p class="location-street">{$location.street} {$location.number}</p>
            {if !$no_tooltip}
                <span class="te-title-tooltip cm-tooltip arrow-right" title="{$smarty.capture.$opening_times_name}">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="384" height="448" viewBox="0 0 384 448">
                        <path d="M256 344v-40c0-4.5-3.5-8-8-8h-24V168c0-4.5-3.5-8-8-8h-80c-4.5 0-8 3.5-8 8v40c0 4.5 3.5 8 8 8h24v80h-24c-4.5 0-8 3.5-8 8v40c0 4.5 3.5 8 8 8h112c4.5 0 8-3.5 8-8zm-32-224V80c0-4.5-3.5-8-8-8h-48c-4.5 0-8 3.5-8 8v40c0 4.5 3.5 8 8 8h48c4.5 0 8-3.5 8-8zm160 104c0 106-86 192-192 192S0 330 0 224 86 32 192 32s192 86 192 192z"/>
                    </svg>
                </span>
            {/if}
        </div>
        <div class="delivery-type-content_pickup-options_pickup-info_address_zipcode">
            {$location.postal_code}&nbsp;<b>{$location.city}</b>
        </div>
        {if $location.phone_number}
            <div class="delivery-type-content_pickup-options_pickup-info_address_phone">
                {__("phone")} {$location.phone_number}
            </div>
        {/if}
    </div>
{/if}
