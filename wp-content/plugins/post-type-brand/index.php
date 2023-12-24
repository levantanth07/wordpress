<?php

/*
Plugin Name: Post Type Brand
Plugin URI: http://ggg.com.vn/
Description: Manage Brand.
Author: nghi.buivan <nghi.buivan@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

class BrandPostType {

    public function __construct()
    {
        // register province post type
        add_action( 'init', [$this, 'registerPostType'], 0 );
        add_action('admin_head', [$this, 'customJs']);
    }

    public function registerPostType()
    {
        $post_type = 'brand';
        $args = [
            'label' => 'Thương hiệu',
            'labels' => [
                'name' => 'Thương hiệu',
                'singular_name' => 'Thương hiệu',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Thương hiệu',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-admin-site-alt2',
            'rewrite' => ['slug' => 'brand '],
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

    function customJs() {
        global $post_type;
        if($post_type == 'brand') {
          ?>
          <script type="text/javascript">
            (function ($) {
                setTimeout(function(){
                    $('#titlewrap label').append('<span style="color:red;"> *</span>');
                    $('input[name="post_title"]').attr('required', true);

                    $('.post-attributes-label').append('<span style="color:red;"> *</span>');
                    $('input[name="menu_order"]').attr('required', true);
                }, 1000);
            })(jQuery);
          </script>
          <?php
        }
    }

}

$brandPostType = new BrandPostType();