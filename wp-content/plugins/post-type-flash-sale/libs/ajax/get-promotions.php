<?php
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Message;
use GDelivery\Libs\Config;
use GDelivery\Libs\Helper\Product;

class GetPromotions extends \Abstraction\Core\AAjaxHook {

    private $redis;

    public function __construct()
    {
        parent::__construct();

        // Block dynamic
        add_action("wp_ajax_get_promotion_for_product", [$this, "getPromotionForProduct"]);

        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
    }

    public function getPromotionForProduct() {
        $res = new Result();

        $productId = $_REQUEST['productId'];
        $productInfo = Product::getProductInfo($productId);

        $promotions = \json_decode(file_get_contents(plugin_dir_path(dirname(__FILE__, 2)) . '/promotions.json'));

        if (!empty($promotions)) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $promotions;
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

} //end class

// init class
$getPromotions = new GetPromotions();
