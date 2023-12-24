<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class Banner{

    public static function getBanners($position, $provinceId)
    {
        // home slider
        $paramsSliders = [
            'post_type' => 'banners',
            'post_status'=>'publish',
            'posts_per_page'=> -1,
            'banner-category' => $position,
            'meta_query' => [
                [
                    'key' => 'banner_display_in_provinces',
                    'value' => serialize((string) $provinceId),
                    'compare' => 'like',
                ]
            ]
        ];

        $getSliders = new \WP_Query($paramsSliders);

        if ($getSliders->posts) {
            foreach ($getSliders->posts as $post) {
                $temp = new \stdClass();
                $temp->id = $post->ID;
                $temp->name = $post->post_title;
                $temp->desktopImage = get_field('banner_desktop_image', $post->ID);
                $temp->mobileImage = get_field('banner_mobile_image', $post->ID);
                $temp->linkType = get_field('banner_link_type', $post->ID);
                $temp->linkTarget = get_field('banner_link_target', $post->ID);
                $temp->order = get_field('banner_order', $post->ID);
                $temp->displayInProvinceIds = get_field('banner_display_in_provinces', $post->ID);
                $temp->brandId = get_field('brandId', $post->ID);

                $sliders[] = $temp;
            }
        } else {
            $sliders = [];
        }

        wp_reset_query();

        return $sliders;
    }

} // end class
