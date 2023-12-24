<?php

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Helper;
use GDelivery\Libs\Helper\Product;

class HelperFlashSale {

    public static function getProductsByProvinceAndKeyword($provinceId, $postId = null, $options = []) {
        $res = new Result();

        $args = [
            'post_type' => 'merchant',
            'fields' => 'ids',
            'post_status' => 'publish',
            'posts_per_page'=> -1,
            'page' => 1,
            'meta_query' => [
                [
                    'key' => 'province_id',
                    'value' => $provinceId,
                ]
            ]
        ];
        $merchantIds = get_posts($args);

        $products = [];
        if (!empty($merchantIds)) {
            $params = [
                'post_type' => 'product',
                'post_status'=>'publish',
                's' => $options['keyWord'] ?? '',
                'posts_per_page'=> -1,
                'page' => 1,
                'post__not_in' => $options['productIdSelected'] ?? [],
                'meta_query' => [
                    [
                        'key' => 'merchant_id',
                        'value' => $merchantIds,
                        'compare' => 'IN'
                    ],
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'topping',
                        'operator' => 'NOT IN'
                    ],
                ],
            ];

            $query = new \WP_Query($params);
            $products = $query->posts;
        }

        $arrProducts = [];
        foreach ($products as $product) {
            $temp = new \stdClass();
            $temp->id = $product->ID;
            $temp->name = $product->post_title;

            $currentProductId = $product->ID;
            $parentId = $product->ID;
            $productInfo = wc_get_product($currentProductId);
            if ($productInfo->is_type('variable')) {
                $variations = $productInfo->get_available_variations();
                if (isset($variations[0])) {
                    $currentProductId = $variations[0]['variation_id'];
                }
            }
            if ($productInfo->is_type('variation')) {
                $parentId = $productInfo->get_parent_id();
            }
            $temp->regularPrice = (float) get_field('_regular_price',$currentProductId);
            $temp->textRegularPrice = number_format($temp->regularPrice);
            $merchantId = get_field('merchant_id', $parentId);

            $merchant = Helper::getMerchantInfo(get_post($merchantId));
            $temp->merchantName = $merchant->name;

            $temp->nameFormated = $temp->name . '<br/>' . $merchant->name;

            $arrProducts[] = $temp;
        }

        if ($arrProducts) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $arrProducts;
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        return $res;
    }

    public static function getDateAvailable($startDate, $endDate, $options) {
        $begin = new DateTime(date_i18n('Y-m-d', strtotime($startDate)));
        $end = new DateTime(date_i18n('Y-m-d', strtotime('+1 day', strtotime($endDate))));
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);
        $arrDateAvailable = [];
        if ($options['availableType'] == 'day') {
            $arrAvailableValue = $options['availableValue'];
            foreach ($period as $dt) {
                $day = strtolower($dt->format("l"));
                $date = $dt->format("d-m-Y");

                if (in_array($day, $arrAvailableValue)) {
                    $arrDateAvailable[] = $date;
                }
            }
        } elseif ($options['availableType'] == 'dates') {
            $arrAvailableValue = explode(',', $options['availableValue']);
            foreach ($period as $dt) {
                $date = $dt->format("d-m-Y");

                if (in_array($date, $arrAvailableValue)) {
                    $arrDateAvailable[] = $date;
                }
            }
        } else {
            foreach ($period as $dt) {
                $arrDateAvailable[] = $dt->format("d-m-Y");
            }
        }

        return $arrDateAvailable;
    }

    public static function getTimeAvailable($startTime, $endTime, $options = []) {
        // Format and save time available
        $arrTimeAvailable = [];
        if (!empty($startTime)) {
            foreach ($startTime as $k => $val) {
                $begin = new DateTime(date_i18n('H:i', strtotime($val)));
                $end = new DateTime(date_i18n('H:i', strtotime($endTime[$k])));

                $interval = DateInterval::createFromDateString('1 minutes');
                $period = new DatePeriod($begin, $interval, $end);
                foreach ($period as $dt) {
                    $arrTimeAvailable[] = $dt->format("H:i");
                }
            }
        } else {
            $begin = new DateTime('00:00');
            $end = new DateTime('23:59');

            $interval = DateInterval::createFromDateString('1 minutes');
            $period = new DatePeriod($begin, $interval, $end);
            foreach ($period as $dt) {
                $arrTimeAvailable[] = $dt->format("H:i");
            }
        }

        return $arrTimeAvailable;
    }

} //end class

// init class
$helper = new HelperFlashSale();
