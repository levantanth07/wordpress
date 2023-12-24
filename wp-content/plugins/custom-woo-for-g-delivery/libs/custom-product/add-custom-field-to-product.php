<?php

class AddCustomFieldsProduct
{
    const sapPriceBeforeTaxKey = '_sap_price_before_tax';
    const sapPriceAfterTaxKey = '_sap_price_after_tax';
    const priceBeforeTaxKey = '_price_before_tax';
    const priceAfterTaxKey = '_price_after_tax';
    const customPriceFields = [
        self::sapPriceBeforeTaxKey, 
        self::sapPriceAfterTaxKey, 
        self::priceBeforeTaxKey, 
        self::priceAfterTaxKey
    ];

    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'addCustomPriceToGeneralTab']);
        add_action('woocommerce_process_product_meta', [$this, 'saveCustomGeneralPrice'], 99, 1);
        add_action('woocommerce_product_options_pricing', [$this, 'customPriceGroupCss']);
        add_action('acf/validate_save_post', [$this, 'validateProductCustomFields']);
    }

    function validateProductCustomFields() 
    {
        if ('simple' == $_POST['product-type']) {
            if (empty($_POST['_sap_price_before_tax'])) {
                acf_add_validation_error('_sap_price_before_tax', 'Giá SAP chưa thuế là bắt buộc');
            }
        }
    }

    function customPriceGroupCss() 
    {
        // echo '<style>
        //     .woocommerce_options_panel ._sale_price_field { display: none; } 
        //     .woocommerce_options_panel ._regular_price_field input{ pointer-events: none; background-color: #f0f0f1;}
        // </style>';
    }

    /**
     * 
     * @param array $inputOptions woocommerce_wp_text_input Field data.
     */
    function wrapToACFField($inputOptions)
    {
        echo '<div class="acf-field is-required"><div class="acf-input">';
        woocommerce_wp_text_input($inputOptions);
        echo '</div></div>';
    }
    
    function addCustomPriceToGeneralTab() 
    {
        global $post;
        $showIfProductTypes = 'show_if_simple show_if_topping show_if_combo';
        $listPrice = [];
        foreach (self::customPriceFields as $customField) {
            $listPrice[$customField] = get_post_meta( $post->ID, $customField, true );
        }
        $this->wrapToACFField([
            'id' => self::sapPriceBeforeTaxKey,
            'label' => __('Giá SAP chưa thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $listPrice[self::sapPriceBeforeTaxKey],
            'wrapper_class' => $showIfProductTypes
        ]);
        woocommerce_wp_text_input([
            'id' => self::sapPriceAfterTaxKey,
            'label' => __('Giá SAP có thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $listPrice[self::sapPriceAfterTaxKey],
            'wrapper_class' => $showIfProductTypes
        ]);
        woocommerce_wp_text_input([
            'id' => self::priceBeforeTaxKey,
            'label' => __('Giá bán chưa thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                // 'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $listPrice[self::priceBeforeTaxKey],
            'wrapper_class' => $showIfProductTypes
        ]);
        woocommerce_wp_text_input([
            'id' => self::priceAfterTaxKey,
            'label' => __('Giá bán có thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $listPrice[self::priceAfterTaxKey],
            'wrapper_class' => $showIfProductTypes
        ]);
    }

    function saveCustomGeneralPrice($postId)
    {
        if (empty($_POST[self::sapPriceBeforeTaxKey])) {
            return;
        }
        $taxRate = 0;
        $promotionDiscount = 0; //TODO: check with promotions
        $product = new WC_Product($postId);
        if ($product->is_taxable()) {
            $taxes = new WC_Tax();
            $taxRates = $taxes->get_rates_for_tax_class($product->get_tax_class());
            $taxRates = array_values($taxRates);
            $taxRate = floatval($taxRates[0]->tax_rate);
        }
        $listPrice = [];
        $listPrice[self::sapPriceBeforeTaxKey] = intval($_POST[self::sapPriceBeforeTaxKey]);
        $listPrice[self::sapPriceAfterTaxKey] = $listPrice[self::sapPriceBeforeTaxKey] + ceil($taxRate / 100 * $listPrice[self::sapPriceBeforeTaxKey]);
        $listPrice[self::priceBeforeTaxKey] = $listPrice[self::sapPriceBeforeTaxKey] - $promotionDiscount;
        if (isset($_POST[self::priceBeforeTaxKey]) && $_POST[self::priceBeforeTaxKey]) {
            $listPrice[self::priceBeforeTaxKey] = intval($_POST[self::priceBeforeTaxKey]);
        }
        $listPrice[self::priceAfterTaxKey] = $listPrice[self::priceBeforeTaxKey] + ceil($taxRate / 100 * $listPrice[self::priceBeforeTaxKey]);
        foreach ($listPrice as $customField => $priceValue) {
            update_post_meta($postId, $customField, $priceValue);
        }
        // $regularPrice = $listPrice[self::sapPriceBeforeTaxKey];
        $regularPrice = $listPrice[self::priceBeforeTaxKey]; //TODO: temporary fix to use priceBeforeTaxKey
        update_post_meta($postId, '_regular_price', $regularPrice);
    }
}

$initAddCustomFieldsProduct = new AddCustomFieldsProduct();