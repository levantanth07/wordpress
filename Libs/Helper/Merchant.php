<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\BookingService;
use GDelivery\Libs\Config;
use Predis\Client;

class Merchant {

    /**
     * @param \WP_Post $merchant
     * @param array $option
     * @return \stdClass
     */
    private static function convertMerchantToStdClass($merchant, $options = [])
    {
        $temp = new \stdClass();

        $temp->id = $merchant->ID;
        $temp->name = $merchant->post_title;
        $temp->banner = get_the_post_thumbnail_url($merchant->ID, 'shop_catalog');
        $temp->logo = get_field('merchant_logo', $temp->id) ? get_field('merchant_logo', $temp->id) : '';
        $temp->merchantType = get_field('merchant_type', $temp->id);
        $temp->restaurantCode = get_field('restaurant_code', $temp->id);
        $temp->rkOrderCategoryCode = get_field('restaurant_order_category_code', $temp->id);
        $temp->rkWaiterCode = get_field('restaurant_waiter_code', $temp->id);
        $temp->rkTableCode = get_field('restaurant_table_code', $temp->id);
        $temp->allowCallNewOrder = get_field('merchant_allow_calling_new_order', $temp->id);
        $temp->allowEmailNewOrder = get_field('merchant_allow_send_mail_new_order', $temp->id);
        $temp->allowGrabExpress = get_field('merchant_allow_grab_express', $temp->id);
        $temp->allowCutleryTool = get_field('merchant_allow_cutlery_tool', $temp->id);
        // todo hardcode for icook in HCM @since 24/10/2021; added by toan.nguyenduc@ggg.com.vn
        $temp->rkOrderCategoryCodeForIcook = get_field('merchant_order_category_code_for_icook', $temp->id);

        // merchant partner
        $temp->merchantAddress = get_field('merchant_address', $temp->id);
        $temp->merchantTelephone = get_field('merchant_telephone', $temp->id);
        $temp->merchantLongitude = get_field('merchant_longitude', $temp->id);
        $temp->merchantLatitude = get_field('merchant_latitude', $temp->id);

        $temp->merchantOpenTime1 = get_field('merchant_open_time_1', $temp->id);
        $temp->merchantCloseTime1 = get_field('merchant_close_time_1', $temp->id);

        $temp->merchantOpenTime2 = get_field('merchant_open_time_2', $temp->id);
        $temp->merchantCloseTime2 = get_field('merchant_close_time_2', $temp->id);
        $temp->minimumTimeToServe = get_field('minimum_time_to_serve', $temp->id);

        $temp->ratingPoint = get_field('rating_point', $temp->id);

        // Brand
        $brand = get_field('brand_id', $temp->id);
        if ($brand) {
            $temp->brand = Helper::getBrandInfo($brand);
        }

        // Get concept
        $getConcepts = get_field('concept_id', $temp->id);
        $concepts = [];
        if ($getConcepts) {
            foreach ($getConcepts as $item) {
                $objConcept = new \stdClass();
                $objConcept->id = $item->ID;
                $objConcept->name = $item->post_title;
                $objConcept->logo = get_the_post_thumbnail_url($item->ID, 'shop_catalog');
                $concepts[] = $objConcept;
            }
            $temp->concepts = $concepts;
        } else {
            $temp->concepts = null;
        }

        // Get rating
        $argRatings = [
            'post_type' => 'rating',
            'showposts' => 999,
            'post_status' => 'publish'
        ];

        $argRatings['meta_query'] = [
            [
                'key' => 'merchant_id',
                'value' => $merchant->ID,
                'compare' => '=',
            ],
        ];
        $loopRating = new \WP_Query($argRatings);
        if ($loopRating->have_posts()) {
            $ratings = [];
            foreach ($loopRating->posts as $onePost) {
                $ratings[] = [
                    'name' => $onePost->post_title,
                    'avatar' => get_the_post_thumbnail_url($onePost->ID, 'shop_catalog'),
                    'phoneNumber' => get_field('phone_number', $onePost->ID),
                    'point' => get_field('point', $onePost->ID),
                    'comment' => get_field('comment', $onePost->ID),
                    'create_at' => get_field('create_at', $onePost->ID),
                ];
            }
            $temp->totalRating = $loopRating->found_posts;
            $temp->ratings = $ratings;
        } else {
            $temp->totalRating = 0;
            $temp->ratings = [];
        }

        $bookingService = new BookingService();
        if ($temp->merchantType && $options === true) {
            $getMerchantInfo = $bookingService->getRestaurant($temp->restaurantCode);
            if ($getMerchantInfo->messageCode == Message::SUCCESS) {
                $temp->restaurant = $getMerchantInfo->result;
            } else {
                $temp->restaurant = null;
            }
        } elseif ($temp->restaurantCode && is_array($options)) {
            $getMerchantInfo = $bookingService->getRestaurant($temp->restaurantCode, $options);
            if ($getMerchantInfo->messageCode == Message::SUCCESS) {
                $temp->restaurant = $getMerchantInfo->result;
            } else {
                $temp->restaurant = null;
            }
        } else {
            $temp->restaurant = null;
        }
        if ($temp->restaurant != null) {
            $temp->restaurant->time = Helper::calculateTimeFromDistance($temp->restaurant->distance);
        }

        return $temp;
    }

    public static function formatMerchantInfo($merchant, $options = [])
    {
        return self::convertMerchantToStdClass($merchant, $options);
    }

    /**
     * Get list merchant by list merchant id
     *
     * @param array $query Query list merchant.
     *
     * @return Result
     */
    public static function getListMerchantFromListId($args, $options = []) {
        $res = new Result();
        $query = new \WP_Query($args);

        $listMerchants = [];
        foreach ($query->posts as $merchant) {
            $listMerchants[] = Helper::getMerchantInfo($merchant, $options);
        }

        if ($listMerchants) {
            $res->result = [
                'data' => $listMerchants,
                'total' => $query->found_posts,
                'currentPage' => (int) $args['paged'],
                'lastPage' => $query->max_num_pages,
                'perPage' => (int) $args['posts_per_page'],
            ];
            $res->numberOfResult = count($listMerchants);
            $res->message = 'Thành công';
            $res->messageCode = Message::SUCCESS;
        } else {
            $res->message = 'Không có sản phẩm nào!';
            $res->messageCode = Message::GENERAL_ERROR;
        }

        return $res;
    }

    public static function getSortedScoringMerchants($params = [])
    {
        $result = new Result();

        if (empty($params['merchantIds'])) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = 'Không có merchants';
            return $result;
        }

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS
        ]);
        $keyCache = sprintf('cms:scoring-merchants:%s', md5(json_encode($params)));
        $sortedScoringMerchants = $redis->get($keyCache);

        if ($sortedScoringMerchants) {
            $result->messageCode = Message::SUCCESS;
            $result->message = 'Thành công';
            $result->result = json_decode($sortedScoringMerchants);
            return $result;
        }

        $query = new \WP_Query([
            'post_type' => 'merchant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__in' => $params['merchantIds'],
            'orderby' => 'post__in',
        ]);

        $merchants = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $merchant) {
                $merchants[] = Helper::getMerchantInfo($merchant, $params);
            }
        }

        $result->messageCode = Message::SUCCESS;
        $result->message = 'Thành công';
        $result->result = $merchants;
        $redis->set($keyCache, \json_encode($merchants));
        return $result;
    }
} // end class
