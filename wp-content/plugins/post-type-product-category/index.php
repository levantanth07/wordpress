<?php
/*
Plugin Name: Post Type Product Category
Plugin URI: http://ggg.com.vn/
Description: Manager product category.
Author: hoang.daohuy <hoang.daohuy@ggg.com.vn>
Version: 1.0
*/

use GDelivery\Libs\Config;
use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;

class ProductCategoryPostType {

    public function addCustomScripts($hook)
    {
        global $post;

        if ($post && $post->post_type == 'product_category') {
            wp_enqueue_style( 'slider', plugin_dir_url(__FILE__) . '/assets/css/custom.css', false, Config::VERSION, 'all');

            wp_enqueue_script( 'script', plugin_dir_url(__FILE__) . '/assets/js/custom.js', array( 'jquery' ), Config::VERSION, true );
        }
    }

    public function __construct()
    {
        // register tgs banner post type
        add_action( 'init', [$this, 'registerPostType'], 0 );

        add_filter( 'parse_query', [$this, "customFilterProductCategory"]);

        add_action('restrict_manage_posts', [$this, 'restrictManagePostType']);

        add_action('post_submitbox_start', [$this, 'addBlockSyncMerchant']);

        add_action('admin_enqueue_scripts', [$this, 'addCustomScripts'], 10, 1);

        // Add the custom columns to the book post type:
        add_filter( 'manage_product_category_posts_columns', [$this, 'setCustomEditProductCategoryColumns'] );
        // Add the data to the custom columns for the book post type:
        add_action( 'manage_product_category_posts_custom_column' , [$this, 'customProductCategoryColumn'], 10, 2 );

        // Sync product category
        add_action("wp_ajax_sync_product_category", [$this, "syncProductCategory"]);

        add_action( 'woocommerce_loaded', array( $this, 'loadPlugin') );
    }

    public function loadPlugin()
    {
        require_once 'add-meta-box-merchant-category.php';
    }

    public function registerPostType()
    {
        $post_type = 'product_category';
        $args = [
            'label' => 'Product Category',
            'labels' => [
                'name' => 'Product Category',
                'singular_name' => 'Product Category',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Product Category',
            ],
            'menu_position' => 20,
            'rewrite' => ['slug' => 'product_category'],
            'supports' => ['title'],
            'taxonomies'          => ['product_category', ],
            'exclude_from_search' => false,
            'capabilities' => [
                /*'edit_post'          => 'edit_tgs-notification',
                'read_post'          => 'read_tgs-notification',
                'delete_post'        => 'delete_tgs-notification',
                'edit_posts'         => 'edit_tgs-notification',
                'edit_others_posts'  => 'edit_tgs-notification',
                'publish_posts'      => 'publish_tgs-notification',
                'read_private_posts' => 'read_private_tgs-notification',
                'create_posts'       => 'edit_tgs-notification',*/
            ],
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type( $post_type, $args );
    }

    public function restrictManagePostType()
    {
        $postType = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if ($postType == 'product_category') {
            global $wpdb;
            $listMerchant = $wpdb->get_results("
                SELECT ID,post_title 
                FROM  $wpdb->posts
                WHERE post_type = 'merchant' AND post_status = 'publish'
            ");
            $current = isset($_GET['apply_for_merchant']) ? $_GET['apply_for_merchant'] : '';
            ?>
            <select name="apply_for_merchant">
                <option value=""><?php _e('Filter By Merchant', 'baapf'); ?></option>
                <?php
                foreach ($listMerchant as $merchant) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $merchant->ID,
                        $merchant->ID == $current ? ' selected="selected"' : '',
                        $merchant->post_title
                    );
                }
                ?>
            </select>
            <?php
        }
    }

    public function addBlockSyncMerchant($post)
    {
        $isMasterProduct = get_field('is_product_category_master', $post->ID) ?? false;
        if (!current_user_can('edit_posts') || !$isMasterProduct) {
            return false;
        }

        $html = "<div class='notice notice-success notice-sync-product-category is-dismissible hidden'><p>Đồng bộ thành công.</p></div>";
        $html .= "<button type='button' class='button-primary text-center' style='margin: 10px 0;' id='sync_product_category' data-product-category-id='{$post->ID}'>SYNC TO MERCHANT</button>";
        ?>
        <div class="block-merchant"></div>
        <script type="text/javascript">
	        jQuery(document).ready(function($) {
		        $('#sync_product_category').on('click', function () {
                    let thisElem = $(this);

                    thisElem.addClass('loading');
                    let productCategoryId = $(this).attr('data-product-category-id');
                    let data = {
                            action: 'sync_product_category',
                            productCategoryId: productCategoryId,
                        };
                    $.ajax({
                        url : '<?=admin_url('admin-ajax.php')?>',
                        type : 'post',
                        dataType : 'json',
                        data : data,
                        success : function (res) {
                            if (res.messageCode == 1) {
                                $('.notice-sync-product-category').removeClass('hidden');
                            } else {
                                //
                            }
                        },
                        error : function (x, y, z) {
                            //
                        },
                        complete : function () {
	                        thisElem.removeClass('loading');
                        }
                    });
		        });
	        });
        </script>
        <?php
        echo $html;
    }

