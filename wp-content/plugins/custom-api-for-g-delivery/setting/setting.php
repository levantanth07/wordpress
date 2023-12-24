<?php
/*
Api for banner
*/

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Setting;
use Abstraction\Object\ApiMessage;

class SettingApi extends \Abstraction\Core\AApiHook
{

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'setting/payment-method', array(
                'methods' => 'GET',
                'callback' => [$this, "getPaymentMethod"],
            ));
        });
    }

    public function getPaymentMethod(WP_REST_Request $request)
    {
        $res = new Result();

        $params = [];
        if (isset($request['isActive'])) {
            $params['isActive'] = $request['isActive'];
        }
        $paymentMethod = Setting::getPaymentMethod($params);

        if ($paymentMethod->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $paymentMethod->result;

            Response::returnJson($res);
            die;
        }

        $res->messageCode = ApiMessage::GENERAL_ERROR;
        $res->message = 'Lấy dánh sách hình thức thanh toán thất bại!';

        Response::returnJson($res);
        die;
    }
}

// init
$settingApi = new SettingApi();
