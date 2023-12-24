<?php
/*
Template Name: Ajax Create RK Order
*/

$res = new \Abstraction\Object\Result();
$tgsService = new \GDelivery\Libs\TGSService();
$bookingService = new \GDelivery\Libs\BookingService();
$currentUser = wp_get_current_user();
$user = Permission::checkCurrentUserRole($currentUser);

if (in_array($user->role, ['restaurant', 'administrator'])) {

    $orderId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

    if ($orderId) {
        $order = wc_get_order($orderId);

        if ($order) {
            $gposService = new \GDelivery\Libs\GPosService();

            $createOrder = $gposService->createOrder($order);

            if ($createOrder->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $order->update_meta_data('is_created_rk_order', 1);
                $order->save();

                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                $res->message = $createOrder->message;
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi tạo order trên POS: '.$createOrder->message;
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Đơng hàng ko tồn tại.';
        }
    } else {
        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
        $res->message = 'Cần truyền đày đủ thông tin.';
    }
} else {
    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
    $res->message = 'Bạn ko có quyền truy cập';
}

\GDelivery\Libs\Helper\Response::returnJson($res);