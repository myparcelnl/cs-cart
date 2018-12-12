<script type="text/javascript">
    var packages = [];
</script>
{include file="addons/sd_myparcel_nl/views/shipments/components/delivery_date.tpl"}
{literal}
    <script type="text/javascript">
        function fn_calculate_packages()
        {
            var products = [];

            Tygh.$('.cm-shipments-package:checked').each(function(id, elm) {
                jelm = Tygh.$(elm);
                id = jelm.prop('id');

                for (var i in packages[id]) {
                    if (typeof(products[i]) == 'undefined') {
                        products[i] = parseInt(packages[id][i]);
                    } else {
                        products[i] += parseInt(packages[id][i]);
                    }
                }
            });

            // Set the values of the ship products to 0. We will change the values to the correct variants after
            Tygh.$('.cm-shipments-product').each(function() {
                Tygh.$(this).val(0);
            });

            if (products.length > 0) {
                for (var i in products) {
                    Tygh.$('#shipment_data_' + i).val(products[i]);
                }
            }
        }
        Tygh.$(document).ready(function() {
            Tygh.$('.cm-shipments-package').on('change', fn_calculate_packages);
        });
    </script>
{/literal}
