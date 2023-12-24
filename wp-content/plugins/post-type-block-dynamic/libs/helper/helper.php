<?php

use Abstraction\Object\Result;
use Abstraction\Object\Message;

class Helper {

    private $redis;

    public function __construct()
    {
        //
    }

    private static function sortItem($items, $listIdSorted)
    {
        $arrProductIdSorted = explode(',', $listIdSorted);
        $listSort = array_flip($arrProductIdSorted);
        $listItemSorted = [];

        foreach ($items as $item) {
            $thumbnail = get_the_post_thumbnail_url($item, 'shop_catalog');
            if (!$thumbnail) {
                $thumbnail = get_bloginfo('template_url') . '/assets/images/no-product-image.png';
            }
            $temp = new \stdClass();
            $temp->id = $item->ID;
            $temp->name = $item->post_title;
            $temp->thumbnail = $thumbnail;
            $listItemSorted[$listSort[$item->ID]] = $temp;
        }
        ksort($listItemSorted);

        return $listItemSorted;
    }

    public static function getProductSortUnSortByProvince($provinceId, $postId = null, $options = []) {
        $res = new Result();

        $arrProductIdSorted = [];
        $listProductSorted = [];

        if ($postId) {
            $listProductIdSorted = get_field('list_item_sorted', $postId);
            $arrProductIdSorted = explode(',', $listProductIdSorted);
            $paramGetProductSorted = [
                'post_type' => 'product',
                'post_status'=>'publish',
                'posts_per_page'=> -1,
                'page' => 1,
                'post__in' => $arrProductIdSorted
            ];
            $queryProductSorted = new \WP_Query($paramGetProductSorted);
            $products = $queryProductSorted->posts;
            $listProductSorted = self::sortItem($products, $listProductIdSorted);
        }

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
        if (isset($options['screen'])) {
            if ($options['screen'] == 'goi_do_an') {
                $args['meta_query'][] = [
                    'key' => 'sceneId',
                    'value' => 1,
                ];
            } elseif ($options['screen'] == 'di_cho') {
                $args['meta_query'][] = [
                    'key' => 'sceneId',
                    'value' => 2,
                ];
            }
        }
        $merchantIds = get_posts($args);

        $products = [];
        if (!empty($merchantIds)) {
            $paramGetProductNoneMaster = [
                'post_type' => 'product',
                'post_status'=>'publish',
                's' => $keyWord ?? '',
                'posts_per_page'=> -1,
                'page' => 1,
                'post__not_in' => $arrProductIdSorted,
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

            $queryProductNoneMaster = new \WP_Query($paramGetProductNoneMaster);
            $products = $queryProductNoneMaster->posts;
        }

        $listProductUnSort = [];
        foreach ($products as $product) {
            $thumbnail = get_the_post_thumbnail_url($product, 'shop_catalog');
            if (!$thumbnail) {
                $thumbnail = get_bloginfo('template_url') . '/assets/images/no-product-image.png';
            }
            $merchantId = get_field('merchant_id', $product->ID);
            $temp = new \stdClass();
            $temp->id = $product->ID;
            $temp->name = $product->post_title . "<br/>" . get_the_title($merchantId);
            $temp->thumbnail = $thumbnail;
            $listProductUnSort[] = $temp;
        }

        if ($listProductUnSort || $listProductSorted) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'sorted' => $listProductSorted,
                'unSort' => $listProductUnSort
            ];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        return $res;
    }

