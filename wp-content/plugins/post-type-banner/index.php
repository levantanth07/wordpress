<?php
/*
Plugin Name: Post Type Banner
Plugin URI: http://ggg.com.vn/
Description: Manager banners for banner and popup.
Author: thienhaxanh2405 <toan.nguyenduc@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

class BannerPostType {

    public function __construct()
    {
        // register tgs banner post type
        add_action( 'init', [$this, 'registerPostType'], 0 );

        add_action( 'init', [$this, 'taxonomiesForBanners'], 0 );

        //add_action( 'init', [$this, 'registerProductCatForBanner'], 11 );

        // filter position
        add_action( 'restrict_manage_posts', [$this, "restrictManagePostType"] );

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route(
                'banners/v1',
                '/list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getBanners"],
                    'permission_callback' => function() {
                        return '';
                    }
                ]
            );
        } );

        // inject popup banner to footer
        add_action('wp_footer', [$this, 'registerPopup']);
    }

    public function registerProductCatForBanner()
    {
        register_taxonomy_for_object_type('product_cat', 'banners');
    }

    public function registerPostType()
    {
        $post_type = 'banners';
        $args = [
            'label' => 'Banners',
            'labels' => [
                'name' => 'Banners',
                'singular_name' => 'Banners',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Banners',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-images-alt2',
            'rewrite' => ['slug' => 'banners'],
            'supports' => ['title'],
            'taxonomies' => ['banner-category', ],
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

    public function taxonomiesForBanners()
    {
        $labels = array(
            'name'              => 'Vị trí',
            'menu_name'         => 'Vị trí',
        );
        $args = array(
            'labels' => $labels,
            'show_in_nav_menus' => true,
            'show_ui' => true,
            'hierarchical' => true,
        );
        register_taxonomy( 'banner-category', 'banners', $args );
    }

    public function restrictManagePostType()
    {
        global $typenow; global $post_type;
        $taxonomy = 'banner-category';
        if (
                $typenow != 'page'
                && $typenow != 'post'
                && $post_type == 'banners'
        ){
            $filters = array($taxonomy);
            foreach ($filters as $tax_slug) {
                $tax_obj = get_taxonomy($tax_slug);
                $tax_name = $tax_obj->labels->name;
                $terms = get_terms($tax_slug);
                echo "<select id=\"{$tax_slug}\" class=\"postform\" name=\"{$tax_slug}\">";
                echo "<option value=\"\">Show All {$tax_name}</option>";
                foreach ($terms as $term) { ?>
                    <option <?=(isset($_GET[$tax_slug]) && $term->slug == $_GET[$tax_slug] ? 'selected' : '')?> value="<?=$term->slug?>"><?=$term->name?> (<?=$term->count?>)</option>
                    <?php
                }
                echo "</select>";
            } // end foreach filters
        } // end if
    }

    // add api
    public function getBanners( WP_REST_Request $request )
    {
        $args = [];
        $args['banner-category'] = $request['position'];
        $args['post_type'] = 'banners';
        $args['posts_per_page'] = -1;
        if ($request['provinceId']) {
            $args['meta_query'] = [
                [
                    'key' => 'banner_display_in_provinces',
                    'value' => serialize((string) $request['provinceId']),
                    'compare' => 'like',
                ]
            ];
        }

        // The Query
        $the_query = new WP_Query($args);

        // return banners
        $banners = [];

        // The Loop
        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                if (get_post_status() == 'publish') {
                    $temp['id'] = get_the_ID();
                    $temp['title'] = html_entity_decode(get_the_title());
                    $temp['date'] = get_the_date('Y-m-d H:i:s');
                    $temp['status'] = get_post_status();
                    $temp['thumbnail'] = get_the_post_thumbnail_url(get_the_ID(), 'large') ? get_the_post_thumbnail_url(get_the_ID(), 'large') : '';
                    $temp['bannerLinkType'] = get_post_meta(get_the_ID(), 'banner_link_type') ? (int) get_post_meta(get_the_ID(), 'banner_link_type')[0] : 0;
                    $temp['bannerLinkTarget'] = get_post_meta(get_the_ID(), 'banner_link_target') ? get_post_meta(get_the_ID(), 'banner_link_target')[0] : '';
                    $temp['order'] = get_post_meta(get_the_ID(), 'banner_order') ? get_post_meta(get_the_ID(), 'banner_order')[0] : '';
                    $temp['displayInProvince'] = get_field('banner_display_in_provinces', get_the_ID());
                    $temp['displayType'] = get_field('banner_display_type', get_the_ID()) ?? 'fixed';
                    $temp['coordinateX'] = (int) get_field('banner_coordinate_x', get_the_ID());
                    $temp['coordinateY'] = (int) get_field('banner_coordinate_y', get_the_ID());

                    $banners[] = $temp;
                }
            }
        }

        // re-order banner
        usort(
                $banners,
                function ($a, $b) {
                return $a['order'] > $b['order'];
            }
        );

        return $banners;

    }

    public function registerPopup()
    {
        require_once plugin_dir_path(__FILE__).'popup-banner.php';
    }
}

// init
$bannerPostType = new BannerPostType();
