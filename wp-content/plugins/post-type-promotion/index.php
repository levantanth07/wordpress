<?php
/*
Plugin Name: Post Type Promotion
Plugin URI: http://ggg.com.vn/
Description: Manage Promotion.
Author: thienhaxanh2405 <toan.nguyenduc@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

class PromotionPostType {

    /**
     * @param WP_Post $post
     *
     * @return stdClass
     */
    private function convertToStdClass($post)
    {
        $temp = new \stdClass();

        $temp->id = $post->ID;
        $temp->title = html_entity_decode($post->post_title);
        $temp->content = $post->post_content;
        $temp->thumbnail = get_the_post_thumbnail_url($post->ID, 'large') ? get_the_post_thumbnail_url($post->ID, 'large') : '';
        $temp->date = $post->post_date;
        $temp->status = $post->post_status;
        $temp->startTime = get_field('promotion_start_time', $post->ID);
        $temp->endTime = get_field('promotion_end_time', $post->ID);
        $temp->order = get_field('promotion_order', $post->ID);
        $temp->displayInProvince = get_field('promotion_display_in_provinces', $post->ID);
        $temp->brandId = get_field('promotion_brand_id', $post->ID);

        $temp->codeType = get_field('promotion_code_type', $post->ID);
        $temp->voucherInfo = get_field('promotion_voucher_info', $post->ID);

        return $temp;
    }

    public function __construct()
    {
        // register tgs banner post type
        add_action( 'init', [$this, 'registerPostType'], 0 );

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route(
                'api/v1/promotion',
                '/list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getPromotions"],
                    'permission_callback' => function() {
                        return '';
                    }
                ]
            );
        } );

        add_action(
            'rest_api_init',
            function () {
                register_rest_route(
                    'api/v1/promotion',
                    '/detail',
                    [
                        'methods' => 'GET',
                        'callback' => [$this, "getPromotionDetail"],
                        'permission_callback' => function() {
                            return '';
                        }
                    ]
                );
            }
        );
    }

    public function registerPostType()
    {
        $post_type = 'promotion';
        $args = [
            'label' => 'Ưu đãi',
            'labels' => [
                'name' => 'Ưu đãi',
                'singular_name' => 'Ưu đãi',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Ưu đãi',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-tickets-alt',
            'rewrite' => ['slug' => 'promotions '],
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

    // add api
    public function getPromotions( WP_REST_Request $request )
    {
        $args = [];
        $args['post_type'] = 'promotion';
        $args['posts_per_page'] = -1;

        if ($request['provinceId']) {
            $args['meta_query'][] =[
                'key' => 'restaurant_province_id',
                'value' => serialize((string) $request['provinceId']),
                'compare' => 'like',
            ];
        }

        // The Query
        $query = new WP_Query($args);

        // return banners
        $promotions = [];

        // The Loop
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $temp = $this->convertToStdClass($post);
                if ($temp->endTime > date_i18n('Y-m-d H:i:s')) {
                    $promotions[] = $temp;
                }
            }
        }

        return $promotions;
    } // end get promotion

    public function getPromotionDetail( WP_REST_Request $request )
    {
        if (isset($request['id']) && $request['id'] && get_post($request['id'])) {
            $post = get_post($request['id']);

            return $this->convertToStdClass($post);
        } else {
            return null;
        }
    } // end get promotion

} // end class

// init
$promotionPostType = new PromotionPostType();

