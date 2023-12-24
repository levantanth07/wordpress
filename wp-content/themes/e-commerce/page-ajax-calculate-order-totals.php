<?php
/*
Template Name: Ajax Calculate Order Totals
*/

$res = new \Abstraction\Object\Result();

if (!isset($_SESSION['isLogin']) || !$_SESSION['isLogin']) {
    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
    $res->message = 'Vui lòng đăng nhập lại.';
} else {
    // get order detail
    $order = wc_get_order( $_REQUEST['id'] );

    if ($order) {
        // customer only can view their order
        if ($order->get_customer_id() == get_current_user_id()) {
            $paymentMethod = $_REQUEST['paymentMethod'];
            $pickupAtRestaurant = $_REQUEST['pickupAtRestaurant'];
            $temp = new \stdClass();

            if (get_option('google_map_service_address') == 'goong_address') {
                $shippingVendor = 'restaurant';
            } else {
                $shippingVendor = 'grab_express';
            }

            $totals = \GDelivery\Libs\Helper\Helper::calculateOrderTotals(
                $order,
                [
                    'pickupAtRestaurant' => $pickupAtRestaurant,
                    'paymentMethod' => $paymentMethod,
                    'shippingVendor' => $shippingVendor
                ]
            );

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $totals;
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Bạn đang gian lận với đơn hàng không phải của mình????';
        }
    } else {
        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
        $res->message = 'Đơn hàng không tồn tại.';
    }
}

\GDelivery\Libs\Helper\Response::returnJson($res);