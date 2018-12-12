{if $is_myparcel_shipment !== false}
    <input type="hidden" value="{$order_info.order_id}" id="current_order_id">
    <input type="hidden" value="{$package_type_package}" id="current_package_type_package">
    <input type="hidden" value="{$is_myparcel_shipment}" id="current_is_myparcel_shipment">
    {* Label format *}
    <div class="control-group addons-sd_myparcel_nl-shipment-control label-page-format">
        <label class="control-label" for="myparcel_nl_label_format_{$order_info.order_id}">{__("addons.sd_myparcel_nl.label_page_format")}:</label>
        <div class="controls">
            <select {if $form_id}form="{$form_id}"{/if} id="myparcel_nl_label_format_{$order_info.order_id}" name="shipment_data[label_format]" value="{$shipment.label_format}" class="input-mini">

                {$shipment_label_format = $shipment.label_format|default:$default_label_format}
                {foreach from=$label_formats item="label_format" key="label_format_description"}
                    <option value="{$label_format}"{if $shipment_label_format == $label_format}selected="selected"{/if}>{$label_format_description}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {* Label positions *}
    <div class="control-group addons-sd_myparcel_nl-shipment-control label-positions">
        <label class="control-label" for="myparcel_nl_label_positions_{$order_info.order_id}">{__("addons.sd_myparcel_nl.label_position")}:</label>
        <div class="controls">
            {$shipment_label_position = $shipment.label_position|default:$default_label_position}
            <select {if $form_id}form="{$form_id}"{/if} id="myparcel_nl_label_positions_{$order_info.order_id}" name="shipment_data[label_position]" class="input-medium">
                {foreach from=$label_positions item="label_position" key="position_description"}
                    <option value="{$label_position}"{if $label_position == $shipment_label_position}selected="selected"{/if}>
                        {__("addons.sd_myparcel_nl.label_positions.`$position_description`")}
                    </option>
                {/foreach}
            </select>
        </div>
    </div>

    {* Package type *}
    <div class="control-group addons-sd_myparcel_nl-shipment-control">
        <label class="control-label" for="myparcel_nl_package_type_{$order_info.order_id}">{__("addons.sd_myparcel_nl.package_type")}:</label>
        <div class="controls">
            <select {if $form_id}form="{$form_id}"{/if} id="myparcel_nl_package_type_{$order_info.order_id}" name="shipment_data[package_type]" value="{$shipment.package_type}" class="input-medium">
                {$shipment_package_type = $shipment.package_type|default:$default_package_type}
                {foreach from=$package_types item="package_type" key="type_description"}
                    <option value="{$package_type}"{if $shipment_package_type == $package_type}selected="selected"{/if}>{__($type_description)}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {* Delivery type *}
    {$shipment_delivery_type = fn_sdmpnl_get_delivery_type_code(['cart' => $order_info])|default:$default_delivery_type}
    <input type="hidden" name="shipment_data[delivery_type]" value="{$shipment_delivery_type}" {if $form_id}form="{$form_id}"{/if}>

    {* Options *}
    {*

    only_recipient
    Deliver the package only at address of the intended recipient. This option is required for Morning and Evening delivery types.

    signature
    Recipient must sign for the package. This option is required for Pickup and Pickup express delivery types.

    return
    Return the package to the sender when the recipient is not home.

    large_format
    This option must be specified if the dimensions of the package are between 100 x 70 x 50 and 175 x 78 x 58 cm. If the scanned dimensions from the carrier indicate that this package is large format and it has not been specified then it will be added to the shipment in the billing process. This option is also available for EU shipments.

    insurance
    This option allows a shipment to be insured up to certain amount. Only package type 1 (package) shipments can be insured. NL shipments can be insured for 5000,- euros. EU shipments must be insured for 500,- euros. Global shipments must be insured for 200,- euros. The following shipment options are mandatory when insuring an NL shipment: only_recipient and signature. *}


    <div class="fieldset">
        {* Only recipient *}
        <div class="control-group select-field addons-sd_myparcel_nl-shipment-control">
            <label for="only_recipient_{$order_info.order_id}" class="control-label">{__("addons.sd_myparcel_nl.shipment_options.only_recipient")}:</label>
            <div class="controls">
                <input {if $form_id}form="{$form_id}"{/if} type="hidden" name="shipment_data[only_recipient]" value="N">
                {$shipment_only_recipient = $shipment.only_recipient|default:$addons.sd_myparcel_nl.only_recipient}
                <input {if $form_id}form="{$form_id}"{/if} type="checkbox" name="shipment_data[only_recipient]" id="only_recipient_{$order_info.order_id}" value="Y"{if $shipment_only_recipient == 'Y'} checked="true"{/if}>
            </div>
        </div>

        {* Signature *}
        <div class="control-group select-field addons-sd_myparcel_nl-shipment-control">
            <label for="signature_{$order_info.order_id}" class="control-label">{__("addons.sd_myparcel_nl.shipment_options.signature")}:</label>
            <div class="controls">
                <input {if $form_id}form="{$form_id}"{/if} type="hidden" name="shipment_data[signature]" value="N">
                {$shipment_signature = $shipment.signature|default:$addons.sd_myparcel_nl.signature}
                <input {if $form_id}form="{$form_id}"{/if} type="checkbox" name="shipment_data[signature]" id="signature_{$order_info.order_id}" value="Y"{if $shipment_signature == 'Y'} checked="true"{/if}>
            </div>
        </div>

        {* Return *}
        <div class="control-group select-field addons-sd_myparcel_nl-shipment-control">
            <label for="return_{$order_info.order_id}" class="control-label">{__("addons.sd_myparcel_nl.shipment_options.return")}:</label>
            <div class="controls">
                <input {if $form_id}form="{$form_id}"{/if} type="hidden" name="shipment_data[return]" value="N">
                {$shipment_return = $shipment.return|default:$addons.sd_myparcel_nl.return}
                <input {if $form_id}form="{$form_id}"{/if} type="checkbox" name="shipment_data[return]" id="return_{$order_info.order_id}" value="Y"{if $shipment_return == 'Y'} checked="true"{/if}>
            </div>
        </div>

        {* Large format *}
        <div class="control-group select-field addons-sd_myparcel_nl-shipment-control">
            <label for="large_format_{$order_info.order_id}" class="control-label">{__("addons.sd_myparcel_nl.shipment_options.large_format")}:</label>
            <div class="controls">
                <input {if $form_id}form="{$form_id}"{/if} type="hidden" name="shipment_data[large_format]" value="N">
                {$shipment_large_format = $shipment.large_format|default:$addons.sd_myparcel_nl.large_format}
                <input {if $form_id}form="{$form_id}"{/if} type="checkbox" name="shipment_data[large_format]" id="large_format_{$order_info.order_id}" value="Y"{if $shipment_large_format == 'Y'} checked="true"{/if}>
            </div>
        </div>

        {* Insurance *}
        <div class="control-group select-field addons-sd_myparcel_nl-shipment-control">
            <label for="insurance_{$order_info.order_id}" class="control-label">{__("addons.sd_myparcel_nl.shipment_options.insurance")}:</label>
            <div class="controls">
                {$shipment_insurance = $shipment.insurance|default:$addons.sd_myparcel_nl.insurance}
                <input {if $form_id}form="{$form_id}"{/if} type="text" name="shipment_data[insurance]" id="insurance_{$order_info.order_id}" value="{$shipment_insurance}" class="input-mini">
            </div>
        </div>
    </div>
    {* /Options *}


    <script>
        //<![CDATA[
        (function (_, $) {
            var orderId                   = '{$order_info.order_id}',
                carrierSelect             = $('#carrier_key_' + orderId),
                controlElements           = $('.addons-sd_myparcel_nl-shipment-control'),
                deliveryTypeMorning       = 1,
                deliveryTypeNight         = 3,
                deliveryTypePickup        = 4,
                deliveryTypePickupExpress = 5,
                isMyParcelShipment        = '{$is_myparcel_shipment}',
                labelPageFormatSelect     = $('#myparcel_nl_label_format_' + orderId),
                labelPageFormatContainer  = controlElements.has(labelPageFormatSelect),
                myparcelCarrier           = '{$smarty.const.MYPARCEL_CARRIER_CODE}',
                onlyRecipientOption       = $('#only_recipient_' + orderId),
                onlyRecipientOptionLabel  = $('label[for="only_recipient_' + orderId + '"]'),
                packageTypeElm            = $('#myparcel_nl_package_type_' + orderId),
                packageTypeContainer      = controlElements.has(packageTypeElm),
                packageTypePackage        = '{$package_type_package}',
                signatureOption           = $('#signature_' + orderId),
                signatureOptionLabel      = $('label[for="signature_' + orderId + '"]'),
                toggleControls = function (context) {
                    if (typeof context !== 'undefined') {
                        orderId                   = $('#current_order_id', context).val();
                        carrierSelect             = $('#carrier_key_' + orderId);
                        isMyParcelShipment        = $('#current_is_myparcel_shipment', context).val();
                        labelPageFormatSelect     = $('#myparcel_nl_label_format_' + orderId);
                        labelPageFormatContainer  = controlElements.has(labelPageFormatSelect);
                        onlyRecipientOption       = $('#only_recipient_' + orderId);
                        onlyRecipientOptionLabel  = $('label[for="only_recipient_' + orderId + '"]');
                        packageTypeElm            = $('#myparcel_nl_package_type_' + orderId);
                        packageTypeContainer      = controlElements.has(packageTypeElm);
                        packageTypePackage        = $('#current_package_type_package', context).val();
                        signatureOption           = $('#signature_' + orderId);
                        signatureOptionLabel      = $('label[for="signature_' + orderId + '"]');
                    }
                    var carrier = (typeof context !== 'undefined' ? $('[name="shipment_data[carrier]"]', context).val() : carrierSelect.val()),
                        isMyParcelCarrierSelected = (carrier === myparcelCarrier) || isMyParcelShipment;
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
                    var carrier = carrierSelect.val(),
                        isMyParcelCarrierSelected = (carrier === myparcelCarrier) || isMyParcelShipment,
                        element = $(event.target),
                        selectedFormat = element.val(),
                        labelPositionSelect = $('#myparcel_nl_label_positions_' + orderId),
                        labelPositionControl = controlElements.has(labelPositionSelect);
                    if (!isMyParcelCarrierSelected || selectedFormat === 'A6') {
                        labelPositionControl.hide();
                        labelPositionSelect.prop('disabled', true);
                    } else {
                        labelPositionControl.show();
                        labelPositionSelect.prop('disabled', false);
                    }
                },
                onDeliveryTypeSelect = function (context) {
                    var element = $('[name="shipment_data[delivery_type]"]', context),
                        selectedType = parseInt(element.val(), 10);
                    if ((selectedType === deliveryTypeMorning) || (selectedType === deliveryTypeNight)) {
                        onlyRecipientOptionLabel.addClass('cm-required');
                        onlyRecipientOption.prop('checked', true).attr('checked', 'checked');

                    } else {
                        onlyRecipientOptionLabel.removeClass('cm-required');
                    }
                    if ((selectedType === deliveryTypePickup) || (selectedType === deliveryTypePickupExpress)) {
                        signatureOptionLabel.addClass('cm-required');
                        signatureOption.prop('checked', true).attr('checked', 'checked');

                    } else {
                        signatureOptionLabel.removeClass('cm-required');
                    }
                };

            carrierSelect.bind('change', function () {
                toggleControls();
            }).trigger('change');
            packageTypeElm.bind('change', function () {
                toggleControls();
            }).trigger('change');
            labelPageFormatSelect.bind('change', onLabelFormatChange).trigger('change');
            $.ceEvent('on', 'ce.dialogshow', function (dialog) {
                toggleControls(dialog);
                onDeliveryTypeSelect(dialog);
            });

            onDeliveryTypeSelect();

        })(Tygh, Tygh.$);
        //]]>
    </script>
{/if}
