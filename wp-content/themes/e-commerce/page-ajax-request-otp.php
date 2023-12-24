<?php 
/*
Template Name: Ajax Request OTP
*/

// process login
$res = new \Abstraction\Object\Result();

if (\GDelivery\Libs\Helper\User::isLogin()) {
    $res->messageCode = \Abstraction\Object\Message::SUCCESS_WITHOUT_DATA;
    $res->message = 'Bạn đã đăng nhập';
} else {
    /** Check exist cellphone and numberic cellphone */
    if (isset($_POST['cellphone'])) {
        $cellphone = \Abstraction\Core\Helper::correctCellphoneNumber($_POST['cellphone']);
        $method = isset($_POST['method']) ? $_POST['method'] : 'sms';
        $forceResend = isset($_POST['forceResend']) ? (boolean) $_POST['forceResend'] : false;

        $tgsService = new \GDelivery\Libs\TGSService();

        // step request send otp
        $requestOTP = $tgsService->requestOTP($cellphone, $method, $forceResend);

        if ($requestOTP->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = $requestOTP->message;
            $res->result = $cellphone;
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = $requestOTP->message;
        }
    } else {
        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
        $res->message = 'Bạn cần nhập số điện thoại hợp lệ';
    }
}

\GDelivery\Libs\Helper\Response::returnJson($res);


