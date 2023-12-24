<?php

/*
Plugin Name: Post Type Rating
Plugin URI: http://ggg.com.vn/
Description: Manage Rating.
Author: nghi.buivan <nghi.buivan@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

class RatingPostType {

    public function __construct()
    {
        // register province post type
        add_action( 'init', [$this, 'registerPostType'], 0 );
        add_filter( 'manage_rating_posts_columns', [$this, 'set_custom_merchant_columns'] );
        add_action( 'manage_rating_posts_custom_column' , [$this, 'custom_merchant_column'], 10, 2 );
        add_filter('parse_query', [$this, 'customMerchantsFilter']);
        add_action('restrict_manage_posts', [$this, 'customFilterByMerchant']);
        add_action('admin_head', [$this, 'ratingCustomJs']);
    }

    public function registerPostType()
    {
        $post_type = 'rating';
        $args = [
            'label' => 'Rating',
            'labels' => [
                'name' => 'Rating',
                'singular_name' => 'Rating',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Rating',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-star-half',
            'rewrite' => ['slug' => 'rating '],
            'taxonomies' => [ 'rating' ],
            'supports' => ['title', 'thumbnail'],
            //'taxonomies'  => ['restaurant-category', ],
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

    public function set_custom_merchant_columns($columns) {
        $columns['merchant_id'] = __( 'Merchant', 'Merchant' );
        $columns['avatar'] = __( 'Avatar', 'Avatar' );
        return $columns;
    }

    public function custom_merchant_column( $column, $post_id ) {
        if ($column == 'merchant_id') {
            $merchant = get_field('merchant_id', $post_id);
            if (is_numeric($merchant)) {
                $getMerchant = get_post($merchant);
                if ($getMerchant) {
                    echo $getMerchant->post_title;
                }
            } else {
                echo $merchant->post_title;
            }
        }
        if ($column == 'avatar') {
            $thumb = get_the_post_thumbnail_url($post_id, 'shop_catalog');
            if ($thumb) {
                echo '<img src="' .$thumb.'"  width="50" height="50">';
            }
        }
    }

    function customMerchantsFilter($query)
    {
        global $pagenow;
        $post_type = $_GET['post_type'] ?? '';
        if (is_admin() && $pagenow == 'edit.php' && in_array($post_type, ['rating'])) {
            if (isset($_GET['custom_field_merchant_id']) && $_GET['custom_field_merchant_id'] != '') {
                $query->query_vars['meta_key'] = 'merchant_id';
                $query->query_vars['meta_value'] = $_GET['custom_field_merchant_id'];
            }
        }
    }

    function customFilterByMerchant($postType)
    {
        if('rating' !== $postType){
            return;
        }
        global $wpdb;
        $listMerchant = $wpdb->get_results("
            SELECT ID,post_title 
            FROM  $wpdb->posts
            WHERE post_type = 'merchant' AND post_status = 'publish'
        ");
        $current = $_GET['custom_field_merchant_id'] ?? '';
        ?>
        <select name="custom_field_merchant_id">
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

    function ratingCustomJs() {
        global $post_type;
        if($post_type == 'rating') {
          ?>
          <script type="text/javascript">
            (function ($) {
                setTimeout(function(){
                    $('#titlewrap label').append('<span style="color:red;"> *</span>');
                    $('input[name="post_title"]').attr('required', true);
                }, 1000);

                $(document).on('click', '#publish', function(e) {
                    let thumbnailId = $('#_thumbnail_id').val();
                    if (thumbnailId == -1) {
                        e.preventDefault();
                        let thumbnailError = `<div class="acf-notice -error acf-error-message"><p>Ảnh đại diện là trường bắt buộc</p></div>`;
                        $('#postimagediv').find('.acf-error-message').remove();
                        $('#postimagediv').prepend(thumbnailError);
                    }
                });
            })(jQuery);
          </script>
          <?php
        }
    }

}

// init
$ratingPostType = new RatingPostType();