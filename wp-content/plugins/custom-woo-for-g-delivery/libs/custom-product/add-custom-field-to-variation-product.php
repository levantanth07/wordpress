<?php

class AddCustomFieldToVariationProduct {

    const sapPriceBeforeTaxKey = '_sap_price_before_tax';
    const sapPriceAfterTaxKey = '_sap_price_after_tax';
    const priceBeforeTaxKey = '_price_before_tax';
    const priceAfterTaxKey = '_price_after_tax';

    public function __construct()
    {
        // Add Variation Settings
        add_action( 'woocommerce_product_after_variable_attributes', [$this, 'showCustomField'], 999, 3 );

        // Save Variation Settings
        add_action( 'woocommerce_save_product_variation', [$this, 'saveCustomField'], 10, 2 );

        add_action( 'woocommerce_variation_options_pricing', [$this, 'addVariationCustomPriceFields'], 10, 3 );

        add_action('acf/validate_save_post', [$this, 'validateProductCustomFields']);
    }

    function validateProductCustomFields()
    {
        if ('variable' == $_POST['product-type']) {
            if (isset($_POST['_sap_price_before_tax']) && is_array($_POST['_sap_price_before_tax'])) {
                foreach($_POST['_sap_price_before_tax'] as $variationId => $variationPrice) {
                    if (empty($variationPrice)) {
                        acf_add_validation_error("_sap_price_before_tax[$variationId]", 'Giá SAP chưa thuế là bắt buộc');
                    }
                }
            }
        }
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
    
    /**
     * Create new fields for variations
     *
     */
    function showCustomField( $loop, $variation_data, $variation )
    {
        // sap code for variation
        woocommerce_wp_text_input(
            array(
                'id' => 'product_variation_sap_code[' . $variation->ID . ']',
                'label' => 'Mã SAP',
                'desc_tip' => 'true',
                'description' => 'Mã SAP',
                'value' => get_post_meta( $variation->ID, 'product_variation_sap_code', true ),
                'custom_attributes' => [
                    'step' 	=> 'any',
                    'min'	=> '0'
                ]
            )
        );

        // rk code for variation
        woocommerce_wp_text_input(
            array(
                'id' => 'product_variation_rk_code[' . $variation->ID . ']',
                'label' => 'Mã RK',
                'desc_tip' => 'true',
                'description' => 'Mã RK',
                'value' => get_post_meta( $variation->ID, 'product_variation_rk_code', true ),
                'custom_attributes' => [
                    'step' 	=> 'any',
                    'min'	=> '0'
                ]
            )
        );

        // limit amount for variation
        woocommerce_wp_text_input(
            array(
                'id' => 'product_limited_quantity_per_day[' . $variation->ID . ']',
                'label' => 'Giới hạn mua mỗi ngày',
                'type' => 'number',
                'desc_tip' => 'true',
                'description' => 'Giới hạn số lượng được mua mỗi ngày, 0 - là ko giới hạn',
                'value' => get_post_meta( $variation->ID, 'product_limited_quantity_per_day', true ),
                'custom_attributes' => [
                    'step' 	=> 'any',
                    'min'	=> '0'
                ]
            )
        );
    }

    /**
     * Save new fields for variations
     *
     */
    function saveCustomField( $post_id )
    {
        // sap code
        $sapCode = $_POST['product_variation_sap_code'][ $post_id ];
        if( ! empty( $sapCode ) ) {
            update_post_meta( $post_id, 'product_variation_sap_code', esc_attr( $sapCode ) );
        }

        // rk code
        $rkCode = $_POST['product_variation_rk_code'][ $post_id ];
        if( ! empty( $rkCode ) ) {
            update_post_meta( $post_id, 'product_variation_rk_code', esc_attr( $rkCode ) );
        }

        // rk code
        $limitedAmount = $_POST['product_limited_quantity_per_day'][ $post_id ];
        if( $limitedAmount >= 0 ) {
            update_post_meta( $post_id, 'product_limited_quantity_per_day', esc_attr( $limitedAmount ) );
        }

        // custom price fields
        $this->saveCustomPrice($post_id);
    }

    function saveCustomPrice($postId)
    {
        if (empty($_POST[self::sapPriceBeforeTaxKey])) {
            return;
        }
        $taxRate = 0;
        $promotionDiscount = 0; //TODO: check with promotions
        $product = new WC_Product_Variation($postId);
        if ($product->is_taxable()) {
            $taxes = new WC_Tax();
            $taxRates = $taxes->get_rates_for_tax_class($product->get_tax_class());
            $taxRates = array_values($taxRates);
            $taxRate = floatval($taxRates[0]->tax_rate);
        }
        $listPrice = [];
        $listPrice[self::sapPriceBeforeTaxKey] = intval($_POST[self::sapPriceBeforeTaxKey][$postId]);
        $listPrice[self::sapPriceAfterTaxKey] = $listPrice[self::sapPriceBeforeTaxKey] + ceil($taxRate / 100 * $listPrice[self::sapPriceBeforeTaxKey]);
        $listPrice[self::priceBeforeTaxKey] = $listPrice[self::sapPriceBeforeTaxKey] - $promotionDiscount;
        if (isset($_POST[self::priceBeforeTaxKey][$postId]) && $_POST[self::priceBeforeTaxKey][$postId]) {
            $listPrice[self::priceBeforeTaxKey] = intval($_POST[self::priceBeforeTaxKey][$postId]);
        }
        $listPrice[self::priceAfterTaxKey] = $listPrice[self::priceBeforeTaxKey] + ceil($taxRate / 100 * $listPrice[self::priceBeforeTaxKey]);
        foreach ($listPrice as $customField => $priceValue) {
            update_post_meta($postId, $customField, $priceValue);
        }
        // $regularPrice = $listPrice[self::sapPriceBeforeTaxKey];
        $regularPrice = $listPrice[self::priceBeforeTaxKey]; //TODO: temporary fix to use priceBeforeTaxKey
        update_post_meta($postId, '_regular_price', $regularPrice);
    }

    function addVariationCustomPriceFields( $loop, $variation_data, $variation ) 
    {
        $sapPriceBeforeTax = get_post_meta( $variation->ID, '_sap_price_before_tax', true );
        $sapPriceAfterTax = get_post_meta( $variation->ID, '_sap_price_after_tax', true );
        $priceBeforeTax = get_post_meta( $variation->ID, '_price_before_tax', true );
        $priceAfterTax = get_post_meta( $variation->ID, '_price_after_tax', true );
        $this->wrapToACFField([
            'id' => '_sap_price_before_tax[' . $variation->ID . ']',
            'wrapper_class' => 'form-row form-row-full',
            'label' => __('Giá SAP chưa thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $sapPriceBeforeTax,
        ]);
        woocommerce_wp_text_input([
            'id' => '_sap_price_after_tax[' . $variation->ID . ']',
            'wrapper_class' => 'form-row form-row-full',
            'label' => __('Giá SAP có thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $sapPriceAfterTax,
        ]);
        woocommerce_wp_text_input([
            'id' => '_price_before_tax[' . $variation->ID . ']',
            'wrapper_class' => 'form-row form-row-full',
            'label' => __('Giá bán chưa thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                // 'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $priceBeforeTax,
        ]);
        woocommerce_wp_text_input([
            'id' => '_price_after_tax[' . $variation->ID . ']',
            'wrapper_class' => 'form-row form-row-full',
            'label' => __('Giá bán có thuế', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'readonly' 	=> 'readonly',
                'step' 	=> 'any',
                'min'	=> '0'
            ),
            'value' => $priceAfterTax,
        ]);
        // echo '<style>
        //     .woocommerce_variation .variable_pricing .form-field:nth-child(2) { display: none; } 
        //     .woocommerce_variation .variable_pricing .form-field:nth-child(1) input{ pointer-events: none; background-color: #f0f0f1;}
        // </style>';
    }

}

$initCustomField = new AddCustomFieldToVariationProduct();

