{$delivery_datetime = $order_info|fn_sd_myparcel_nl_get_delivery_date}
{if $delivery_datetime}
    <p class="strong selected-date-info">
        {__('delivery_time')}: <span class="selected-datetime">{$delivery_datetime|date_format:"`$settings.Appearance.date_format`"}, {$delivery_datetime|date_format:"`$settings.Appearance.time_format`"}</span><span class="delivery-options-comment"></span>
    </p>
    {script src="js/lib/daterangepicker/moment.min.js"}
    <script>
        //<![CDATA[
        (function ($, _) {
            var getChoosenOption = function () {
                var parseJson = function (json, defaultValue) {
                        var result = defaultValue || [];
                        try {
                            result = JSON.parse(json);
                        } catch (e) {
                        }
                        return result;
                    },
                    deliveryOptions = parseJson('{$delivery_options|json_encode nofilter}'),
                    selectedDateTime = moment($('.selected-datetime').text()),
                    selectedDeliveryOptions = deliveryOptions ? deliveryOptions.filter(
                        function (currOptions) {
                            var currDate = moment(currOptions.date);
                            return currDate.isSame(selectedDateTime, 'day');
                        }
                    )[0] : null,
                    fitOption = selectedDeliveryOptions ? selectedDeliveryOptions.time.filter(
                        function (currTimeRange) {
                            var start = currTimeRange.start.split(':'),
                                end = currTimeRange.end.split(':'),
                                startMoment = moment(selectedDeliveryOptions.date).hour(start[0]).minute(start[1]),
                                endMoment = moment(selectedDeliveryOptions.date).hour(end[0]).minute(end[1]);
                            return selectedDateTime.isBetween(startMoment, endMoment);
                        }
                    )[0] : {},
                    additionalPrice = fitOption && fitOption.price ? fitOption.price.amount : 0,
                    modifierText = '',
                    deliveryDateInfo = $('.selected-date-info .delivery-options-comment');
                if (!fitOption) {
                    modifierText = _.tr('no_deliver_at_selected_datetime');
                }
                if (additionalPrice) {
                    modifierText = '(' + fitOption.price_comment + ')';
                }
                deliveryDateInfo.text(modifierText);
            };
            _.tr({
                no_deliver_at_selected_datetime: '{__("addons.sd_myparcel_nl.no_deliver_at_selected_datetime")|escape:"javascript"}'
            });
            getChoosenOption();
        })(Tygh.$, Tygh);
        //]]>
    </script>
{/if}
