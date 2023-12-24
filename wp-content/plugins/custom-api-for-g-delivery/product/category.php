<?php

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Category;
use Abstraction\Object\ApiMessage;

class CategoryApi extends \Abstraction\Core\AApiHook
{

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/category/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "getDetailCategory"],
            ) );

            register_rest_route( 'api/v1', '/categories', array(
                'methods' => 'GET',
                'callback' => [$this, "getCategories"],
            ) );

            register_rest_route(
                'api/v1',
                '/product/taxonomy',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getProductTaxonomy"]
                ]
            );
        });
    }

    public function getDetailCategory(WP_REST_Request $request)
    {
        $res = new Result();

        $categoryId = $request['id'];
        $getWith = isset($request['getWith']) ? $request['getWith'] : '';
        $category = Category::getCategoryById(
            $categoryId,
            $getWith
        );

        if (!empty($category)) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $category;
        } else {
            $res->messageCode = ApiMessage::NOT_FOUND;
            $res->message = 'Category not found';
        }
        \GDelivery\Libs\Helper\Response::returnJson($res);
        die();
    }

    public function getCategories(WP_REST_Request $request)
    {
        $res = new Result();
        $provinceId = isset($request['provinceId']) ? $request['provinceId'] : null;
        $categories = Category::getCategories(
            [
                'withParent' => isset($request['withParent']) ? $request['withParent'] : 0,
                'parentId' => isset($request['parentId']) ? $request['parentId'] : '',
                $provinceId
            ]
        );

        if (!empty($categories)) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $categories;
        } else {
            $res->messageCode = ApiMessage::NOT_FOUND;
            $res->message = 'Category not found';
        }
        \GDelivery\Libs\Helper\Response::returnJson($res);
        die();
    }

    public function getProductTaxonomy(WP_REST_Request $request)
    {
        $res = new Result();

        $productTypes = get_terms(
            'product_meat_type',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );
        $types = [];
        foreach ($productTypes as $type) {
            $types[] = [
                'id' => $type->term_id,
                'name' => $type->name,
                'slug' => $type->slug,
            ];
        }


        $productBrands = get_terms(
            'product_apply_for_brand',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );
        $brands = [];
        foreach ($productBrands as $brand) {
            $brands[] = [
                'id' => $brand->term_id,
                'name' => $brand->name,
                'slug' => $brand->slug,
            ];
        }

        $res->message = 'success';
        $res->messageCode = ApiMessage::SUCCESS;
        $res->result = [
            'types' => $types,
            'brands' => $brands,
        ];

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die();
    }
}

// init
$categoryApi = new CategoryApi();