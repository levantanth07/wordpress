<?php
/*
Api for Product

*/

use GDelivery\Libs\Helper\Category;
use GDelivery\Libs\Helper\Product;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Result;
use Abstraction\Object\ApiMessage;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\ScoringProduct;

class ProductApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/product/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "getDetailProduct"],
            ) );
            register_rest_route( 'api/v1', '/switch-product/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "getSwitchProduct"],
            ) );
            register_rest_route( 'api/v1', '/apply-switch-product', array(
                'methods' => 'POST',
                'callback' => [$this, "applySwitchProduct"],
            ) );
            register_rest_route( 'api/v1', '/remove-apply-product', array(
                'methods' => 'POST',
                'callback' => [$this, "removeApplyProduct"],
            ) );
            register_rest_route( 'api/v1', '/voucher/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "getDetailVoucher"],
            ) );

            register_rest_route( 'api/v1', '/product/rkcode', array(
                'methods' => 'post',
                'callback' => [$this, "getDetailProductByRkCode"],
            ) );

            register_rest_route( 'api/v1', '/product/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getListProducts"],
            ) );

            register_rest_route( 'api/v1', '/product/upsell/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getListProductUpSell"],
            ) );

            register_rest_route( 'api/v1', '/product/from/ids', array(
                'methods' => 'GET',
                'callback' => [$this, "getListProductFromListId"],
            ) );

            register_rest_route( 'api/v1', '/tag', array(
                'methods' => 'GET',
                'callback' => [$this, "getListTag"],
            ) );

            register_rest_route(
                'api/v1',
                '/product/groups',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListGroups"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-sorted-in-group',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListSortedProductInGroups"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-sorted-home',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListSortedProductHome"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/post/(?P<slug>\S+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getPageDetail"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/brand/(?P<brandId>\d+)/shipping-tax',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getShippingTax"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-by-merchant/(?P<merchantId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListProductByMerchant"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/same-buy/(?P<productId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListSameBuyProduct"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/same-category/(?P<productId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListSameCategoryProduct"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/group-by-merchant/(?P<merchantId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListGroupByMerchant"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-by-group/(?P<groupId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListProductByGroup"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/upsells/(?P<productId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListUpsellsProduct"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/suggestion-for-you',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListSuggestionProduct"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-by-category/(?P<categoryId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListProductByCategory"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/list-by-merchant-category/(?P<merchantCategoryId>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getListProductByMerchantCategory"]
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/scoring/(?P<id>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getDetailScoringProduct']
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/scoring/list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getListScoringProducts']
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/scoring/specific-list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getListSpecificProducts']
                ]
            );

            register_rest_route(
                'api/v1',
                '/product/scoring/sort',
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'getSortedScoringProducts']
                ]
            );
        } );
    }

    /**
     * Get list product by brand, group, tag
     *
     * @param WP_REST_Request $request
     */
    public function getListProducts(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $options = [];
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 20;

        $query = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'getWith' => $params['getWith'] ?? 'variationProduct,groupProduct,tags,modifier,topping,productType,productBrand',
        ];

        if (isset($params['keyword']) && $params['keyword']) {
            $query['s'] = $params['keyword'];
        }

        if (isset($params['productId']) && $params['productId']) {
            $query['post__not_in'] = [$params['productId']];
        }

        // Query by provinceID.
        /*if (isset($params['provinceId'])) {
            $terms = Category::getListInProvince($params['provinceId']);
            $termIds = [];
            foreach ($terms as $term) {
                $termIds[] = $term->id;
            }
            $query['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $termIds,
                'operator' => 'IN'
            );
        }*/

        /*$query['meta_query'][] = array(
            'key' => 'merchant_id',
            'value' => 0,
            'compare' => '!=',
        );*/

        // Query by category.
        if (isset($params['categoryId'])) {
            $query['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $params['categoryId'],
            );
        }

        // Query by tag.
        if (isset($params['tag'])) {
            $query['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => $params['tag'],
            );
        }

        // Query by group.
        if (isset($params['group'])) {
            $query['tax_query'][] = array(
                'taxonomy' => 'product_group',
                'field' => 'slug',
                'terms' => $params['group'],
            );
        }

        if (isset($params['merchantId']) && $params['merchantId']) {
            $query['meta_query'][] = array(
                'key' => 'merchant_id',
                'value' => $params['merchantId'],
                'compare' => '='
            );
        } else {
            $query['meta_query'][] = array(
                'key' => 'merchant_id',
                'value' => '',
                'compare' => '!='
            );
        }

        // Query by provinceID.
        /*if (isset($params['provinceId'])) {
            $terms = Category::getListInProvince($params['provinceId']);
            $termIds = [];
            foreach ($terms as $term) {
                $termIds[] = $term->id;
            }
            $query['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $termIds,
                'operator' => 'IN'
            );
        }*/

        if (isset($params['isMasterProduct'])) {
            $query['meta_query'][] = [
                'key' => 'is_master_product',
                'value' => 1,
                'compare' => '='
            ];
        } else {
            $query['meta_query'][] = [
                'key' => 'is_master_product',
                'value' => 0,
                'compare' => '='
            ];
        }

        // Query by group.
        if (isset($params['isPromotion'])) {
            $query['meta_query'][] = array(
                'key' => '_sale_price',
                'value' => 0,
                'compare' => '>',
            );
        }

        $query['tax_query'][] = array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => 'topping',
            'operator' => 'NOT IN'
        );

        if (isset($params['productType'])) {
            $query['tax_query'][] = array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $params['productType'],
                'operator' => 'IN'
            );
            $options['productType'] = $params['productType'];
        } else {
            $query['tax_query'][] = array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'voucher-coupon',
                'operator' => 'NOT IN'
            );
        }

        // Sort and filter product
        if (isset($params['orderBy']) && $params['orderBy']) {
            $metaKey = $orderBy = $order = null;
            switch ($params['orderBy']) {
                case 'price-asc':
                    $metaKey = '_price';
                    $orderBy = 'meta_value_num';
                    $order = 'ASC';
                    break;
                case 'price-desc':
                    $metaKey = '_price';
                    $orderBy = 'meta_value_num';
                    $order = 'DESC';
                    break;
                case 'a-to-z':
                    $query['orderby'] = 'name';
                    $query['order'] = 'ASC';
                    break;
                case 'z-to-a':
                    $query['orderby'] = 'name';
                    $query['order'] = 'DESC';
                    break;
                case 'oldest':
                    $query['orderby'] = 'post_date';
                    $query['order'] = 'ASC';
                    break;
                case 'newest':
                    $query['orderby'] = 'post_date';
                    $query['order'] = 'DESC';
                    break;
                default:
                    $metaKey = null;
                    $orderBy = null;
                    $order = null;
                    break;
            }
            if ($metaKey && $orderBy && $order) {
                $query['meta_key'] = $metaKey;
                $query['orderby'] = $orderBy;
                $query['order'] = $order;
            }
        }

        if (isset($params['perPage'])) {
            $query['posts_per_page'] = $params['perPage'];
        }

        //var_dump($query); die();
        $listProduct = Product::getListProduct($query, $options);
        if ($listProduct->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'data' => $listProduct->result,
                'total' => $listProduct->total,
                'currentPage' => $page,
                'lastPage' => $listProduct->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $listProduct->message;
        }

        Response::returnJson($res);
        die;
    }

    /**
     * Get detail of product
     *
     * @param array $data Params in endpoint.
     */
    public function getDetailProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['id'];

        $product = Product::getDetailProduct($productId);
        if ($product->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $product->result;
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $product->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getSwitchProduct(WP_REST_Request $request)
    {
        $res = new Result();

        $productId = $request['id'];

        $getProduct = Product::getDetailProduct($productId);
        if ($getProduct->messageCode == Message::SUCCESS) {
            $product = $getProduct->result;

            $productPrice = 0;
            $htmlVariation = '';
            $availableVariation = false;
            if (!empty($product->availableVariations)) {
                $availableVariation = true;
                $variations = $product->availableVariations;
                $htmlVariationList = '';
                foreach ($variations as $key => $variation) {
                    $isActive = ($key == 0) ? 'active' : '';
                    if ($key == 0) {
                        $productPrice = $variation['salePrice'] ?: $variation['regularPrice'];
                    }
                    $price = $variation['salePrice'] ?: $variation['regularPrice'];
                    $htmlVariationList .= '<li><a class="instead-product '.$isActive.'" data-price="'.$price.'" data-id="'.$variation['variationId'].'">'.$variation['variations'][0]['attrValue'].'</a></li>';
                }
                $htmlVariation .= '<div class="variations mt-3">
                                        <h5 class="title-sw"><strong>'.$variations[0]['variations'][0]['attrName'].'</strong></h5>
                                        <ul class="tags-list">'.$htmlVariationList.'</ul>
                                    </div>';
            }

            $htmlTopping = '';
            if (!empty($product->topping->data)) {
                $htmlToppingList = '';
                foreach ($product->topping->data as $topping) {
                    $toppingPrice = $topping->salePrice ?: $topping->regularPrice;
                    $htmlToppingList .= '<li>
                                            <a>
                                                <input class="instead-product" data-price="'.$toppingPrice.'" type="checkbox" id="topping_'.$topping->id.'" name="topping[]" value="'.$topping->id.'">
                                                <label for="topping_'.$topping->id.'">'.$topping->name.'</label>
                                            </a>
                                        </li>';
                }
                $htmlTopping .= '<div class="toppings mt-4">
                                    <h5 class="title-sw"><strong>Topping</strong></h5>
                                    <ul class="mt-2">'.$htmlToppingList.'</ul>
                                </div>';
            }

            $htmlModifier = '';
            if (!empty($product->modifier)) {
                $htmlModifier = '<div class="modifiers">';
                foreach ($product->modifier as $modifier) {
                    $htmlModifierData = '<ul class="tags-list">';
                    foreach ($modifier->data as $index => $item) {
                        $isActive = ($index == 0) ? 'active' : '';
                        $htmlModifierData .= '<li><a parent-id="'.$modifier->id.'" data-id="'.$item->id.'" class="'.$isActive.'">'.$item->name.'</a></li>';
                    }
                    $htmlModifierData .= '</div>';

                    $htmlModifier .= '<div class="item-modifier mt-4">
                                        <h5 class="title-sw"><strong>'.$modifier->name.'</strong></h5>'.$htmlModifierData;
                }
                $htmlModifier .= '</div>';
            }

            if (!$availableVariation) {
                $productPrice = $product->salePrice ?: $product->regularPrice;
                $check = '<input class="mr-3 sz-12 instead-product" checked type="checkbox" disabled="disabled" id="istProduct" data-price="'.$productPrice.'" name="istProduct" value="'.$product->id.'">';
            }
            $html = '<div class="inner-product">
                        <div class="main-product">
                            '.$check.'
                            <img src="'.$product->thumbnail.'" alt="">
                            <div class="txt-prd-name">
                                <h4>'.$product->name.'</h4>
                            </div>
                        </div>
                        '.$htmlVariation.'
                        '.$htmlModifier.'
                        '.$htmlTopping.'
                    </div>';

            $response = new stdClass();
            $response->html = $html;
            $response->price = $productPrice;
            $response->strPrice = number_format($productPrice);

            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $response;
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $getProduct->message;
        }

        return $res;
    }

    public function applySwitchProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $productId = $params['productId'] ?? $params['variationId'];
        $order = wc_get_order($params['orderId']);
        $requestNote = $params['note'] ?? '';
        $quantity = $params['quantity'] ?? 1;

        if ($order) {

            $getProduct = Product::getDetailProduct($productId);
            if ($getProduct->messageCode == Message::SUCCESS) {
                if (isset($params['toppingIds']) && !empty($params['toppingIds'])) {
                    foreach ($params['toppingIds'] as $toppingId) {
                        $getTopping = Product::getDetailProduct($toppingId);
                        if ($getTopping->messageCode != Message::SUCCESS) {
                            $res->messageCode = Message::GENERAL_ERROR;
                            $res->message = "Không tìm thấy sản phẩm ID: {$toppingId}";
                            Response::returnJson($res);
                            die;
                        }
                    }
                }

                foreach ($order->get_items() as $oneItem) {
                    $lineItemId = $oneItem->get_meta('lineItemId');
                    if ($lineItemId == $params['lineItemId']) {
                        $indexItem = $oneItem->get_id();
                        wc_update_order_item_meta($indexItem, "instead_of_product_id_{$lineItemId}", $productId);
                        wc_update_order_item_meta($indexItem, "instead_of_quantity_{$lineItemId}", $quantity);
                        wc_update_order_item_meta($indexItem, "instead_of_product_note_{$lineItemId}", $requestNote);

                        if (isset($params['modifiers']) && !empty($params['modifiers'])) {
                            $modifiers = [];
                            foreach ($params['modifiers'] as $itemModifier) {
                                $modifier = new \stdClass();
                                $modifier->categoryId = $itemModifier['categoryId'];
                                $term = get_term($modifier->categoryId);
                                $modifier->categoryName = $term->name;
                                $dataModifiers = [];
                                foreach ($itemModifier['data'] as $itemDataModifier) {
                                    $dataModifier = new \stdClass();
                                    $dataModifier->id = $itemDataModifier['id'];
                                    $termChild = get_term($dataModifier->id);
                                    $dataModifier->name = $termChild->name;
                                    $dataModifiers[] = $dataModifier;
                                }
                                $modifier->data = $dataModifiers;
                                $modifiers[] = $modifier;
                            }
                            wc_update_order_item_meta($indexItem, "instead_of_modifier_{$lineItemId}", $modifiers);
                        }
                        if (isset($params['toppingIds']) && !empty($params['toppingIds'])) {
                            wc_update_order_item_meta($indexItem, "instead_of_topping_id_{$lineItemId}", $params['toppingIds']);
                        }
                    }
                }
                $order->update_meta_data('is_request_change_product', true);
                $order->update_meta_data('request_change_product', 'processing');
                $order->save();

                $res->messageCode = Message::SUCCESS;
                $res->message = 'success';
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Không tìm thấy sản phẩm';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không tìm thấy đơn hàng';
        }
        Response::returnJson($res);
        die;
    }

    public function removeApplyProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $orderId = $params['id'];
        $lineItemId = $params['lineItemId'];
        $order = wc_get_order($orderId);
        $isValid = false;
        foreach ($order->get_items() as $oneItem) {
            $currentLineItemId = $oneItem->get_meta('lineItemId');
            if ($lineItemId == $currentLineItemId) {
                $indexItem = $oneItem->get_id();
                wc_delete_order_item_meta($indexItem, "instead_of_product_id_{$lineItemId}");
                wc_delete_order_item_meta($indexItem, "instead_of_quantity_{$lineItemId}");
                wc_delete_order_item_meta($indexItem, "instead_of_product_note_{$lineItemId}");
                wc_delete_order_item_meta($indexItem, "instead_of_modifier_{$lineItemId}");
                wc_delete_order_item_meta($indexItem, "instead_of_topping_id_{$lineItemId}");
            } else {
                $productId = $oneItem->get_meta("instead_of_product_id_{$currentLineItemId}");
                if ($productId) {
                    $isValid = true;
                }
            }
        }
        $order->save();
        if (!$isValid) {
            $order->delete_meta_data('is_request_change_product');
            $order->delete_meta_data('request_change_product');
        }
        $order->save();
        $res->messageCode = Message::SUCCESS;
        $res->message = 'success';
        Response::returnJson($res);
        die;
    }

    /**
     * Get detail of voucher/coupon
     *
     * @param array $data Params in endpoint.
     */
    public function getDetailVoucher(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['id'];

        $product = Product::getDetailVoucher($productId);
        if ($product->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $product->result;
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $product->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getListProductUpSell(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['productId'];

        $listProduct = Product::getListProductUpSells($productId);
        if ($listProduct->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listProduct->result;
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $listProduct->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getListProductFromListId(WP_REST_Request $request)
    {
        $res = new Result();
        $productIds = explode(',', $request['productIds']);

        $query = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $request['perPage'] ?? -1,
            'paged' => $request['page'] ?? 1,
            'post__in' => $productIds,
        ];

        $listData = Product::getListProductFromListId($query);

        if ($listData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listData->result;
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Lấy danh sách sản phẩm thất bại';
        }

        Response::returnJson($res);
        die;
    }

    public function getListTag(WP_REST_Request $request)
    {
        $res = new Result();
        $provinceId = $request['provinceId'] ?? 5;
        $listTag = Product::getListProductTags($provinceId);

        if ($listTag) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listTag;
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Lấy danh sách tag thất bại';
        }

        return $res;
    }

    public function getDetailProductByRkCode(WP_REST_Request $request)
    {
        $res = new Result();
        $rkCode = $request['rkCode'];
        $categoryId = $request['categoryId'];
        if ($rkCode) {
            $res->result = Product::searchProductByRkCode($rkCode, $categoryId);
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'success!';
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Missing param rkCode!';
        }
        return $res;
    }

    public function getListGroups(WP_REST_Request $request)
    {
        $res = new Result();

        if (isset($request['categoryId'])) {
            $listSortedProducts = Product::getListSortedGroup($request['categoryId']);

            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listSortedProducts;

        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Cần truyền thông tin category';
        }

        return $res;
    }

    public function getListSortedProductInGroups(WP_REST_Request $request)
    {
        $res = new Result();

        if (isset($request['provinceId'], $request['group'])) {
            $options = $request->get_query_params();
            $page = isset($options['page']) ? (int) $options['page'] : 1;
            $perPage = isset($options['perPage']) ? (int) $options['perPage'] : 8;
            $listSortedProductOnGroup = Product::getListSortedProductOnGroup($request['provinceId'], $request['group'], $options);
            if ($listSortedProductOnGroup->messageCode === Message::SUCCESS) {
                $data = $listSortedProductOnGroup;
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'Thành công';
                $res->result = [
                    'data' => $data->result,
                    'total' => $data->total,
                    'currentPage' => $page,
                    'lastPage' => $data->lastPage,
                    'perPage' => $perPage,
                ];
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = 'Không có sản phẩm';
            }
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Cần truyền đủ province và group';
        }

        return $res;
    }

    public function getListSortedProductHome(WP_REST_Request $request)
    {
        $res = new Result();
        if (isset($request['provinceId'])) {
            $options = [];
            if (isset($request['type'])) {
                $options['type'] = $request['type'];
            }
            if (isset($request['tag'])) {
                $options['tag'] = $request['tag'];
            }
            $listSortedProduct = Product::getListSortedProductHome($request['provinceId'], $options);
            if (!empty($listSortedProduct)) {
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $listSortedProduct;
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = 'Không có sản phẩm';
            }
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Cần truyền province';
        }

        return $res;
    }

    // todo more
    public function getPageDetail(WP_REST_Request $request)
    {
        $res = new Result();
        $slug = $request['slug'];
        if ($slug) {
            $posts = get_page_by_path($slug, OBJECT, 'page');
            if ($posts) {
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $posts;
            } else {
                $res->messageCode = ApiMessage::NOT_FOUND;
                $res->message = 'Không tìm thấy trang';
            }
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Vui lòng truyền đầy đủ param';
        }
        return $res;
    }

    public function getShippingTax(WP_REST_Request $request)
    {
        $res = new Result();
        $brandId = $request['brandId'];
        if ($brandId) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = \GDelivery\Libs\Helper\Helper::getShippingTax($brandId);
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Vui lòng chọn lại brand';
        }

        return $res;
    }

    public function getListProductByMerchant(WP_REST_Request $request)
    {
        $res = new Result();
        $merchantId = $request['merchantId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListProductByMerchantId($merchantId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getListSameBuyProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['productId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListSameBuyProduct($productId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListUpsellsProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['productId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListUpsellsProduct($productId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListSameCategoryProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['productId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListSameCategoryProduct($productId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListGroupByMerchant(WP_REST_Request $request)
    {
        $res = new Result();
        $merchantId = $request['merchantId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $groupData = Product::getListGroupByMerchant($merchantId, $params);
        if ($groupData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $groupData->total;
            $res->total = $groupData->total;
            $res->lastPage = $groupData->lastPage;
            $res->currentPage = $groupData->currentPage;
            $res->result = [
                'data' => $groupData->result,
                'total' => $groupData->total,
                'currentPage' => $page,
                'lastPage' => $groupData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $groupData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListProductByGroup(WP_REST_Request $request)
    {
        $res = new Result();
        $groupId = $request['groupId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListProductByGroup($groupId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListSuggestionProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListSuggestionProduct($params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListProductByCategory(WP_REST_Request $request)
    {
        $res = new Result();
        $categoryId = $request['categoryId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $productData = Product::getListProductByCategory($categoryId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListProductByMerchantCategory(WP_REST_Request $request)
    {
        $res = new Result();
        $merchantCategoryId = $request['merchantCategoryId'];
        $params = $request->get_query_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 100;
        $productData = Product::getListProductByMerchantCategory($merchantCategoryId, $params);
        if ($productData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->numberOfResult = $productData->total;
            $res->total = $productData->total;
            $res->lastPage = $productData->lastPage;
            $res->currentPage = $productData->currentPage;
            $res->result = [
                'data' => $productData->result,
                'total' => $productData->total,
                'currentPage' => $page,
                'lastPage' => $productData->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $productData->message;
        }
        Response::returnJson($res);
        die;
    }

    public function getListScoringProducts(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 20;

        $sortingProducts = ScoringProduct::getListProducts([
            'post_type' => 'product',
            'post_status' => array_values(get_post_stati()),
            'posts_per_page' => $perPage,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => 'is_master_product',
                    'value' => 0,
                    'compare' => '=',
                ],
                [
                    'key' => 'merchant_id',
                    'value' => 0,
                    'compare' => '>',
                ],
            ],
            'tax_query' => Product::getDefaultTaxQuery(),
        ]);
        if ($sortingProducts->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'data' => $sortingProducts->result,
                'total' => $sortingProducts->total,
                'currentPage' => $page,
                'lastPage' => $sortingProducts->lastPage,
                'perPage' => $perPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $sortingProducts->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getListSpecificProducts(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $productIds = $params['productIds'] ?? [];

        $sortingProducts = ScoringProduct::getListSpecificProducts([
            'post_type' => 'product',
            'post__in' => $productIds,
            'post_status' => array_values(get_post_stati()),
            'meta_query' => [
                [
                    'key' => 'is_master_product',
                    'value' => 0,
                    'compare' => '=',
                ],
                [
                    'key' => 'merchant_id',
                    'value' => 0,
                    'compare' => '>',
                ],
            ],
            'tax_query' => Product::getDefaultTaxQuery(),
        ]);

        if ($sortingProducts->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'data' => $sortingProducts->result,
                'total' => $sortingProducts->total,
                'lastPage' => $sortingProducts->lastPage,
            ];
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $sortingProducts->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getDetailScoringProduct(WP_REST_Request $request)
    {
        $res = new Result();
        $productId = $request['id'];
        $product = ScoringProduct::getDetailProduct($productId);
        if ($product->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $product->result;
        } else {
            $res->messageCode = ApiMessage::BAD_REQUEST;
            $res->message = $product->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getSortedScoringProducts(WP_REST_Request $request)
    {
        $result = new Result();
        $params = $request->get_params();
        $productsResult = Product::getSortedScoringProducts($params);
        if ($productsResult->messageCode == Message::SUCCESS) {
            $result->messageCode = ApiMessage::SUCCESS;
            $result->message = 'Thành công';
            $result->result = $productsResult->result;
        } else {
            $result->messageCode = ApiMessage::BAD_REQUEST;
            $result->message = $productsResult->message;
        }
        Response::returnJson($result);
        die;
    }
}

// init
$productApi = new ProductApi();
