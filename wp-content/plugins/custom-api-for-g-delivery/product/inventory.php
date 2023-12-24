<?php
/*
    Product Inventory API
*/

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use Abstraction\Object\ApiMessage;
use GDelivery\Libs\InventoryService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InventoryApi extends \Abstraction\Core\AApiHook {

    private $logger;

    public function __construct()
    {
        parent::__construct();

        add_action( 'rest_api_init', function () {
            register_rest_route('api/v1/inventory', '/check-stock', array(
                'methods' => 'POST',
                'callback' => [$this, "checkStock"],
            ));
            register_rest_route('api/v1/inventory', '/decrease-stock', array(
                'methods' => 'POST',
                'callback' => [$this, "decreaseStock"],
            ));
            register_rest_route('api/v1/inventory', '/increase-stock', array(
                'methods' => 'POST',
                'callback' => [$this, "increaseStock"],
            ));
        });

        $this->logger = new Logger('g-backend');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/g-backend/inventory-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
    }

    private function validateParams($params)
    {
        if (!isset($params['products'])) {
            return [false, 'Cần truyền thông tin sản phẩm (products parameter)'];
        }
        $isValidColumns = true;
        $requiredColumns = ['id', 'quantity'];
        foreach ($params['products'] as $productItem) {
            foreach ($requiredColumns as $column) {
                if (!array_key_exists($column, $productItem)) {
                    $isValidColumns = false;
                    break;
                }
            }
        }
        if (!$isValidColumns) {
            return [false, "Sai cấu trúc sản phẩm (['id', 'quantity'])"];
        }
        return [true, ''];
    }

    public function checkStock(WP_REST_Request $request)
    {
        $params = $request->get_params();
        list($isValidParam, $message) = $this->validateParams($params);
        if (!$isValidParam) {
            return $this->response(ApiMessage::GENERAL_ERROR, $message);
        }
        $checkStock = (new InventoryService())->checkStock($params['products']);
        $this->logger->info("Inventory checkStock. Params: " . \json_encode($params) . "; Response: " . \json_encode($checkStock->result));
        if ($checkStock->messageCode != Message::SUCCESS) {
            return $this->response(ApiMessage::GENERAL_ERROR, $checkStock->message);
        }
        return $this->response(ApiMessage::SUCCESS, 'OK', $checkStock->result);
    }

    public function decreaseStock(WP_REST_Request $request)
    {
        $params = $request->get_params();
        list($isValidParam, $message) = $this->validateParams($params);
        if (!$isValidParam) {
            return $this->response(ApiMessage::GENERAL_ERROR, $message);
        }
        (new InventoryService())->decreaseStock($params['products']);
        $this->logger->info("Inventory decreaseStock. Params: " . \json_encode($params));
        return $this->response(ApiMessage::SUCCESS, 'OK');
    }

    public function increaseStock(WP_REST_Request $request)
    {
        $params = $request->get_params();
        list($isValidParam, $message) = $this->validateParams($params);
        if (!$isValidParam) {
            return $this->response(ApiMessage::GENERAL_ERROR, $message);
        }
        (new InventoryService())->increaseStock($params['products']);
        $this->logger->info("Inventory increaseStock. Params: " . \json_encode($params));
        return $this->response(ApiMessage::SUCCESS, 'OK');
    }

    private function response($messageCode, $message, $result = [])
    {
        $res = new Result();
        $res->messageCode = $messageCode;
        $res->message = $message;
        $res->result = $result;
        return $res;
    }
}
$inventoryApi = new InventoryApi();