{if $shipments}
    <li>{btn type="list" text=__("addons.sd_myparcel_nl.bulk_print_labels") class="cm-new-window" dispatch="dispatch[shipments.print_labels]" form="manage_shipments_form"}</li>
{/if}
