<?php
/*
Api for banner
*/

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;

class BannerApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', 'banners/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getBanners"],
            ) );
        } );

        // inject popup banner to footer
//        add_action('wp_footer', [$this, 'registerPopup']);
    }

    // add api
    public function getBanners( WP_REST_Request $request )
    {
        $res = new Result();
        $args = [];
        //$args['banner-category'] = $request['position'];
        $args['post_type'] = 'banners';
        $args['posts_per_page'] = -1;

        if (isset($request['provinceId']) && $request['provinceId']) {
            $args['meta_query'][] = [
                'key' => 'provinceId',
                'value' => $request['provinceId'],
                'compare' => 'LIKE'
            ];
        }

        if (isset($request['position']) && $request['position'])  {
            $term = get_term_by('slug', $request['position'], 'banner-category');
            $args['tax_query'][] = [
                'taxonomy' => 'banner-category',
                'field'    => 'term_id',
                'terms'    => $term->term_id
            ];
        }

        if ($request['categoryId']) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $request['categoryId']
            ];
        }

        // The Query
        $getSliders = new WP_Query($args);

        // return banners
        $banners = [];

        // The Loop
        if ($getSliders->posts) {
            foreach ($getSliders->posts as $post) {
                $temp = new \stdClass();
                $temp->id = $post->ID;
                $temp->name = $post->post_title;
                $temp->desktopImage = get_field('banner_desktop_image', $post->ID);
                $temp->mobileImage = get_field('banner_mobile_image', $post->ID);
                $temp->webLink = get_field('web_link', $post->ID);
                $temp->linkType = get_field('banner_link_type', $post->ID);
                $temp->linkTarget = get_field('banner_link_target', $post->ID);
                $temp->order = get_field('banner_order', $post->ID);
                $temp->brandId = get_field('banner_brand_id', $post->ID);
                $temp->provinceId = get_field('provinceId', $post->ID);
                $temp->position = get_the_terms($post->ID, 'banner-category');

                $banners[] = $temp;
            }
        }

        // re-order banner
        usort(
                $banners,
                function ($a, $b) {
                return $a->order > $b->order;
            }
        );

        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $banners;

        Response::returnJson($res);
        die;
    }
}

// init
$bannerApi = new BannerApi();