    public function setCustomEditProductCategoryColumns($columns) {
        unset( $columns['author'] );
        unset( $columns['date'] );
        $columns['apply_for_merchant'] = __( 'Áp dụng cho merchant' );
        $columns['author'] = __( 'Author' );
        $columns['date'] = __( 'Date' );

        return $columns;
    }

    function customProductCategoryColumn( $column, $post_id ) {
        switch ( $column ) {

            case 'author' :
                $terms = get_the_term_list( $post_id , 'author' , '' , ',' , '' );
                if ( is_string( $terms ) )
                    echo $terms;
                else
                    _e( 'Unable to get author(s)' );
                break;

            case 'apply_for_merchant' :
                $merchantId = get_post_meta( $post_id , 'apply_for_merchant' , true );
                $isMaster = get_post_meta( $post_id , 'is_product_category_master' , true );
                echo ! $isMaster ? get_the_title($merchantId) : '';
                break;

        }
    }

    public function syncProductCategory()
    {
        global $wpdb;
        $res = new Result();

        if (isset($_REQUEST['productCategoryId'])) {
            $productCategoryId = $_REQUEST['productCategoryId'];
            $merchants = get_field('apply_for_merchants', $productCategoryId);
            $thumbnail = get_field('thumbnail', $productCategoryId);
            $mobileOrder = get_field('mobile_order', $productCategoryId);
            $rankingDay = get_field('ranking_day', $productCategoryId);
            $rankingMeal = get_field('ranking_meal', $productCategoryId);
            $rankingOrder = get_field('ranking_order', $productCategoryId);

            foreach ($merchants as $merchant) {
                $arg = array(
                    'post_author' => get_current_user_id(),
                    'post_content' => '',
                    'post_status' => "publish",
                    'post_title' => get_the_title($productCategoryId),
                    'post_type' => "product_category",
                );
                $query = "SELECT p.ID FROM {$wpdb->posts} as p ".
                    "JOIN {$wpdb->postmeta} as pm ON pm.post_id=p.ID AND pm.meta_key='apply_for_merchant'".
                    "JOIN {$wpdb->postmeta} as pm2 ON pm2.post_id=p.ID AND pm2.meta_key='parent_product_category_id'".
                    " WHERE pm.meta_value=%d AND pm2.meta_value=%d AND p.post_status='publish'";
                $postId = $wpdb->get_var($wpdb->prepare($query, $merchant->ID, $productCategoryId));

                if (!$postId) {
                    //Create product category
                    $postId = wp_insert_post($arg);
                    update_post_meta($postId, 'apply_for_merchant', $merchant->ID);
                    update_post_meta($postId, 'parent_product_category_id', $productCategoryId);
                    update_post_meta($postId, 'thumbnail', $thumbnail);
                    update_post_meta($postId, 'ranking_day', $rankingDay);
                    update_post_meta($postId, 'ranking_meal', $rankingMeal);
                    update_post_meta($postId, 'ranking_order', $rankingOrder);
                    update_post_meta($postId, 'mobile_order', $mobileOrder);
                } else {
                    $productCategory = array(
                        'ID' => $postId,
                        'post_title' => get_the_title($productCategoryId),
                    );

                    wp_update_post($productCategory);
                    update_post_meta($postId, 'thumbnail', $thumbnail);
                    update_post_meta($postId, 'ranking_day', $rankingDay);
                    update_post_meta($postId, 'ranking_meal', $rankingMeal);
                    update_post_meta($postId, 'ranking_order', $rankingOrder);
                    update_post_meta($postId, 'mobile_order', $mobileOrder);
                }
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = '';

        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
        }

        Response::returnJson($res);
        die;
    }

    public function customFilterProductCategory( $query ) {
        global $pagenow;
        $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if ( is_admin() && $pagenow=='edit.php' && $post_type == 'product_category' && ! empty( $_GET['apply_for_merchant'] ) ) {
            $query->query_vars['meta_key'] = 'apply_for_merchant';
            $query->query_vars['meta_value'] = $_GET['apply_for_merchant'];
        }
    }

}

// init
$productCategoryPostType = new ProductCategoryPostType();
