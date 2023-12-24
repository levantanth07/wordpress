<?php
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Message;
use GDelivery\Libs\Config;

class GetProducts extends \Abstraction\Core\AAjaxHook {

    private $redis;

    public function __construct()
    {
        parent::__construct();

        // Block dynamic
        add_action("wp_ajax_get_products_by_province_and_keyword", [$this, "getProductsByProvinceAndKeyword"]);

        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
    }

    public function getProductsByProvinceAndKeyword() {
        $res = new Result();
        $keyWord = $_REQUEST['keyWord'];
        $provinceId = $_REQUEST['provinceId'];
        if (mb_strlen($keyWord) < 3 ) {

            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Bạn phải nhập nhiều hơn 3 kí tự để tìm kiếm';
            Response::returnJson($res);
            die;
        }

        if (!$provinceId) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Phải chọn tỉnh thành';

            Response::returnJson($res);
            die;
        }

        $items = [];
        $options = [
            'keyWord' => $keyWord,
            'productIdSelected' => $_REQUEST['productIdSelected']
        ];

        $getProducts = HelperFlashSale::getProductsByProvinceAndKeyword($provinceId, null, $options);
        if ($getProducts->messageCode == Message::SUCCESS) {
            $items = $getProducts->result;
        }

        if (!empty($items)) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $items;
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

} //end class

// init class
$getProducts = new GetProducts();
