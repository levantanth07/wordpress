<?php

namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\BookingService;

class ScoringMerchant
{
    public static function getListMerchants($params)
    {
        $result = new Result();
        $result->messageCode = Message::SUCCESS;
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 20;
        $query = new \WP_Query([
            'post_type' => 'merchant',
            'post_status' => array_values(get_post_stati()),
            'posts_per_page' => $perPage,
            'paged' => $page,
        ]);

        if (! $query->have_posts()) {
            $result->message = 'Ko có dữ liệu nhà hàng';
            return $result;
        }

        $merchants = [];
        foreach ($query->posts as $merchant) {
            $merchants[] = self::convertMerchantToStdClass($merchant);
        }

        $result->result = $merchants;
        $result->numberOfResult = count($merchants);
        $result->total = $query->found_posts;
        $result->lastPage = $query->max_num_pages;
        $result->message = 'Thành công';
        return $result;
    }

    public static function getListSpecificMerchants($params)
    {
        $result = new Result();
        $result->messageCode = Message::SUCCESS;
        if (empty($merchantIds = $params['merchantIds'] ?? [])) {
            $result->message = 'Ko có dữ liệu nhà hàng';
            return $result;
        }

        $query = new \WP_Query([
            'post_type' => 'merchant',
            'post_status' => array_values(get_post_stati()),
            'post__in' => $merchantIds,
        ]);

        if (! $query->have_posts()) {
            $result->message = 'Ko có dữ liệu nhà hàng';
            return $result;
        }

        $merchants = [];
        foreach ($query->posts as $merchant) {
            $merchants[] = self::convertMerchantToStdClass($merchant);
        }

        $result->result = $merchants;
        $result->numberOfResult = count($merchants);
        $result->total = $query->found_posts;
        $result->lastPage = $query->max_num_pages;
        $result->message = 'Thành công';
        return $result;
    }

    public static function getDetailMerchant($id)
    {
        $result = new Result();
        $query = new \WP_Query([
            'post_type' => 'merchant',
            'post_status'=> array_values(get_post_stati()),
            'p' => $id,
        ]);

        if (! $query->have_posts()) {
            $result->messageCode = Message::NOT_FOUND;
            $result->message = 'Merchant không tồn tại';
            return $result;
        }

        $result->messageCode = Message::SUCCESS;
        $result->message = 'Thành công';
        $result->result = self::convertMerchantToStdClass($query->posts[0]);
        return $result;
    }

    private static function convertMerchantToStdClass($merchant)
    {
        $merchantObject = new \stdClass();
        $merchantObject->id = $merchant->ID;
        $restaurantCode = get_field('restaurant_code', $merchant->ID);
        if ($restaurantCode) {
            $bookingService = new BookingService();
            $restaurant = $bookingService->getRestaurant($restaurantCode);
            if ($restaurant->messageCode == Message::SUCCESS) {
                $merchantObject->restaurant = $restaurant->result;
            }
        }

        $merchantObject->openTime1 = get_field('merchant_open_time_1', $merchant->ID);
        $merchantObject->closeTime1 = get_field('merchant_close_time_1', $merchant->ID);
        $merchantObject->openTime2 = get_field('merchant_open_time_2', $merchant->ID);
        $merchantObject->closeTime2 = get_field('merchant_close_time_2', $merchant->ID);
        $merchantObject->status = $merchant->post_status;
        $merchantObject->provinceId = ($provinceId = get_field('province_id', $merchant->ID)) ? (int) $provinceId : null;
        $merchantObject->numberOfOrders = (int) get_field('number_of_order', $merchant->ID);
        $merchantObject->brandId = ($brandId = get_post_meta($merchant->ID, 'brand_id', true)) ? (int) $brandId : null;
        $merchantObject->blockDynamics = self::getBlockDynamicsByMerchant($merchant);
        $merchantObject->merchantInfo = Helper::getMerchantInfo($merchant);

        self::addInformationOfProducts($merchant, $merchantObject);

        return $merchantObject;
    }

    /**
     * @param \WP_Post $merchant
     * @return array|int[]
     */
    protected static function getBlockDynamicsByMerchant($merchant)
    {
        global $wpdb;
        $blockDynamics = $wpdb->get_results($wpdb->prepare("
	        SELECT wp_posts.ID FROM wp_posts
            INNER JOIN wp_postmeta AS pm1 ON wp_posts.ID = pm1.post_id
            INNER JOIN wp_postmeta AS pm2 ON wp_posts.ID = pm2.post_id
	        WHERE wp_posts.post_type = %s
	            AND wp_posts.post_status = %s
	            AND (pm1.meta_key = %s AND pm1.meta_value = %s)
	            AND (pm2.meta_key = %s AND FIND_IN_SET(%d, pm2.meta_value))
		   ", [
            'block_dynamic',
            'publish',
            'type',
            'merchant',
            'list_item_sorted',
            $merchant->ID,
        ]));

        if (empty($blockDynamics)) return [];
        return array_map(fn ($blockDynamic) => (int) $blockDynamic->ID, $blockDynamics);
    }

    protected static function addInformationOfProducts($merchant, $merchantObject)
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'is_master_product',
                    'value' => 0,
                    'compare' => '=',
                ],
                [
                    'key' => 'merchant_id',
                    'value' => $merchant->ID,
                    'compare' => '=',
                ],
            ],
            'tax_query' => Product::getDefaultTaxQuery(),
        ]);

        if (! $query->have_posts()) return;
        $productTags = [];
        $productCategories = [];
        $arrEComCategoryId = [];
        foreach ($query->posts as $product) {
            array_push($productTags, ...ScoringProduct::getTags($product->ID));
            $productCategories[] = ScoringProduct::getProductCategory($product->ID);
            $terms = get_the_terms($product->ID,'ecom-category');
            if ($terms) {
                foreach ($terms as $term) {
                    $arrEComCategoryId[] = $term->term_id;
                }
            }
        }
        $merchantObject->eComCategoryIds = array_values(array_unique($arrEComCategoryId));

        $merchantObject->productTags = array_values(array_unique($productTags, SORT_REGULAR));
        $productCategoryDays = [];
        $productCategoryMeals = [];
        foreach ($productCategories as $productCategory) {
            array_push($productCategoryDays, ...$productCategory->day);
            array_push($productCategoryMeals, ...$productCategory->meal);
        }

        $merchantObject->productCategoryDays = array_values(array_unique($productCategoryDays));
        $merchantObject->productCategoryMeals = array_values(array_unique($productCategoryMeals));
    }
}
