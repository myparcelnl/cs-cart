<!-- Overrided by sd_myparcel_nl addon [manage.tpl] -->
{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="countries_form"
          class="{if ""|fn_check_form_permissions} cm-hide-inputs{/if}">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}
        <table width="100%" class="table table-middle">
            <thead>
            <tr>
                <th class="left">{__("code")}</th>
                <th class="center">{__("code")}&nbsp;A3</th>
                <th class="center">{__("code")}&nbsp;N3</th>
                <th>{__("country")}</th>
                <th>{__("addons.sd_myparcel_nl.tariff_zone")}</th>
                <th class="center">{__("region")}</th>
                <th class="right" width="10%">{__("status")}</th>
            </tr>
            </thead>
            {foreach from=$countries item=country}
                <tr class="cm-row-status-{$country.status|lower}">
                    <td class="center row-status">
                        {$country.code}
                    </td>
                    <td class="center row-status">
                        {$country.code_A3}
                    </td>
                    <td class="center row-status">
                        {$country.code_N3}
                    </td>
                    <td>
                        <input type="text" name="country_data[{$country.code}][country]" size="55"
                               value="{$country.country}" class="span4 input-hidden"/>
                    </td>
                    <td>
                        {include file="common/select_object.tpl" style="field" link_tpl=$config.current_url|fn_link_attach:"descr_sl=" items=$tariff_zones selected_key=$country.tariff_zone|default:'World' key_name="name" select_container_name="country_data[{$country.code}][tariff_zone]"}
                    </td>
                    <td class="center row-status">
                        {$country.region}
                    </td>
                    <td class="right">
                        {$has_permission = fn_check_permissions("tools", "update_status", "admin", "GET", ["table" => "countries"])}
                        {include file="common/select_popup.tpl" id=$country.code status=$country.status hidden="" object_id_name="code" table="countries" non_editable=!$has_permission}
                    </td>
                </tr>
            {/foreach}
        </table>
        {include file="common/pagination.tpl"}

    </form>
    {capture name="buttons"}
        {include file="buttons/save.tpl" but_name="dispatch[countries.m_update]" but_role="submit-link" but_target_form="countries_form"}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("countries") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons select_languages=true}
<!-- /Overrided by sd_myparcel_nl addon [manage.tpl] -->
