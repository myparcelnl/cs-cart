{if $runtime.controller == "addons"}
<script>
    //<![CDATA[
    (function (_, $) {
        $.ceEvent('on', 'ce.commoninit', function(){
            $('#addon_option_sd_myparcel_nl_bulk_print_label_page_format, #addon_option_sd_myparcel_nl_label_position, #addon_option_sd_myparcel_nl_package_type, #addon_option_sd_myparcel_nl_only_recipient, #addon_option_sd_myparcel_nl_signature, #addon_option_sd_myparcel_nl_return, #addon_option_sd_myparcel_nl_large_format, #addon_option_sd_myparcel_nl_insurance').closest('.control-group.sd_myparcel_nl').addClass('addons-sd_myparcel_nl-shipment-control');
            var controlElements           = $('.addons-sd_myparcel_nl-shipment-control'),
                deliveryTypeMorning       = 1,
                deliveryTypeNight         = 3,
                deliveryTypePickup        = 4,
                deliveryTypePickupExpress = 5,
                isMyParcelShipment        = 1,
                isMyParcelCarrierSelected = 1,
                labelPageFormatSelect     = $('#addon_option_sd_myparcel_nl_bulk_print_label_page_format'),
                labelPageFormatContainer  = controlElements.has(labelPageFormatSelect),
                myparcelCarrier           = '{$smarty.const.MYPARCEL_CARRIER_CODE}',
                onlyRecipientOption       = $('#addon_option_sd_myparcel_nl_only_recipient'),
                onlyRecipientOptionLabel  = $('label[for="addon_option_sd_myparcel_nl_only_recipient"]'),
                packageTypeElm            = $('#addon_option_sd_myparcel_nl_package_type'),
                packageTypeContainer      = controlElements.has(packageTypeElm),
                packageTypePackage        = '1',
                signatureOption           = $('#addon_option_sd_myparcel_nl_signature'),
                signatureOptionLabel      = $('label[for="addon_option_sd_myparcel_nl_signature"]'),
                toggleControls = function (context) {
                    if (isMyParcelCarrierSelected && packageTypeElm.val() === packageTypePackage) {
                        controlElements.show();
                        controlElements.each(function () {
                            var elm = $(this);
                            $('input, select', elm).prop('disabled', false);
                        });

                    } else {
                        controlElements.hide();
                        controlElements.each(function () {
                            var elm = $(this);
                            $('input, select', elm).prop('disabled', true);
                        });
                        if (isMyParcelCarrierSelected) {
                            packageTypeContainer.show();
                            $('input, select', packageTypeContainer).prop('disabled', false);
                            labelPageFormatContainer.show();
                            $('input, select', labelPageFormatContainer).prop('disabled', false);
                        }
                    }
                    labelPageFormatSelect.trigger('change');
                },
                onLabelFormatChange = function (event) {
                    var element = $(event.target),
                        selectedFormat = element.val(),
                        labelPositionSelect = $('#addon_option_sd_myparcel_nl_label_position'),
                        labelPositionControl = controlElements.has(labelPositionSelect);
                    if (!isMyParcelCarrierSelected || selectedFormat === 'A6') {
                        labelPositionControl.hide();
                        labelPositionSelect.prop('disabled', true);
                    } else {
                        labelPositionControl.show();
                        labelPositionSelect.prop('disabled', false);
                    }
                };

            packageTypeElm.bind('change', function () {
                toggleControls();
            }).trigger('change');
            labelPageFormatSelect.bind('change', onLabelFormatChange).trigger('change');
            $.ceEvent('on', 'ce.dialogshow', function (dialog) {
                toggleControls(dialog);
            });
        });
    })(Tygh, Tygh.$);
    //]]>
</script>
{/if}