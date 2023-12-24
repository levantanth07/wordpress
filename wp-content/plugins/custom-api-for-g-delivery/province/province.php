<?php
/*
Api for province
*/

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Province;
use Abstraction\Object\Message;

class ProvinceApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/province/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getProvinces"],
            ) );
        } );
    }

    // add api
    public function getProvinces( WP_REST_Request $request )
    {
        $res = new Result();
        $listProvinces = \GDelivery\Libs\Helper\Helper::getProvinces();
        if ($listProvinces->messageCode == Message::SUCCESS) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listProvinces->result;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Lỗi khi lấy thông tin tỉnh thành';
        }

        return $res;

    }
}

// init
$provinceApi = new ProvinceApi();
