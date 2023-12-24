<?php


use Abstraction\Object\ApiMessage;
use Abstraction\Object\Result;

class Customer extends \Abstraction\Core\AApiHook
{
    public function __construct()
    {
        parent::__construct();

        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/customer/verify', array(
                'methods' => 'post',
                'callback' => [$this, "verifyCustomer"],
            ) );
        });
    }

    public function verifyCustomer(WP_REST_Request $request)
    {
        $res = new Result();
        if (isset($request['cellphone']) && $request['cellphone']) {

            $cellphone = \Abstraction\Core\Helper::correctCellphoneNumber($request['cellphone']);
            $email = isset($request['email']) ? $request['email'] : $cellphone.'@fake_email.com';
            $existingUserId = username_exists($cellphone);
            if ($existingUserId) {
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $existingUserId;
                wp_update_user(
                    [
                        'ID' => $existingUserId,
                        'user_email' => $email
                    ]
                );
            } else {
                $user_id = wp_insert_user(
                    [
                        'user_login' => $cellphone,
                        'user_pass' => isset($request['password']) ? $request['password'] : \GDelivery\Libs\Helper\User::randomPassword(8),
                        'first_name' => $cellphone,
                        'last_name' => '',
                        'user_email' => $email,
                        'role' => 'customer'
                    ]
                );
                if ($user_id) {
                    $res->messageCode = ApiMessage::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $user_id;
                } else {
                    $res->messageCode = ApiMessage::GENERAL_ERROR;
                    $res->message = 'Có lỗi xãy ra';
                }
            }
        } else {
            $res->messageCode = ApiMessage::MISSING_PARAMS;
            $res->message = 'Cần truyền đủ cellphone';
        }
        return $res;
    }
}
$customerApi = new Customer();