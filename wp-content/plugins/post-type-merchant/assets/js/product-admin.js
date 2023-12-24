jQuery(document).ready(function($) {
    var productMasterIdInput = $("div#product_master_id").find('input');
    productMasterIdInput.attr('readonly', 'readonly');
    var variationMappingInput = $("div#variation_mapping").hide();
    var variationMappingInput = $("div#variation_mapping").find('input');
    variationMappingInput.attr('readonly', 'readonly');

    setTimeout(function() {
        // var merchantIdSelect = $("div#merchant_id").find('.select2-container');
        // merchantIdSelect.css({"pointer-events": "none", "width": "100%"});
    }, 1000);
});