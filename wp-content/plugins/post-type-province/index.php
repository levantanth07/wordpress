<?php

/*
Plugin Name: Post Type Province
Plugin URI: http://ggg.com.vn/
Description: Manage Province.
Author: nghi.buivan <nghi.buivan@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

class ProvincePostType {

    public function __construct()
    {
        // register province post type
        add_action( 'init', [$this, 'registerPostType'], 0 );
    }

    public function registerPostType()
    {
        $post_type = 'province';
        $args = [
            'label' => 'Tỉnh thành',
            'labels' => [
                'name' => 'Tỉnh thành',
                'singular_name' => 'Tỉnh thành',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Tỉnh thành',
            ],
            'menu_position' => 20,
            'rewrite' => ['slug' => 'province '],
            'supports' => ['title'],
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

}

// init
$provincePostType = new ProvincePostType();