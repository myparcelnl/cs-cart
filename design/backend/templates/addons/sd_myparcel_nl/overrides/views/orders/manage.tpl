<!-- Overridden by the sd_myparcel_nl addon -->
{capture name="mainbox"}
    {if $runtime.mode == "new"}
        <p>{__("text_admin_new_orders")}</p>
    {/if}

    {capture name="sidebar"}
        {hook name="orders:manage_sidebar"}
            {include file="common/saved_search.tpl" dispatch="orders.manage" view_type="orders"}
            {include file="views/orders/components/orders_search_form.tpl" dispatch="orders.manage"}
        {/hook}
    {/capture}
    {$main_form_id = "orders_list_form"}
    <form action="{""|fn_url}" method="post" target="_self" name="{$main_form_id}" id="{$main_form_id}"></form>

    {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

    {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
    {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}

    {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

    {if $incompleted_view}
        {assign var="page_title" value=__("incompleted_orders")}
        {assign var="get_additional_statuses" value=true}
    {else}
        {assign var="page_title" value=__("orders")}
        {assign var="get_additional_statuses" value=false}
    {/if}
    {assign var="order_status_descr" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}
    {assign var="extra_status" value=$config.current_url|escape:"url"}
    {$statuses = []}
    {assign var="order_statuses" value=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}

    {if $orders}
        <table width="100%" class="table table-middle">
            <thead>
            <tr>
                <th  class="left">
                    {include file="common/check_items.tpl" check_statuses=$order_status_descr}
                </th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by == "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by == "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("customer")}{if $search.sort_by == "customer"}{$c_icon nofilter}{/if}</a></th>
                <th><a class="cm-ajax" href="{"`$c_url`&sort_by=phone&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("phone")}{if $search.sort_by == "phone"}{$c_icon nofilter}{/if}</a></th>

                {hook name="orders:manage_header"}{/hook}
                <th>{__('addons.sd_myparcel_nl.shipments')}</th>
                <th>{__('addons.sd_myparcel_nl.shipment_options')}</th>
                <th>{__('addons.sd_myparcel_nl.track_n_trace_status')}</th>
                <th>{include file="buttons/button.tpl" but_text=__("addons.sd_myparcel_nl.print_labels") but_name="dispatch[orders.print_labels]" but_role="action" but_meta="cm-new-window cm-process-items cm-submit nowrap" but_target_form="`$main_form_id`"}</th>
                <th>&nbsp;</th>
                <th width="14%" class="right"><a class="cm-ajax{if $search.sort_by == "total"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=total&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("total")}</a></th>

            </tr>
            </thead>
            {foreach from=$orders item="o"}
                {hook name="orders:order_row"}
                    <tr>
                        <td class="left">
                            <input form="{$main_form_id}" type="checkbox" name="order_ids[]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                        <td>
                            <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="underlined">{__("order")} #{$o.order_id}</a>
                            {if $order_statuses[$o.status].params.appearance_type == "I" && $o.invoice_id}
                                <p class="muted">{__("invoice")} #{$o.invoice_id}</p>
                            {elseif $order_statuses[$o.status].params.appearance_type == "C" && $o.credit_memo_id}
                                <p class="muted">{__("credit_memo")} #{$o.credit_memo_id}</p>
                            {/if}
                            {include file="views/companies/components/company_name.tpl" object=$o}
                        </td>
                        <td>
                            {if "MULTIVENDOR"|fn_allowed_for}
                                {assign var="notify_vendor" value=true}
                            {else}
                                {assign var="notify_vendor" value=false}
                            {/if}

                            {include file="common/select_popup.tpl" suffix="o" order_info=$o id=$o.order_id status=$o.status items_status=$order_status_descr update_controller="orders" notify=true notify_department=true notify_vendor=$notify_vendor status_target_id="orders_total,`$rev`" extra="&return_url=`$extra_status`" statuses=$order_statuses btn_meta="btn btn-info o-status-`$o.status` btn-small"|lower}
                            {if $o.issuer_name}
                                <p class="muted shift-left">{$o.issuer_name}</p>
                            {/if}
                        </td>
                        <td class="nowrap">{$o.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                        <td>
                            {if $o.email}<a href="mailto:{$o.email|escape:url}">@</a> {/if}
                            {if $o.user_id}<a href="{"profiles.update?user_id=`$o.user_id`"|fn_url}">{/if}{$o.lastname} {$o.firstname}{if $o.user_id}</a>{/if}
                            {if $o.company}<p class="muted">{$o.company}</p>{/if}
                        </td>
                        <td>{$o.phone}</td>

                        {hook name="orders:manage_data"}{/hook}

                        {$order_info = $o}
                        {$is_group_shippings = count($order_info.shipping) > 1}
                        {strip}
                            <td>
                                {if $order_info.shipping}
                                    {if !"ULTIMATE:FREE"|fn_allowed_for}
                                        {include file="addons/sd_myparcel_nl/views/orders/create_shipments_forms.tpl"}
                                    {/if}
                                    {foreach from=$order_info.shipping item="shipping" key="shipping_id" name="f_shipp"}
                                        <div class="clearfix">
                                            {if $shipping.need_shipment}
                                                {if $shipping.shipment_keys}
                                                    {assign var="shipment_btn" value=__("new_shipment")}
                                                    {$align="left"}
                                                {else}
                                                    {assign var="shipment_btn" value=__("create_detailed_shipment")}
                                                    {$align="right"}
                                                {/if}
                                                <div class="clearfix">
                                                    {if "ULTIMATE:FREE"|fn_allowed_for}
                                                        {include file="buttons/button.tpl" but_role="action" but_meta="cm-promo-popup pull-{$align}" allow_href=false but_text=$shipment_btn}
                                                    {/if}
                                                </div>
                                            {/if}

                                            {if $is_group_shippings}<hr>{/if}

                                            {if $order_info.shipment_ids}
                                                {if !$is_group_shippings}
                                                    <div class="pull-right">
                                                        <a href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                                                    </div>
                                                {/if}
                                            {/if}
                                        </div>
                                    {/foreach}
                                {/if}
                            </td>

                            <td>
                                {if $order_info.shipping}
                                    {$delivery_datetime = fn_sd_myparcel_nl_get_selected_shipment_date($order_info)}

                                    {$selected_delivery_options = fn_sdmpnl_get_selected_delivery_options(['cart' => $order_info, 'order_id' => $order_info.order_id])}
                                    {if $selected_delivery_options}
                                        {if $selected_delivery_options.delivery_type}
                                            <p>{__("addons.sd_myparcel_nl.delivery_type")}: {$selected_delivery_options.delivery_type}</p>
                                        {/if}
                                        {if $selected_delivery_options.pickup_address}
                                            <p>{$selected_delivery_options.pickup_address nofilter}</p>
                                        {/if}
                                        {if !$selected_delivery_options.pickup_address}
                                            {if $delivery_datetime}
                                                <p>{__('delivery_time')}: {$delivery_datetime|date_format:"`$settings.Appearance.date_format`"}, {$delivery_datetime|date_format:"`$settings.Appearance.time_format`"}</p>
                                            {elseif $selected_delivery_options.delivery_datetime && strtotime($selected_delivery_options.delivery_datetime) !== 0}
                                                <p>{__("delivery_time")}: {$selected_delivery_options.delivery_datetime}</p>
                                            {/if}
                                        {/if}
                                    {/if}
                                {/if}
                            </td>

                            <td>
                                {if $order_info.shipments}
                                    {foreach from=$order_info.shipments item="shipment" key="shipment_key"}
                                        {if $shipment.carrier_info.tracking_url}
                                            <a href="{$shipment.carrier_info.tracking_url nofilter}" target="_blank" id="on_tracking_number_{$shipment_key}_{$order_info.order_id}">{if $shipment.tracking_number}{$shipment.tracking_number}{else}&mdash;{/if}</a>
                                        {else}
                                            <span id="on_tracking_number_{$shipment_key}_{$order_info.order_id}">{$shipment.tracking_number}</span>
                                        {/if}
                                        {if $shipment.carrier_info.tracking_info.data}
                                            &nbsp;{$shipment.carrier_info.tracking_info.data.tracktraces[0].description}
                                        {/if}
                                        <br>
                                        {$shipment = []}
                                    {/foreach}
                                {/if}
                            </td>
                            <td>
                                <input form="{$main_form_id}" type="checkbox" name="print_label_order_ids[]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                            </td>
                        {/strip}

                        <td width="5%" class="center">
                            {capture name="tools_items"}
                                <li>{btn type="list" href="orders.details?order_id=`$o.order_id`" text={__("view")}}</li>
                                {hook name="orders:list_extra_links"}
                                    <li>{btn type="list" href="order_management.edit?order_id=`$o.order_id`" text={__("edit")}}</li>
                                    <li>{btn type="list" href="order_management.edit?order_id=`$o.order_id`&copy=1" text={__("copy")}}</li>
                                {assign var="current_redirect_url" value=$config.current_url|escape:url}
                                    <li>{btn type="list" href="orders.delete?order_id=`$o.order_id`&redirect_url=`$current_redirect_url`" class="cm-confirm" text={__("delete")} method="POST"}</li>
                                {/hook}
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_items}
                            </div>
                        </td>
                        <td class="right">
                            {include file="common/price.tpl" value=$o.total}
                        </td>
                    </tr>
                {/hook}
            {/foreach}
        </table>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

    {if $orders}
        <div class="statistic clearfix" id="orders_total">
            {hook name="orders:statistic_list"}
                <table class="pull-right ">
                    {if $total_pages > 1 && $search.page != "full_list"}
                        <tr>
                            <td>&nbsp;</td>
                            <td width="100px">{__("for_this_page_orders")}:</td>
                        </tr>
                        <tr>
                            <td>{__("gross_total")}:</td>
                            <td>{include file="common/price.tpl" value=$display_totals.gross_total}</td>
                        </tr>
                        {if !$incompleted_view}
                            <tr>
                                <td>{__("totally_paid")}:</td>
                                <td>{include file="common/price.tpl" value=$display_totals.totally_paid}</td>
                            </tr>
                        {/if}
                        <hr />
                        <tr>
                            <td>{__("for_all_found_orders")}:</td>
                        </tr>
                    {/if}
                    <tr>
                        <td class="shift-right">{__("gross_total")}:</td>
                        <td>{include file="common/price.tpl" value=$totals.gross_total}</td>
                    </tr>
                    {hook name="orders:totals_stats"}
                    {if !$incompleted_view}
                        <tr>
                            <td class="shift-right"><h4>{__("totally_paid")}:</h4></td>
                            <td class="price">{include file="common/price.tpl" value=$totals.totally_paid}</td>
                        </tr>
                    {/if}
                    {/hook}
                </table>
            {/hook}
            <!--orders_total--></div>
    {/if}

    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}


    {capture name="adv_buttons"}
        {hook name="orders:manage_tools"}
            {include file="common/tools.tpl" tool_href="order_management.new" prefix="bottom" hide_tools="true" title=__("add_order") icon="icon-plus"}
        {/hook}
    {/capture}

