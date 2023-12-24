<?php

use GDelivery\Libs\Config;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Helper;
use Abstraction\Object\Message;
use Abstraction\Object\ApiMessage;

class EComCategory
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'addCustomScripts'], 10, 1);
        add_action( 'init', [$this, 'createEComCategoryTaxonomy'], 0 );
        add_filter( 'manage_edit-ecom-category_columns', [$this, 'setCustomEditEComCategoryColumns'] );
        add_filter( 'manage_ecom-category_custom_column' , [$this, 'customEComCategoryColumn'], 10, 3 );
        add_action( 'init', [$this, 'createMerchantCategoryTaxonomy'], 0 );
        add_filter( 'manage_edit-merchant-category_columns', [$this, 'setCustomEditMerchantCategoryColumns'] );
        add_filter( 'manage_merchant-category_custom_column' , [$this, 'customMerchantCategoryColumn'], 10, 3 );
        add_filter('list_cats', [$this, 'customCategoryName'], 10, 3);
        add_filter('acf/input/admin_footer', [$this, 'addCustomJquery'], 10, 1);

        add_action('rest_api_init', [$this, 'registerApi']);
    }

    public function registerApi()
    {
        register_rest_route('api/v1', 'e-com/category', array(
            'methods' => 'GET',
            'callback' => [$this, "getEComCategory"],
        ));
    }

    public function addCustomScripts($hook)
    {
        global $post;

        if ($post && $post->post_type == 'product') {
            wp_enqueue_style('slider', plugins_url() . '/custom-woo-for-g-delivery/assets/css/custom.css', false, Config::VERSION, 'all');
        }
    }

    public function createEComCategoryTaxonomy() {
        $labels = array(
            'name' => __( 'Danh mục ngành hàng' ),
            'singular_name' => __( 'E-com category' ),
            'search_items' =>  __( 'Search category' ),
            'all_items' => __( 'All category' ),
            'parent_item' => __( 'Parent category' ),
            'parent_item_colon' => __( 'Parent category:' ),
            'edit_item' => __( 'Edit E-Com category' ),
            'update_item' => __( 'Update category' ),
            'add_new_item' => __( 'Add New' ),
            'new_item_name' => __( 'New category name' ),
            'menu_name' => __( 'E-Com category' ),
        );

        register_taxonomy('ecom-category',array('product'), array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'E-com category' ),
        ));
    }

    public function setCustomEditEComCategoryColumns($columns) {
        $new_columns = array();

        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['thumb'] = __( 'Thumbnail' );
        $new_columns['name'] = __( 'Name' );
        $new_columns['order'] = __( 'Thứ tự' );

        $columns           = array_merge( $new_columns, $columns );
        $columns['handle'] = '';

        return $columns;
    }

    public function customEComCategoryColumn( $content, $columnName, $termId ) {
        switch ( $columnName ) {
            case 'thumb':
                $thumbnail = get_term_meta( $termId , 'thumbnail', true );
                echo wp_get_attachment_image( $thumbnail, array('40', '40'), "", array( "class" => "img-responsive" ) );
                break;
            case 'order':
                $order = get_term_meta( $termId , 'order', true );
                echo $order;
                break;
        }
    }

    public function createMerchantCategoryTaxonomy() {
        $labels = array(
            'name' => __( 'Quản lý category merchant' ),
            'singular_name' => __( 'Merchant category' ),
            'search_items' =>  __( 'Search category' ),
            'all_items' => __( 'All category' ),
            'parent_item' => __( 'Parent category' ),
            'parent_item_colon' => __( 'Parent category:' ),
            'edit_item' => __( 'Edit Merchant category' ),
            'update_item' => __( 'Update category' ),
            'add_new_item' => __( 'Add New' ),
            'new_item_name' => __( 'New category name' ),
            'menu_name' => __( 'Merchant category' ),
        );

        register_taxonomy('merchant-category',array('product'), array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'Merchant category' ),
        ));
    }

    public function setCustomEditMerchantCategoryColumns($columns) {
        $new_columns = array();

        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
            unset( $columns['cb'] );
        }

        $new_columns['name'] = __( 'Name' );
        $new_columns['order'] = __( 'Thứ tự' );

        $columns           = array_merge( $new_columns, $columns );
        $columns['merchant'] = __( 'Merchant' );

        return $columns;
    }

    public function customMerchantCategoryColumn( $content, $columnName, $termId ) {
        switch ( $columnName ) {
            case 'merchant':
                $merchantId = get_term_meta( $termId , 'merchant_id', true );
                if ($merchantId) {
                    $merchantObj = get_post($merchantId);

                    echo $merchantObj->post_title;
                }
                break;
            case 'order':
                $order = get_term_meta( $termId , 'order', true );
                echo $order;
                break;
        }
    }

    public function customCategoryName($categoryName, $category) {
        global $taxonomy;

        if ($taxonomy == 'merchant-category' &&
            $category &&
            empty($category->parent)) {
            $termId = $category->term_id;
            $merchantId = get_term_meta( $termId , 'merchant', true );
            $merchantName = '';
            if ($merchantId) {
                $merchantName = get_the_title($merchantId);
            }
            $categoryName .= ' - ' . $merchantName;
        }

        return $categoryName;
    }

    public function addCustomJquery() {
        global $taxonomy;
        global $pagenow;
        if (
            $taxonomy == 'merchant-category'
            && $pagenow === 'edit-tags.php') {
            ?>
            <script type="text/javascript">
			        (function ($) {
				        $('<b>Cài đặt ranking</b>').insertBefore($('div[data-name="ranking_day"]'));
				        $('<div style="clear: both;"><b>Merchant category</b></div>').insertBefore($('div[data-name="short_name"]'));

                        $('.form-field.term-name-wrap label').append('<span style="color:red;"> *</span>');
                        $('.form-field.term-name-wrap input').attr('required', true);
			        })(jQuery)
            </script>
            <?php
        }
    }

    public function getEComCategory(WP_REST_Request $request) {
        $res = new Result();
        try {
            $params['type'] = $request['type'];

            $getMerchantCategory = Helper::getEComCategory($params);

            if ($getMerchantCategory->messageCode == Message::SUCCESS) {
                $res->result = $getMerchantCategory->result;
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'success';

            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = $getMerchantCategory->message;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant category Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }
}
$eComCategory = new EComCategory();
