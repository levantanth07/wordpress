<?php
/*
Api for brand

Get with product_cat

*/

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;

class BrandApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/brand/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getBrands"],
            ) );

            register_rest_route( 'api/v1', '/brand/(?P<id>\d+)/detail', array(
                'methods' => 'GET',
                'callback' => [$this, "getBrand"],
            ) );
        } );
    }

    public function getBrand(WP_REST_Request $request)
    {
        $res = new Result();
        $id = $request['id'];

        $getBrand = \GDelivery\Libs\Helper\Helper::getBrand($id);

        if ($getBrand->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $getBrand->result;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = $getBrand->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getBrands(WP_REST_Request $request)
    {
        $res = new Result();
        $provinceId = isset($request['provinceId']) ? $request['provinceId'] : '';
        $provinceCode = isset($request['provinceCode']) ? $request['provinceCode'] : '';
        $type = isset($request['type']) ? $request['type'] : '';
        $sceneId = isset($request['sceneId']) ? $request['sceneId'] : '';

        $getBrand = \GDelivery\Libs\Helper\Helper::getBrands(
            [
                'provinceId' => $provinceId,
                'provinceCode' => $provinceCode,
                'type' => $type,
                'sceneId' => $sceneId
            ]
        );

        if ($getBrand->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $getBrand->result;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = $getBrand->message;
        }

        Response::returnJson($res);
        die;
    }

}

// init
$brandApi = new BrandApi();