    public static function getMerchantByProvince($provinceId, $postId = null, $options = []) {
        $res = new Result();

        $listMerchantSorted = [];

        $paramGetMerchant = [
            'post_type' => 'merchant',
            'post_status'=>'publish',
            's' => $keyWord ?? '',
            'posts_per_page'=> -1,
            'page' => 1,
            'meta_query' => [
                [
                    'key' => 'province_id',
                    'value' => $provinceId,
                ]
            ]
        ];

        if (isset($options['screen'])) {
            if ($options['screen'] == 'goi_do_an') {
                $paramGetMerchant['meta_query'][] = [
                    'key' => 'sceneId',
                    'value' => 1,
                ];
            } elseif ($options['screen'] == 'di_cho') {
                $paramGetMerchant['meta_query'][] = [
                    'key' => 'sceneId',
                    'value' => 2,
                ];
            }
        }

        if ($postId) {
            $listMerchantIdSorted = get_field('list_item_sorted', $postId);
            $arrMerchantIdSorted = explode(',', $listMerchantIdSorted);
            $paramGetMerchantSorted = [
                'post_type' => 'merchant',
                'post_status'=>'publish',
                'posts_per_page'=> -1,
                'page' => 1,
                'post__in' => $arrMerchantIdSorted
            ];
            $queryMerchantSorted = new \WP_Query($paramGetMerchantSorted);
            $merchants = $queryMerchantSorted->posts;
            $listMerchantSorted = self::sortItem($merchants, $listMerchantIdSorted);
            $paramGetMerchant['post__not_in'] =  explode(',', $listMerchantIdSorted);
        }

        $queryMerchant = new \WP_Query($paramGetMerchant);
        $merchants = $queryMerchant->posts;

        $listMerchantUnSort = [];
        foreach ($merchants as $merchant) {
            $thumbnail = get_the_post_thumbnail_url($merchant, 'shop_catalog');
            if (!$thumbnail) {
                $thumbnail = get_bloginfo('template_url') . '/assets/images/no-product-image.png';
            }
            $temp = new \stdClass();
            $temp->id = $merchant->ID;
            $temp->name = $merchant->post_title;
            $temp->thumbnail = $thumbnail;
            $listMerchantUnSort[] = $temp;
        }

        if ($listMerchantUnSort) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'sorted' => $listMerchantSorted,
                'unSort' => $listMerchantUnSort
            ];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có merchant';
        }

        return $res;
    }

    public static function getProductSortUnSortByMerchant($merchantId, $postId = null, $options = []) {
        $res = new Result();

        $arrProductIdSorted = [];
        $listProductSorted = [];

        if ($postId) {
            $listProductIdSorted = get_field('list_item_sorted', $postId);
            $arrProductIdSorted = explode(',', $listProductIdSorted);
            $paramGetProductSorted = [
                'post_type' => 'product',
                'post_status'=>'publish',
                'posts_per_page'=> -1,
                'page' => 1,
                'post__in' => $arrProductIdSorted
            ];
            $queryProductSorted = new \WP_Query($paramGetProductSorted);
            $products = $queryProductSorted->posts;
            $listProductSorted = self::sortItem($products, $listProductIdSorted);
        }

        $paramGetProductNoneMaster = [
            'post_type' => 'product',
            'post_status'=>'publish',
            'posts_per_page'=> -1,
            'page' => 1,
            'post__not_in' => $arrProductIdSorted,
            'meta_query' => [
                [
                    'key' => 'merchant_id',
                    'value' => $merchantId,
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

        $queryProductUnSort = new \WP_Query($paramGetProductNoneMaster);
        $products = $queryProductUnSort->posts;

        $listProductUnSort = [];
        foreach ($products as $product) {
            $thumbnail = get_the_post_thumbnail_url($product, 'shop_catalog');
            if (!$thumbnail) {
                $thumbnail = get_bloginfo('template_url') . '/assets/images/no-product-image.png';
            }
            $merchantId = get_field('merchant_id', $product->ID);
            $temp = new \stdClass();
            $temp->id = $product->ID;
            $temp->name = $product->post_title . "<br/>" . get_the_title($merchantId);
            $temp->thumbnail = $thumbnail;
            $listProductUnSort[] = $temp;
        }

        if ($listProductUnSort || $listProductSorted) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'sorted' => $listProductSorted,
                'unSort' => $listProductUnSort
            ];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        return $res;
    }

} //end class

// init class
$blockDynamicAjax = new BlockDynamic();
