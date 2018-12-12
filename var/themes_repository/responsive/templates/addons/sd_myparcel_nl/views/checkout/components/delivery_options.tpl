{if $shipping|fn_sd_myparcel_nl_is_myparcel_shipping && ($delivery_options || $pickup_options) && $shipping.service_params.checkout_enable == 'Y'}
    <div class="cm-reload" id="select-delivery-options">
        <div class="delivery-options-wrapper">

            {if $delivery_options}
                {* ------------- Delivery options ------------- *}
                <div class="delivery ty-control-group ty-inline-block">

                    <input type="radio"
                        name="delivery_options[selected_delivery_type]"
                        onclick="Tygh.addons.sd_myparcel_nl.calculateShippingCost();"
                        value="delivery"
                        class="delivery-type"
                        id="input_delivery"
                        data-content-block-id="delivery_form_container"
                        {if $shipping.delivery_options.type == 'delivery' || !$shipping.delivery_options.type} checked{/if}>
                    <label for="input_delivery" class="">{__("addons.sd_myparcel_nl.delivery")}</label>

                    <div id="delivery_form_container" class="delivery-type-content">
                        <div class="delivery-type-content_delivery-options">
                            <select name="delivery_options[delivery_date]">
                                {foreach $delivery_options as $date_index => $date}
                                    <option value="{$date.date}"{if $date.date == $shipping.delivery_options.date} selected{/if}>{$date.date|date_format:"%d-%m"}</option>
                                {/foreach}
                            </select>
                            {foreach $delivery_options as $date_index => $date}
                                <div class="delivery-type-content_delivery-options_timeframe{if $shipping.delivery_options.date != $date.date} hidden{/if}"
                                     data-selected-date="{$date.date}">
                                    {foreach $date.time as $interval_index => $interval}
                                        {$currency = fn_sd_myparcel_nl_get_currency_by_code($interval.price.currency)}
                                        <div>
                                            {$amount = $interval.price.amount / 100}
                                            <input type="radio"
                                                   name="delivery_options[delivery][{$date.date}]"
                                                   value="{$interval.type}"
                                                   class="select-variant{if count($date.time) == 1} hidden{/if}"
                                                   onclick="Tygh.addons.sd_myparcel_nl.setDeliveryType('delivery');Tygh.addons.sd_myparcel_nl.calculateShippingCost();"
                                                   data-is-min-amount="{$interval.is_min_amount}"
                                                   {if ($date.date == $shipping.delivery_options.date && $shipping.delivery_options.time.type == $interval.type) || (!$shipping.delivery_options.time.type && $interval.is_min_amount) && $cart.selected_delivery_type == 'delivery'}checked="checked"{/if}
                                            >&nbsp;{$interval.start|date_format:$settings.Appearance.time_format}-{$interval.end|date_format:$settings.Appearance.time_format}&nbsp;{$interval.price_comment|fn_sd_myparcelnl_get_price_comment_text}{if $amount > 0}&nbsp;+&nbsp;{include file="common/price.tpl" value=fn_format_price_by_currency($amount, $interval.price.currency, $smarty.const.CART_PRIMARY_CURRENCY)}{/if}
                                        </div>
                                    {/foreach}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}


            {if $pickup_options}
                {* ------------ Pickup options ------------- *}
                <div class="pickup ty-control-group ty-inline-block">

                    <div class="wrap-input-pickup">
                        <input type="radio"
                           name="delivery_options[selected_delivery_type]"
                           value="pickup"
                           class="delivery-type"
                           id="input_pickup"
                           data-content-block-id="pickup_form_container"
                           {if $shipping.delivery_options.type == 'pickup'}checked{/if}>
                        <label for="input_pickup">{__("addons.sd_myparcel_nl.pickup")}</label>
                    </div>
                    <div id="pickup_form_container" class="delivery-type-content">
                        <div class="delivery-type-content_pickup-options">
                            <select name="delivery_options[pickup_location_code]">
                                {foreach $pickup_options as $location_index => $location}
                                    {assign var="pickup_option_name" value=$location.city|cat:" (":$location.location:" ":$location.postal_code:")"}
                                    <option value="{$location.location_code}"{if $shipping.delivery_options.location_code == $location.location_code}selected{/if}>{$pickup_option_name|truncate:45:"..."}
                                    </option>
                                {/foreach}
                            </select>
                            {foreach $pickup_options as $location_index => $location}
                                {$opening_times_name = "opening_times_`$location_index`"}
                                {capture name = $opening_times_name}
                                    {strip}
                                        <div class="location-tooltip">
                                            <div>{__("addons.sd_myparcel_nl.opening_hours")}</div>
                                            <table>
                                            {foreach from = $location.opening_hours item = "intervals" key = "day"}
                                                <tr>
                                                    <td class="pickup-options-day ty-inline-block">{__("addons.sd_myparcel_nl."|cat:$day)} </td>
                                                    <td>
                                                        <table>
                                                        {foreach from = $intervals item = "interval"}
                                                            <tr>
                                                                <td>
                                                                    <div class="pickup-options-hours ty-inline-block">
                                                                        {$interval}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        {/foreach}
                                                        </table>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                            </table>
                                            <div>{$location.comment}</div>
                                        </div>
                                    {/strip}
                                {/capture}
                                <div class="delivery-type-content_pickup-options_pickup-info{if $shipping.delivery_options.location_code != $location.location_code} hidden{/if}"
                                     data-id="pickup-location-info-{$location.location_code}">

                                    {foreach $location.time as $pickup_time}
                                        {$currency = fn_sd_myparcel_nl_get_currency_by_code($pickup_time.price.currency)}
                                        <div>
                                            <input type="radio"
                                                   name="delivery_options[pickup][{$location.location_code}]"
                                                   value="{$pickup_time.type}"
                                                   class="select-variant"
                                                   onclick="Tygh.addons.sd_myparcel_nl.setDeliveryType('pickup');Tygh.addons.sd_myparcel_nl.calculateShippingCost();"
                                                   data-is-min-amount="{$pickup_time.is_min_amount}"
                                                   {if ($location.location_code == $shipping.delivery_options.location_code && $shipping.delivery_options.time.type == $pickup_time.type) || (!$shipping.delivery_options.time.type && $pickup_time.is_min_amount)}checked="checked"{/if}
                                            >
                                            {$amount = $pickup_time.price.amount / 100}
                                            {__("addons.sd_myparcel_nl.pickup_at_time")}&nbsp;{$pickup_time.start|date_format:$settings.Appearance.time_format}{if $amount > 0}&nbsp;+&nbsp;{include file="common/price.tpl" value=fn_format_price_by_currency($amount, $pickup_time.price.currency, $smarty.const.CART_PRIMARY_CURRENCY)}{/if}
                                        </div>
                                    {/foreach}

                                    {include file="addons/sd_myparcel_nl/components/pickup_info.tpl" location=$location}
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/if}
        </div>

        <script class="cm-ajax-force">
            (function (_, $) {
                _.tr({
                    additional_shipping_options_price_modifier: '{__("addons.sd_myparcel_nl.additional_shipping_options_price_modifier")|escape:"javascript"}',
                    no_deliver_at_selected_datetime: '{__("addons.sd_myparcel_nl.no_deliver_at_selected_datetime")|escape:"javascript"}'
                });
                {literal}
                _.addons = _.addons || {};
                _.addons.sd_myparcel_nl = {
                    calculateShippingCost: function () {
                        var params = [],
                        parents = $('#shipping_rates_list'),
                        selects = $('input[type=radio]:checked, select', parents),
                        url = fn_url('checkout.checkout');
                        $.each(selects, function (id, elm) {
                            if (($(elm).is('input') && $(elm).prop('checked')) || $(elm).is('select')) {
                                params.push({name: elm.name, value: elm.value});
                            }
                        });
                        for (var i in params) {
                            url += '&' + params[i]['name'] + '=' + escape(params[i]['value']);
                        }
                        $.ceAjax('request', url, {
                            result_ids: 'shipping_rates_list,checkout_info_summary_*,checkout_info_order_info_*,selected_shipping_info',
                            method: 'get',
                            full_render: true
                        });
                    },
                    setDeliveryType: function(val) {
                        if (val == 'delivery') {
                            $('#input_delivery').prop('checked', true);
                            $('#input_pickup').prop('checked', false);
                        } else {
                            $('#input_delivery').prop('checked', false);
                            $('#input_pickup').prop('checked', true);
                        }
                    }
                };

                if ($('.delivery-type-content_delivery-options_timeframe:visible').length === 0
                    && $('.delivery-type-content_pickup-options_pickup-info:visible').length === 0)
                {
                    _.addons.sd_myparcel_nl.calculateShippingCost();
                }


                function onDocumentReady() {
                    var pickupSelect = $('#pickup_form_container select[name="delivery_options[pickup_location_code]"]'),
                        deliveryDateSelect = $('#delivery_form_container select[name="delivery_options[delivery_date]"]'),
                        inputPickup = $('#input_pickup'),
                        clickOnDefaultVariant = function (context, selector, activeVariant) {
                            var inputsContainer    = $(context).siblings('div[class^="delivery-type-content"]'),
                                inputElements      = $('input.select-variant', inputsContainer),
                                selectedLocationId = activeVariant.data('id'),
                                selectedDate       = activeVariant.data('selectedDate'),
                                minAmountElement
                            ;

                            inputElements.each(function (idx, elm) {
                                var currentLocationId = $(elm).closest('div[class^="delivery-type-content"]').data('id'),
                                    currentDate       = $(elm).closest('div[class^="delivery-type-content"]').data('selectedDate');
                                if ($(this).data('isMinAmount') === 1  && (((typeof selectedLocationId !== 'undefined') && currentLocationId === selectedLocationId) || ((typeof selectedDate !== 'undefined') && currentDate === selectedDate))) {

                                    minAmountElement = $(elm);
                                    minAmountElement.prop('checked', true);
                                    minAmountElement.attr('checked', 'checked');
                                    _.addons.sd_myparcel_nl.calculateShippingCost();

                                    return false;
                                }
                            });
                        },
                        displayVariantBlock = function (context, selector, activePickupLocation) {
                            clickOnDefaultVariant(context, selector, activePickupLocation);
                        },
                        pickupLocations = $('input[name^="delivery_options[pickup]"]')
                    ;

                    deliveryDateSelect.bind('change', function () {
                        var selector = '.delivery-type-content_delivery-options_timeframe',
                            activeTimeframe = $(selector + '[data-selected-date="' + $(this).val() + '"]');
                        _.addons.sd_myparcel_nl.setDeliveryType('delivery');
                        displayVariantBlock(this, selector, activeTimeframe);
                    });

                    pickupSelect.bind('change', function () {
                        var selector = '.delivery-type-content_pickup-options_pickup-info',
                            activePickupLocation = $(selector + '[data-id="pickup-location-info-' + $(this).val() + '"]');
                        _.addons.sd_myparcel_nl.setDeliveryType('pickup');
                        pickupLocations.each(function () {
                            $(this).prop('checked', false)
                                .removeAttr('checked');
                        });

                        displayVariantBlock(this, selector, activePickupLocation);
                    });

                    inputPickup.on('click', function() {
                        var parent = $(this).parent().parent(),
                            selectbox = $("select[name='delivery_options[pickup_location_code]']", parent),
                            firstValue = $(selectbox).find("option:first").val();
                        $(selectbox).val(firstValue).change();
                    });

                    {/literal}
                }

                $(window.document).unbind('ajaxComplete');
                $(window.document).bind('ajaxComplete', onDocumentReady);
                $(window.document).on('ready', onDocumentReady);

            })(Tygh, Tygh.$);
        </script>

        <!--select-delivery-options--></div>
{/if}
