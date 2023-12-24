<?php
/*
Api for brand

Get with product_cat

*/

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Product;
use GDelivery\Libs\Helper\Helper;

class ApiBlockDynamic extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/dynamic-listing/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getGroupDynamic"],
            ) );
        } );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/dynamic-listing/(?P<id>\d+)/item-ids', array(
                'methods' => 'GET',
                'callback' => [$this, "getItemIds"],
            ) );
        } );
    }

    public function getGroupDynamic(WP_REST_Request $request)
    {
        $res = new Result();
        $perPage = $request['perPage'] ?? -1;
        $page = $request['page'] ?? 1;
        $provinceId = $request['provinceId'];
        $screen = $request['screen'];

        $args = [
            'post_type' => 'block_dynamic',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'orderby' => 'meta_value_num',
            'meta_key' => 'order',
            'order' => 'ASC',
        ];

        if ($screen == 'home') {
            $args['meta_query'][] = [
                'key' => 'show_on_home',
                'value' => true,
            ];
        } elseif($screen == 'merchant_detail') {
            $args['meta_query'][] = [
                'key' => 'screen',
                'value' => $screen,
            ];
            $args['meta_query'][] = [
                'key' => 'merchant',
                'value' => $request['merchantId'],
            ];
        } else {
            $args['meta_query'][] = [
                'key' => 'screen',
                'value' => $screen,
            ];
        }
        if ($provinceId && $screen != 'merchant_detail') {
            $args['meta_query'][] = [
                'key' => 'province_id',
                'value' => $provinceId,
                'compare' => '='
            ];
        }

//         Thêm các điều kiện: trong khoảng ngày, thứ hoặc các ngày cụ thể, khung giờ nếu có
        $date = date_i18n('d-m-Y');
        $time = date_i18n('H:i');
        $args['meta_query'][] = [
            'key' => 'available_date',
            'value' => serialize($date),
            'compare' => 'LIKE'
        ];
        $args['meta_query'][] = [
            'key' => 'available_time',
            'value' => serialize($time),
            'compare' => 'LIKE'
        ];

        $query = new \WP_Query($args);
        $arrGroup = [];
        foreach ($query->posts as $group) {
            $temp = new stdClass();
            $id = $group->ID;
            $temp->id = $id;
            $temp->name = $group->post_title;
            $temp->shortName = get_field('short_name', $id);
            $temp->description = get_field('description', $id);
            $temp->type = get_field('type', $id);
            $temp->showOnHome = get_field('show_on_home', $id);
            $temp->screen = get_field('screen', $id);
            $temp->startDate = get_field('start_date', $id);
            $temp->endDate = get_field('end_date', $id);
            $temp->availableType = get_field('available_type', $id);
            $temp->availableValue = get_field('available_value', $id);
            $temp->rangeTime = get_field('range_time', $id);
            $temp->thumbnail = get_field('thumbnail', $id) ?: '';
            $temp->banner = get_field('banner', $id) ?: '';
            $temp->order = get_field('order', $id);
            $arrGroup[] = $temp;
        }

        if (!empty($arrGroup)) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $arrGroup;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có nhóm';
        }

        Response::returnJson($res);
        die;
    }

    public function getItems(WP_REST_Request $request)
    {
        $res = new Result();
        $perPage = $request['perPage'] ?? 8;
        $page = $request['page'] ?? 1;

        $blockId = $request['id'];
        $blockType = get_field('type', $blockId);
        $itemIds = get_field('list_item_sorted', $blockId);
        $screen = get_field('screen', $blockId);
        $arrItemIds = explode(',', $itemIds);
        $arrKeyItemIds = array_flip($arrItemIds);

        if ($blockType == 'merchant') {
            $args = [
                'post_type' => 'merchant',
                'post_status' => 'publish',
                'posts_per_page' => $perPage,
                'post__in' => $arrItemIds,
                'paged' => $page,
            ];
            $query = new \WP_Query($args);
            $items = [];
            $options = [];
            if ($request['latitude'] && $request['longitude']) {
                $options['fromLatitude'] = $request['latitude'];
                $options['fromLongitude'] = $request['longitude'];
            }
            foreach ($query->posts as $merchant) {
                $items[$arrKeyItemIds[$merchant->ID]] = Helper::getMerchantInfo($merchant, $options);
            }
        } elseif ($blockType == 'product' || $screen == 'merchant_detail') {
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $perPage,
                'post__in' => explode(',', $itemIds),
                'paged' => $page,
                'meta_query' => Product::getDefaultMetaQuery(),
            ];

            if ($request['categoryIds']) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'ecom-category',   // taxonomy name
                        'field' => 'term_id',           // term_id, slug or name
                        'terms' => explode(',', $request['categoryIds']),                  // term id, term slug or term name
                    )
                );
            }

            if ($request['orderBy']) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'ecom-category',   // taxonomy name
                        'field' => 'term_id',           // term_id, slug or name
                        'terms' => explode(',', $request['categoryIds']),                  // term id, term slug or term name
                    )
                );
            }

            $query = new \WP_Query($args);
            $items = [];
            foreach ($query->posts as $product) {
                $items[$arrKeyItemIds[$product->ID]] = \GDelivery\Libs\Helper\Product::formatProductInfo($product);
            }
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có dữ liệu';

            Response::returnJson($res);
            die;
        }

        if ($items) {
            ksort($items);
            $items = array_values($items);
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = [
                'data' => $items,
                'total' => $query->found_posts,
                'currentPage' => (int) $page,
                'lastPage' => $query->max_num_pages,
                'perPage' => (int) $perPage,
                'info' => [
                    'blockType' => $blockType,
                    'screen' => $screen,
                ]
            ];
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

    public function getItemIds(WP_REST_Request $request)
    {
        $res = new Result();

        $blockId = $request['id'];
        $blockType = get_field('type', $blockId);
        $itemIds = get_field('list_item_sorted', $blockId);

        if ($blockType == 'product') {
            $args = array(
                'post_type' => 'product',
                'post__in' => explode(',', $itemIds),
                'fields' => 'ids'
            );
            $args['meta_query'] = Product::getDefaultMetaQuery();
        } else {
            $args = array(
                'post_type' => 'merchant',
                'post_status'=>'publish',
                'fields' => 'ids'
            );
        }
        $query = new \WP_Query($args);

        $arrItemIds = $query->posts;

        $screen = get_field('screen', $blockId);
        if ($screen == 'merchant_detail') {
            $merchantId = get_field('merchant', $blockId);
            $sceneId = (int) get_field('sceneId', $merchantId);
        } elseif ($screen == 'goi_do_an') {
            $sceneId = 1;
        } else {
            // screen di_cho
            $sceneId = 2;
        }

        if ($itemIds) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = [
                'info' => [
                    'ids' => $arrItemIds,
                    'blockType' => $blockType,
                    'screen' => $screen,
                    'sceneId' => $sceneId,
                ]
            ];
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }
}

// init
$blockDynamicApi = new ApiBlockDynamic();