{/capture}

{capture name="incomplete_button"}
    {if $incompleted_view}
        <li>{btn type="list" href="orders.manage" text={__("view_all_orders")}}</li>
    {else}
        <li>{btn type="list" href="orders.manage?skip_view=Y&status=`$smarty.const.STATUS_INCOMPLETED_ORDER`" text={__("incompleted_orders")} form="orders_list_form"}</li>
    {/if}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {if $orders}
            <li>{btn type="list" text={__("bulk_print_invoice")} dispatch="dispatch[orders.bulk_print]" form="orders_list_form" class="cm-new-window"}</li>
            <li>{btn type="list" text={__("bulk_print_pdf")} dispatch="dispatch[orders.bulk_print..pdf]" form="orders_list_form"}</li>
            <li>{btn type="list" text={__("bulk_print_packing_slip")} dispatch="dispatch[orders.packing_slip]" form="orders_list_form" class="cm-new-window"}</li>
            <li>{btn type="list" text={__("view_purchased_products")} dispatch="dispatch[orders.products_range]" form="orders_list_form"}</li>

            <li class="divider"></li>
            <li>{btn type="list" text={__("export_selected")} dispatch="dispatch[orders.export_range]" form="orders_list_form"}</li>

            {$smarty.capture.incomplete_button nofilter}

            {if $orders && !$runtime.company_id}
                <li class="divider"></li>
                <li>{btn type="delete_selected" dispatch="dispatch[orders.m_delete]" form="orders_list_form"}</li>
            {/if}
        {else}
            {$smarty.capture.incomplete_button nofilter}
        {/if}
        {hook name="orders:list_tools"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}


