<?php
/*
Api for brand

Get with product_cat

*/

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Product;
use GDelivery\Libs\Helper\Helper;

class ApiFlashSale extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/flash-sale/current', array(
                'methods' => 'GET',
                'callback' => [$this, "currentFlashSale"],
            ) );

            register_rest_route( 'api/v1', '/flash-sale/current/merchant/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "currentFlashSaleMerchant"],
            ) );

            register_rest_route( 'api/v1', '/flash-sale/(?P<id>\d+)/items', array(
                'methods' => 'GET',
                'callback' => [$this, "getItemsFlashSale"],
            ) );
        } );
    }

    public function currentFlashSale(WP_REST_Request $request)
    {
        $res = new Result();
        $provinceId = $request['provinceId'];
        $screen = $request['screen'];

        $args = [
            'post_type' => 'flash_sale',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'paged' => 1,
            'orderby' => 'ID',
            'order' => 'DESC',
        ];
        if ($provinceId) {
            $args['meta_query'][] = [
                'key' => 'province_id',
                'value' => $provinceId,
                'compare' => '='
            ];
        }
        if ($screen) {
            $args['meta_query'][] = [
                'key' => 'screen',
                'value' => $screen,
            ];
        }

        $args['meta_query'][] = [
            'key' => 'is_active',
            'value' => true,
        ];

        $currentDateTime = date_i18n('Y-m-d H:i:s');

        $query = new \WP_Query($args);
        $flashSaleInfo = null;
        foreach ($query->posts as $flashSale) {
            $flashSaleId = $flashSale->ID;
            $rangeTimeShowFlashSale = get_field('rangeTimeShowFlashSale', $flashSaleId);
            $rangeTimeActiveFlashSale = get_field('rangeTimeActiveFlashSale', $flashSaleId);
            $availableDates = get_field('available_date', $flashSaleId);
            $availableTimes = get_field('available_time', $flashSaleId);

            foreach ($rangeTimeShowFlashSale as $key => $dateTime) {
                $startTime = $dateTime->start;
                $endTime = $dateTime->end;

                if ($currentDateTime >= $startTime && $currentDateTime <= $endTime) {
                    $flashSaleInfo = new \stdClass();
                    $flashSaleInfo->id = $flashSaleId;
                    $flashSaleInfo->name = $flashSale->post_title;
                    $flashSaleInfo->startTime = $rangeTimeActiveFlashSale[$key]->start;
                    $flashSaleInfo->endTime = $rangeTimeActiveFlashSale[$key]->end;
                    $flashSaleInfo->isShow = true;
                    $flashSaleInfo->screen = get_field('screen', $flashSaleId);

                    $flashSaleInfo->isActive = false;
                    $date = date_i18n('d-m-Y', strtotime($currentDateTime));
                    $time = date_i18n('H:i', strtotime($currentDateTime));
                    if (in_array($date, $availableDates) && in_array($time, $availableTimes)) {
                        $flashSaleInfo->isActive = true;
                    }

                    $productFlashSale = get_field('productFlashSale', $flashSaleId);
                    $products = [];
                    $fiveProducts = array_slice($productFlashSale, 0, 5, true);
                    foreach ($fiveProducts as $product) {
                        $temp = Product::getProductInfo($product->id);
                        $promotion = new \stdClass();
                        $promotion->id = (int) $product->promotionId;
                        $temp->promotion = $promotion;
                        $merchant = get_field('merchant_id', $product->id);
                        $temp->merchant = Helper::getMerchantInfo($merchant);
                        $temp->quantity = (int) $product->quantity;
                        $temp->soldQuantityFake = (int) $product->soldQuantityFake;
                        $products[] = $temp;
                    }
                    $flashSaleInfo->items = $products;
                    break;
                }
            }
            if (!empty($flashSaleInfo)) {
                break;
            }
        }

        if (!empty($flashSaleInfo)) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $flashSaleInfo;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có chương trình flash sale';
        }

        Response::returnJson($res);
        die;
    }

    public function currentFlashSaleMerchant(WP_REST_Request $request)
    {
        $res = new Result();
        $merchantId = $request['id'];
        $sceneId = get_field('sceneId', $merchantId);
        $provinceId = get_field('province_id', $merchantId);
        $screen = $sceneId == 1 ? 'goi_do_an' : 'di_cho';

        $args = [
            'post_type' => 'flash_sale',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'paged' => 1,
            'orderby' => 'ID',
            'order' => 'DESC',
        ];
        if ($provinceId) {
            $args['meta_query'][] = [
                'key' => 'province_id',
                'value' => $provinceId,
                'compare' => '='
            ];
        }
        if ($screen) {
            $args['meta_query'][] = [
                'key' => 'screen',
                'value' => $screen,
            ];
        }

        $args['meta_query'][] = [
            'key' => 'is_active',
            'value' => true,
        ];

        $currentDateTime = date_i18n('Y-m-d H:i:s');

        $query = new \WP_Query($args);
        $flashSaleInfo = null;
        foreach ($query->posts as $flashSale) {
            $flashSaleId = $flashSale->ID;
            $rangeTimeShowFlashSale = get_field('rangeTimeShowFlashSale', $flashSaleId);
            $rangeTimeActiveFlashSale = get_field('rangeTimeActiveFlashSale', $flashSaleId);
            $availableDates = get_field('available_date', $flashSaleId);
            $availableTimes = get_field('available_time', $flashSaleId);

            foreach ($rangeTimeShowFlashSale as $key => $dateTime) {
                $startTime = $dateTime->start;
                $endTime = $dateTime->end;

                if ($currentDateTime >= $startTime && $currentDateTime <= $endTime) {
                    $flashSaleInfo = new \stdClass();
                    $flashSaleInfo->id = $flashSaleId;
                    $flashSaleInfo->name = $flashSale->post_title;
                    $flashSaleInfo->startTime = $rangeTimeActiveFlashSale[$key]->start;
                    $flashSaleInfo->endTime = $rangeTimeActiveFlashSale[$key]->end;
                    $flashSaleInfo->isShow = true;
                    $flashSaleInfo->screen = get_field('screen', $flashSaleId);

                    $flashSaleInfo->isActive = false;
                    $date = date_i18n('d-m-Y', strtotime($currentDateTime));
                    $time = date_i18n('H:i', strtotime($currentDateTime));
                    if (in_array($date, $availableDates) && in_array($time, $availableTimes)) {
                        $flashSaleInfo->isActive = true;
                    }

                    $productFlashSale = get_field('productFlashSale', $flashSaleId);
                    $products = [];
                    foreach ($productFlashSale as $product) {
                        $productId = $product->id;
                        $merchant = get_field('merchant_id', $productId);
                        if ($merchantId != $merchant->ID) {
                            continue;
                        }
                        $temp = Product::getProductInfo($product->id);
                        $promotion = new \stdClass();
                        $promotion->id = (int) $product->promotionId;
                        $temp->promotion = $promotion;
                        $merchant = get_field('merchant_id', $product->id);
                        $temp->merchant = Helper::getMerchantInfo($merchant);
                        $temp->quantity = (int) $product->quantity;
                        $temp->soldQuantityFake = (int) $product->soldQuantityFake;
                        $products[] = $temp;
                    }
                    $flashSaleInfo->items = $products;
                    break;
                }
            }
            if (!empty($flashSaleInfo)) {
                break;
            }
        }

        if (!empty($flashSaleInfo)) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $flashSaleInfo;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có chương trình flash sale';
        }

        Response::returnJson($res);
        die;
    }

    public function getItemsFlashSale(WP_REST_Request $request)
    {
        $res = new Result();
        $flashSaleId = $request['id'];
        $page = $request['page'] ?? 1;
        $perPage = $request['perPage'] ?? -1;

        $args = [
            'post_type' => 'flash_sale',
            'post_status' => 'publish',
            'p' => $flashSaleId,
        ];

        $query = new \WP_Query($args);
        $flashSaleInfo = $query->posts[0];

        $productFlashSale = get_field('productFlashSale', $flashSaleId);
        $arrProducts = [];
        if ($perPage == -1) {
            $products = $productFlashSale;
        } else {
            $products = array_slice($productFlashSale, $page - 1, $perPage, true);
        }
        foreach ($products as $product) {
            $temp = Product::getProductInfo($product->id);
            $promotion = new \stdClass();
            $promotion->id = (int) $product->promotionId;
            $temp->promotion = $promotion;
            $merchant = get_field('merchant_id', $product->id);
            $temp->merchant = Helper::getMerchantInfo($merchant);
            $temp->quantity = (int) $product->quantity;
            $temp->soldQuantityFake = (int) $product->soldQuantityFake;
            $arrProducts[] = $temp;
        }

        if (!empty($flashSaleInfo)) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $arrProducts;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

}

// init
$apiFlashSale = new ApiFlashSale();
