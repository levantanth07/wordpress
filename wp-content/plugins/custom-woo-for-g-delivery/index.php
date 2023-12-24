<?php
/*
Plugin Name: Custom Woo for G-Delivery
Plugin URI: https://thienhaxanh.info
Description: Add some logic to Woo.
Version: 1.0.0
Author: thienhaxanh2405
Author URI: https://thienhaxanh.info
*/

class CustomWooForGDelivery {

    public function __construct()
    {
        add_action( 'woocommerce_loaded', array( $this, 'loadPlugin') );
    }

    public function loadPlugin()
    {
        // hook to admin login page
        add_filter( 'login_headerurl', [$this, 'myLoginLogoUrl'] );
        add_filter( 'login_headertext', [$this, 'myLoginLogoUrlTitle'] );
        add_action( 'login_enqueue_scripts', [$this, 'myLoginLogo'] );
        add_action( 'admin_enqueue_scripts', [$this, 'adminStyle']);

        // hook to save product
        add_action('save_post',  [$this, 'removeCacheProduct']);
        add_action('deleted_post', [$this, 'removeCacheProduct']);

        // disable send email when password change
        add_filter( 'send_password_change_email', '__return_false' );

        add_filter( 'product_type_selector', [$this, 'customizeProductTypeText']);

        // order
        require_once 'libs/custom-order/status.php';

        // user
        require_once 'libs/custom-user/custom-user-meta.php';
        require_once 'libs/custom-user/custom-user-login.php';
        require_once 'libs/custom-user/Permission.php';

        // product
        require_once 'libs/custom-product/import-page.php';
        require_once 'libs/custom-product/add-custom-field-list-product.php';
        require_once 'libs/custom-product/add-custom-field-to-variation-product.php';
        require_once 'libs/custom-product/add-topping.php';
        require_once 'libs/custom-product/add-combo.php';
        // require_once 'libs/custom-product/add-voucher-coupon.php';
        require_once 'libs/custom-product/add-modifier.php';
        require_once 'libs/custom-product/product-hook.php';
        require_once 'libs/custom-product/add-product-group.php';
        // require_once 'libs/custom-product/add-product-meat-type.php';
        // require_once 'libs/custom-product/add-product-apply-for-brand.php';
        require_once 'libs/custom-product/add-custom-field-to-product.php';
        require_once 'libs/custom-product/add-custom-field-topping-product.php';
        require_once 'libs/custom-product/add-custom-field-time-config-product.php';

        // sort
        require_once 'libs/custom-sort/product-group.php';
        require_once 'libs/custom-sort/upsell-sort.php';

        // system setings
        require_once 'libs/system-settings/setting-website.php';

        // e-com
        require_once 'libs/e-com/category.php';
        require_once 'libs/e-com/add-meta-box-merchant-category.php';

        // ajax
        require_once 'libs/ajax/province.php';
        require_once 'libs/ajax/restaurant.php';
        require_once 'libs/ajax/checkout.php';
        require_once 'libs/ajax/customer.php';
        require_once 'libs/ajax/address.php';
        require_once 'libs/ajax/order.php';
        require_once 'libs/ajax/cart.php';
        require_once 'libs/ajax/brand.php';
        require_once 'libs/ajax/product.php';
        require_once 'libs/ajax/home.php';
        require_once 'libs/ajax/export.php';
    }

    function customizeProductTypeText($types){
        unset($types['grouped']);
        unset($types['external']);
        $types['simple'] = __('Sản phẩm đơn');
        $types['variable'] = __('Sản phẩm biến thể ');
        return $types;    
    }

    function adminStyle($hook) {
        wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__).'assets/css/admin-style.css');
        $screen = get_current_screen();
        if ('product_tag' == $screen->taxonomy) {
            wp_enqueue_script('customProductTag', plugin_dir_url(__FILE__) . '/assets/js/custom-product-tag.js');
        }
        if ('product' == $screen->post_type) {
            wp_enqueue_script('customProductJS', plugin_dir_url(__FILE__) . '/assets/js/custom-product.js');
        }
    }

    function myLoginLogo() { ?>
        <style type="text/css">
            .login h1 a {
                background-image: url(http://dealer.icook.com.vn/wp-content/uploads/2020/04/logo_162x47.png) !important;
                margin-bottom: -2rem !important;
                width: 160px !important;
                background-size: 160px !important;

            }
        </style>
    <?php }

    function myLoginLogoUrl()
    {
        return home_url('wp-login.php');
    }

    function myLoginLogoUrlTitle()
    {
        return 'Your Site Name and Info';
    }

    function removeCacheProduct($post_id)
    {
        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => \GDelivery\Libs\Config::REDIS_HOST,
            'port'   => \GDelivery\Libs\Config::REDIS_PORT,
            'password' => \GDelivery\Libs\Config::REDIS_PASS
        ]);
        $terms = get_the_terms($post_id, 'product_cat');
        if ($terms) {
            $term_info = null;
            foreach ($terms as $term) {
                if ($term->parent != 0) {
                    // hardcore for term is not parent
                    $term_info = $term;
                    break;
                }
            }

            if ($term_info) {
                $province_id = get_field('product_category_province_id', 'product_cat_' . $term_info->term_id);
                $key_cache = "icook:province:{$province_id}" . '*';
                $list_key_cache = $redis->keys($key_cache);
                if ($list_key_cache)
                    $redis->del($list_key_cache);
            }
        }
    }
} // end class

new CustomWooForGDelivery();