<script>
    // Implements select orders functionality, because inputs no more inside form tags, and core script cant't process them
    (function (_, $) {
        var selectAllCheckbox = $('input[name="check_all"]'),
            orderIdsSelector = 'input[form="{$main_form_id}"][name="order_ids[]"], input[form="{$main_form_id}"][name="print_label_order_ids[]"]',
            selectAllOrdersHandler = function () {
                var orderIdsCheckboxes = $(orderIdsSelector),
                    selectAllCheckbox = $('input[name="check_all"]');
                orderIdsCheckboxes
                    .prop('checked', false)
                    .attr('checked', false);
                orderIdsCheckboxes
                    .prop('checked', selectAllCheckbox.prop('checked'))
                    .attr('checked', selectAllCheckbox.prop('checked'));
            },
            selectOrdersByStatusHandler = function () {
                var selectedCheckBoxes = $('.cm-item-status-' + $(this).data('caStatus')),
                    orderIdsCheckboxes = $(orderIdsSelector);
                orderIdsCheckboxes
                    .prop('checked', false)
                    .attr('checked', false);
                selectedCheckBoxes
                    .prop('checked', true)
                    .attr('checked', true);
            },
            selectNoneAllHandler = function () {
                var orderIdsCheckboxes = $(orderIdsSelector);
                if ($(this).hasClass('cm-off')) {
                    orderIdsCheckboxes
                        .prop('checked', false)
                        .attr('checked', false);
                } else if ($(this).hasClass('cm-on')) {
                    orderIdsCheckboxes
                        .prop('checked', true)
                        .attr('checked', true);
                }
            },
            insertOrphanedInputsIntoMainForm = function () {
                $('input[name="redirect_url"], input[name="page"]').each(function () {
                    if ($(this).parents('form').length === 0) {
                        $('form[name="{$main_form_id}"]').append($(this));
                    }
                });
            }
        ;

        $(orderIdsSelector).bind('click', function () {
            var linked_elm;
            if ($(this).attr('name') === 'order_ids[]') {
                linked_elm = $('input[form="{$main_form_id}"][name="print_label_order_ids[]"]').eq(
                    $('input[form="{$main_form_id}"][name="order_ids[]"]').index($(this))
                );
            } else {
                linked_elm = $('input[form="{$main_form_id}"][name="order_ids[]"]').eq(
                    $('input[form="{$main_form_id}"][name="print_label_order_ids[]"]').index($(this))
                );
            }
            linked_elm.prop('checked', $(this).prop('checked'));
        });
        $(selectAllCheckbox).bind('click', selectAllOrdersHandler);
        $('[data-ca-status]').bind('click', selectOrdersByStatusHandler);
        $('.cm-on, .cm-off').bind('click', selectNoneAllHandler);
        insertOrphanedInputsIntoMainForm();
        $(document).ajaxComplete(function () {
            $('input[name="check_all"], [data-ca-status], .cm-on, .cm-off').unbind('click');
            $('input[name="check_all"]').bind('click', selectAllOrdersHandler);
            $('[data-ca-status]').bind('click', selectOrdersByStatusHandler);
            $('.cm-on, .cm-off').bind('click', selectNoneAllHandler);
            insertOrphanedInputsIntoMainForm();
        });

    })(Tygh, Tygh.$);
</script>

<!-- /Overridden by the sd_myparcel_nl addon -->
