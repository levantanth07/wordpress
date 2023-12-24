<?php


namespace GDelivery\Libs\Helper;


class PaymentMethod
{
    public static function getListPaymentMethod($params = []) {
        $args = [];
        $args['post_type'] = 'payment_methods';
        $args['posts_per_page'] = -1;
        $args['post_status'] = 'all';
        $args['meta_key'] = 'payment_method_order';
        $args['orderby'] = 'meta_value';
        $args['order'] = 'ASC';

        if (isset($params['totalPaySum']) && $params['totalPaySum'] == 0) {
            $args['meta_query'][] = [
                'key' => 'payment_method_code',
                'value' => 'COD',
                'compare' => '='
            ];
        } else {
            if (isset($params['isActive']) && $params['isActive']) {
                $args['post_status'] = 'publish';
            }
        }

        if (isset($params['code'])) {
            $args['meta_query'][] = [
                'key' => 'payment_method_code',
                'value' => $params['code'],
                'compare' => '='
            ];
        }

        // The Query
        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return false;
        }

        return $query->posts;
    }
}