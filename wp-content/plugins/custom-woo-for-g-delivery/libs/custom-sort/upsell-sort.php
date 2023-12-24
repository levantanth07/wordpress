<?php
add_action('woocommerce_product_options_related', 'upsellProduct');
function upsellProduct() {
    ?>
    <script type="text/javascript">
        (function($) {
            $('#upsell_ids').attr('data-sortable', 'true');
        })(jQuery);
    </script>
    <?php
}
