{if "shipments.add"|fn_check_view_permissions}
    {if $order_info.shipping}
        {foreach from=$order_info.shipping item="shipping" key="shipping_id" name="f_shipp"}
            {if $shipping.need_shipment}
                {capture name="add_new_picker_`$order_info.order_id`"}
                    {include file="addons/sd_myparcel_nl/overrides/views/shipments/components/new_shipment.tpl" group_key=$shipping.group_key}
                {/capture}
                {include file="common/popupbox.tpl"
                    id="add_shipment_`$shipping.group_key`_`$order_info.order_id`"
                    content=$smarty.capture["add_new_picker_`$order_info.order_id`"]
                    text=__("new_shipment")
                    link_text=__("new_shipment")
                    act="link"
                    link_class="pull-`$align`"
                }
            {/if}
        {/foreach}
    {else}
        {foreach from=$order_info.product_groups item="group" key="group_id"}
            {if $group.all_free_shipping}
                {if "shipments.add"|fn_check_view_permissions}
                    {capture name="add_new_picker_`$order_info.order_id`"}
                        {include file="addons/sd_myparcel_nl/overrides/views/shipments/components/new_shipment.tpl" group_key=$group_id}
                    {/capture}
                    {include file="common/popupbox.tpl"
                        id="add_shipment_`$group_id`_`$order_info.order_id`"
                        content=$smarty.capture["add_new_picker_`$order_info.order_id`"]
                        text=__("new_shipment")
                        link_text=__("new_shipment")
                        act="link"
                        link_class="pull-`$align`"
                    }
                {/if}
            {/if}
        {/foreach}
    {/if}
{/if}
