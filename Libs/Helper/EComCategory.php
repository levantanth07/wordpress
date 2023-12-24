<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class EComCategory {

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
            $temp->logo = wp_get_attachment_url(get_term_meta($categoryId, 'thumbnail', true)) ?: '';
            $temp->slug = $category->slug;
            $temp->name = html_entity_decode($category->name);
            $temp->shortName = html_entity_decode(get_field('short_name', 'merchant-category_' . $categoryId) ?? '');
            $temp->isActive = (bool) get_field('is_active', 'merchant-category_' . $categoryId);
            $temp->id = $categoryId;
        } else {
            $temp->logo = '';
            $temp->slug = '';
            $temp->name = '';
            $temp->shortName = '';
            $temp->isActive = false;
            $temp->id = 0;
        }

        return $temp;
    }

} // end class
