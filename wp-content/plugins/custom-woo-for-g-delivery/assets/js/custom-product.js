jQuery(document).ready(function ($) {
    $(document).on('click', '.woocommerce_variation', function (e) {
        $(this).find('.variable_manage_stock').attr('checked', 'checked');
        $(this).find('.show_if_variation_manage_stock').css("display", "block");
        $(this).find('.show_if_variation_manage_stock select').css("pointer-events", "none");
    });
    $(document).on('click', '.inventory_tab', function (e) {
        $('#_manage_stock').attr('checked', 'checked');
        $('#_backorders').css("pointer-events", "none");
        $('.stock_fields').css("display", "block");
    });
    
});