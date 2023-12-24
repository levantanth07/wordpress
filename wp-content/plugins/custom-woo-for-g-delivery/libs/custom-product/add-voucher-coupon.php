<?php

/**
 * Voucher-Coupon Product Type
 */
class VoucherCouponProductType extends \WC_Product_Simple
{

    /**
     * Return the product type
     * @return string
     */
    public function get_type()
    {
        return 'voucher-coupon';
    }
}

/**
 * Class for custom option
 */
class RegisterVoucherCouponProductType
{

    /**
     * Build the instance
     */
    public function __construct()
    {
        // load voucher-coupon produc type
        add_action('woocommerce_loaded', [$this, 'loadVoucherCouponType']);

        register_activation_hook(__FILE__, [$this, 'createVoucherCouponType']);

        // register product class
        add_filter('woocommerce_product_class', [$this, 'registerVoucherCouponProductTypeClass'], 10, 2);

        // add product selector
        add_filter('product_type_selector', [$this, 'addType']);

        // show price
        add_action('admin_footer', array($this, 'showPrice'));

        add_filter( 'woocommerce_product_data_tabs', array( $this, 'addTabVoucherCoupon' ), 50 );
        add_action( 'woocommerce_product_data_panels', array( $this, 'contentVoucherCoupon' ) );

        add_action('woocommerce_process_product_meta_voucher-coupon', array($this, 'saveCustomField'));

    }

    function registerVoucherCouponProductTypeClass($classname, $product_type)
    {
        if ($product_type == 'voucher-coupon') {
            $classname = 'VoucherCouponProductType';
        }
        return $classname;
    }

    /**
     * Load WC Dependencies
     *
     * @return void
     */
    public function loadVoucherCouponType()
    {
        new VoucherCouponProductType();
    }

    /**
     * add voucher-coupon to type selector
     *
     */
    public function addType($types)
    {
        $types['voucher-coupon'] = 'Sản phẩm voucher/coupon';

        return $types;
    }

    /**
     * Add Experience Product Tab.
     *
     * @param array $tabs
     *
     * @return mixed
     */
    public function addTabVoucherCoupon( $tabs ) {

        $tabs['advanced_type'] = array(
            'label'    => __( 'Voucher/coupon', 'your_textdomain' ),
            'target' => 'voucher-coupon_product_options',
            'class'  => 'show_if_voucher-coupon',
        );

        return $tabs;
    }

    /**
     * Add field campaign id to type selector
     *
     */
    public function contentVoucherCoupon()
    {
        global $post, $product_object;
        ?>
        <div id='voucher-coupon _product_options' class='panel woocommerce_options_panel hidden'>
            <div class='options_group'>
        <?php
        woocommerce_wp_text_input(
            array(
                'id' => '_evoucher_campaign_id[' . $post->ID . ']',
                'value' => $product_object->get_meta( '_evoucher_campaign_id', true ),
                'data_type' => 'text',
                'label' => __('Evoucher campaign id', 'woocommerce'),
            )
        );

        $campaign_start_date = $product_object->get_meta( '_campaign_start_date', true );
        $campaign_end_date = $product_object->get_meta( '_campaign_end_date', true );
        echo '<p class="form-field campaign_dates_fields">
				<label for="campaign_dates_fields">' . esc_html__('Thời gian sự kiện', 'woocommerce') . '</label>
				<input type="text" class="short" name="_campaign_start_date[' . $post->ID . ']" id="_campaign_start_date" value="' . esc_attr($campaign_start_date) . '" placeholder="' . esc_html(_x('From&hellip;', 'placeholder', 'woocommerce')) . ' YYYY-MM-DD" maxlength="10" pattern="' . esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')) . '" />
				<input type="text" class="short" style="clear: left; margin-top: 1em;" name="_campaign_end_date[' . $post->ID . ']" id="_campaign_end_date" value="' . esc_attr($campaign_end_date) . '" placeholder="' . esc_html(_x('To&hellip;', 'placeholder', 'woocommerce')) . '  YYYY-MM-DD" maxlength="10" pattern="' . esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')) . '" />
			</p>';
        ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save new fields for voucher/coupon
     *
     */
    function saveCustomField($post_id)
    {
        // Campaign code
        $campaignCode = $_POST['_evoucher_campaign_id'][$post_id];
        $campaignStartDate = $_POST['_campaign_start_date'][$post_id];
        $campaignEndDate = $_POST['_campaign_end_date'][$post_id];
        if (!empty($campaignCode)) {
            update_post_meta($post_id, '_evoucher_campaign_id', esc_attr($campaignCode));
        }
        if (!empty($campaignStartDate)) {
            update_post_meta($post_id, '_campaign_start_date', esc_attr($campaignStartDate));
        }
        if (!empty($campaignEndDate)) {
            update_post_meta($post_id, '_campaign_end_date', esc_attr($campaignEndDate));
        }
    }

    /**
     * display price for voucher-coupon
     */
    public function showPrice()
    {
        global $post, $product_object;

        if (!$post) {
            return;
        }

        if ('product' != $post->post_type) {
            return;
        }

        if ($product_object && $product_object->get_type() == 'voucher-coupon') {
            $isVoucherCouponType = true;
        } else {
            $isVoucherCouponType = false;
        }

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function ($) {
                //for Price tab
                jQuery('#general_product_data .pricing').addClass('show_if_voucher-coupon');

                <?php if ( $isVoucherCouponType ) { ?>
                jQuery('.product_data_tabs li').removeClass('active');
                jQuery('.panel-wrap.product_data .panel').hide();
                jQuery('#general_product_data').show().find('.pricing').show();
                jQuery('.general_options').show().addClass('active');

                $('.campaign_dates_fields').each(function () {
                    $(this).find('input').datepicker({
                        defaultDate: '',
                        dateFormat: 'yy-mm-dd',
                        numberOfMonths: 1,
                        showButtonPanel: true,
                        onSelect: function () {
                            date_picker_select($(this));
                        }
                    });
                    $(this).find('input').each(function () {
                        date_picker_select($(this));
                    });
                });

                // Date picker fields.
                function date_picker_select(datepicker) {
                    var option = $(datepicker).next().is('.hasDatepicker') ? 'minDate' : 'maxDate',
                        otherDateField = 'minDate' === option ? $(datepicker).next() : $(datepicker).prev(),
                        date = $(datepicker).datepicker('getDate');

                    $(otherDateField).datepicker('option', option, date);
                    $(datepicker).change();
                }
                <?php } ?>
            });
        </script>
        <?php
    }

    /**
     * Installing on activation
     *
     * @return void
     */
    public function createVoucherCouponType()
    {
        // If there is no advanced product type taxonomy, add it.
        if (!get_term_by('slug', 'voucher-coupon', 'product_type')) {
            wp_insert_term('voucher-coupon', 'product_type');
        }
    }
}

$voucherCouponProductType = new RegisterVoucherCouponProductType();