<!-- Overridden by sd_myparcel_nl addon -->
{$form_id = "shipments_form_`$order_info.order_id`"}
<form action="{""|fn_url}" method="post" name="{$form_id}"
      id="{$form_id}" class="form-horizontal form-edit">
    <input type="hidden" name="shipment_data[order_id]" value="{$order_info.order_id}"/>
    <input type="hidden" name="return_url" value=".manage?page={$smarty.request.page|default:1}"/>

    {foreach from=$order_info.shipping key="shipping_id" item="shipping"}
        {if $shipping.packages_info.packages}
            {assign var="has_packages" value=true}
        {/if}
    {/foreach}

    {if $has_packages}
        <div class="tabs cm-j-tabs">
            <ul>
                <li id="tab_general_{$order_info.order_id}" class="cm-js active"><a>{__("general")}</a></li>
                <li id="tab_packages_info_{$order_info.order_id}" class="cm-js"><a>{__("packages")}</a></li>
            </ul>
        </div>
    {/if}

    <div class="cm-tabs-content" id="tabs_content_{$order_info.order_id}">
        <div id="content_tab_general_{$order_info.order_id}">

            <table class="table table-middle">
                <thead>
                <tr>
                    <th>{__("product")}</th>
                    <th width="5%">{__("quantity")}</th>
                </tr>
                </thead>

                {assign var="shipment_products" value=false}

                {foreach from=$order_info.products item="product" key="key"}
                    {if $product.shipment_amount > 0 && (!isset($product.extra.group_key) || $product.extra.group_key == $group_key)}
                        {assign var="shipment_products" value=true}
                        <tr>
                            <td>
                                {assign var=may_display_product_update_link value="products.update"|fn_check_view_permissions}
                                {if $may_display_product_update_link && !$product.deleted_product}<a
                                        href="{"products.update?product_id=`$product.product_id`"|fn_url}">{/if}{$product.product|default:__("deleted_product") nofilter}{if $may_display_product_update_link}</a>{/if}
                                {if $product.product_code}<p>{__("sku")}:&nbsp;{$product.product_code}</p>{/if}
                                {if $product.product_options}
                                    <div class="options-info">{include file="common/options_info.tpl" product_options=$product.product_options}</div>{/if}
                            </td>
                            <td class="center" nowrap="nowrap">
                                {math equation="amount + 1" amount=$product.shipment_amount assign="loop_amount"}
                                {if $loop_amount <= 100}
                                    <select form="{$form_id}" id="shipment_data_{$key}" class="input-small cm-shipments-product"
                                            name="shipment_data[products][{$key}]">
                                        <option value="0">0</option>
                                        {section name=amount start=1 loop=$loop_amount}
                                            <option value="{$smarty.section.amount.index}"
                                                    {if $smarty.section.amount.last}selected="selected"{/if}>{$smarty.section.amount.index}</option>
                                        {/section}
                                    </select>
                                {else}
                                    <input form="{$form_id}" id="shipment_data_{$key}" type="text" class="input-text" size="3"
                                           name="shipment_data[products][{$key}]" value="{$product.shipment_amount}"/>
                                    &nbsp;of&nbsp;{$product.shipment_amount}
                                {/if}
                            </td>
                        </tr>
                    {/if}
                {/foreach}

                {if !$shipment_products}
                    <tr>
                        <td colspan="2">{__("no_products_for_shipment")}</td>
                    </tr>
                {/if}

            </table>

            {include file="common/subheader.tpl" title=__("options")}

            <fieldset>
                <div class="control-group">
                    <label class="control-label"
                           for="shipping_name_{$order_info.order_id}">{__("shipping_method")}</label>
                    <div class="controls">
                        <select form="{$form_id}" name="shipment_data[shipping_id]" id="shipping_name_{$order_info.order_id}">
                            {foreach from=$shippings item="shipping"}
                                <option value="{$shipping.shipping_id}"{if $order_info.shipping_ids == $shipping.shipping_id}{$shipping_info = fn_sd_myparcel_nl_get_shipping_info($shipping.shipping_id)}{$carrier = $shipping_info.service_code} selected{/if}>{$shipping.shipping}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label"
                           for="tracking_number_{$order_info.order_id}">{__("tracking_number")}</label>
                    <div class="controls">
                        <input form="{$form_id}" type="text" name="shipment_data[tracking_number]"
                               id="tracking_number_{$order_info.order_id}" size="10" value=""/>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="carrier_key_{$order_info.order_id}">{__("carrier")}</label>
                    <div class="controls">
                        {include file="common/carriers.tpl" id="carrier_key_{$order_info.order_id}" name="shipment_data[carrier]"}
                    </div>
                </div>

                {hook name="shipments:additional_info"}{/hook}


                <div class="control-group">
                    <label class="control-label" for="shipment_comments_{$order_info.order_id}">{__("comments")}</label>
                    <div class="controls">
                        <textarea form="{$form_id}" id="shipment_comments_{$order_info.order_id}" name="shipment_data[comments]" cols="55"
                                  rows="8" class="span9"></textarea>
                    </div>
                </div>

                {if "orders.update_status"|fn_check_view_permissions}
                    <div class="control-group">
                        <label class="control-label"
                               for="order_status_{$order_info.order_id}">{__("order_status")}</label>
                        <div class="controls">
                            <select form="{$form_id}" id="order_status_{$order_info.order_id}" name="shipment_data[order_status]">
                                <option value="">{__("do_not_change")}</option>
                                {foreach from=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses key="key" item="status"}
                                    <option value="{$key}">{$status}</option>
                                {/foreach}
                            </select>
                            <p class="description">
                                {__("text_order_status_notification")}
                            </p>
                        </div>
                    </div>
                {/if}
            </fieldset>

            <div class="cm-toggle-button">
                <div class="control-group select-field notify-customer">
                    <div class="controls">
                        <label for="shipment_notify_user_{$order_info.order_id}" class="checkbox">
                            <input form="{$form_id}" type="checkbox" name="notify_user" id="shipment_notify_user_{$order_info.order_id}"
                                   value="Y"/>
                            {__("send_shipment_notification_to_customer")}</label>
                    </div>
                </div>
            </div>
        </div>

        {if $has_packages}
            <div id="content_tab_packages_info_{$order_info.order_id}">
                <span class="packages-info">{__("text_shipping_packages_info")}</span>
                {assign var="package_num" value="1"}

                {foreach from=$order_info.shipping key="shipping_id" item="shipping"}
                    {foreach from=$shipping.packages_info.packages key="package_id" item="package"}
                        {assign var="allowed" value=true}

                        {capture name="package_container"}
                            <div class="package-container">
                                 <script type="text/javascript">
                                    packages['package_{$shipping_id}{$package_id}'] = [];
                                </script>
                                <h3>
                                    {__("package")} {$package_num} {if $package.shipping_params}({$package.shipping_params.box_length} x {$package.shipping_params.box_width} x {$package.shipping_params.box_height}){/if}
                                </h3>
                                <ul>
                                    {foreach from=$package.products key="cart_id" item="amount"}
                                        <script type="text/javascript">
                                            packages['package_{$shipping_id}{$package_id}']['{$cart_id}'] = '{$amount}';
                                        </script>
                                    {if $order_info.products.$cart_id}
                                        <li><span>{$amount}</span>
                                            x {$order_info.products.$cart_id.product} {if $order_info.products.$cart_id.product_options}({include file="common/options_info.tpl" product_options=$order_info.products.$cart_id.product_options}){/if}
                                        </li>
                                        {else}
                                        {assign var="allowed" value=false}
                                    {/if}
                                    {/foreach}
                                </ul>
                                <span class="strong">{__("weight")}:</span> {$package.weight}<br/>
                                <span class="strong">{__("shipping_method")}:</span> {$shipping.shipping}
                            </div>
                        {/capture}

                        {if $allowed}
                            {$smarty.capture.package_container nofilter}
                        {/if}

                        {math equation="num + 1" num=$package_num assign="package_num"}
                    {/foreach}
                {/foreach}
            </div>
        {/if}
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[shipments.add]" cancel_action="close" but_target_form="`$form_id`"}
    </div>

</form>

<!-- /Overriden by sd_myparcel_nl addon -->
