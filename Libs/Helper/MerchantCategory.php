<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class MerchantCategory {

    /**
     * @param \WP_Term $category
     *
     * @return \stdClass
     */
    public static function convertToStdClass($category)
    {
        $temp = new \stdClass();

        if ($category) {
            $categoryId = $category->term_id;
            $temp->logo = wp_get_attachment_url(get_term_meta($categoryId, 'thumbnail_id', true)) ?? '';
            $temp->url = get_term_link($category);
            $temp->slug = $category->slug;
            $temp->name = html_entity_decode($category->name);
            $temp->shortName = html_entity_decode(get_field('short_name', 'merchant-category_' . $categoryId));
            $merchant = get_field('merchant_id', 'merchant-category_' . $categoryId);
            $tempMerchant = new \stdClass();
            if ($merchant instanceof \WP_Post) {
                $tempMerchant->id = $merchant->ID;
                $tempMerchant->name = html_entity_decode($merchant->post_title);
                $tempMerchant->banner = get_the_post_thumbnail_url($merchant->ID, 'shop_catalog') ?: '';
            }

            $temp->merchant = $tempMerchant;
            $temp->isActive = (bool) get_field('is_active', 'merchant-category_' . $categoryId);
            $temp->layout = get_field('layout', 'merchant-category_' . $categoryId);
            $temp->id = $categoryId;
        } else {
            $temp->logo = '';
            $temp->url = '';
            $temp->slug = '';
            $temp->name = '';
            $temp->shortName = '';
            $temp->merchant = '';
            $temp->isActive = '';
            $temp->layout = '';
            $temp->id = 0;
        }

        return $temp;
    }

} // end class
