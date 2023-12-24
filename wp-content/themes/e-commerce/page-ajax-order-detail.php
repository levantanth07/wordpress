<?php 
/*
Template Name: Ajax Order Detail
*/

$res = new \Abstraction\Object\Result();

$user = Permission::checkCurrentUserRole();

if ($user->role != 'operator' && $user->role != 'administrator' && $user->role != 'restaurant') {
    $res->messageCode = 0;
    $res->message = 'Bạn không có quyền truy cập';
} else {
    $orderId = isset($_REQUEST['orderId']) ? $_REQUEST['orderId'] : null;

    if ($orderId) {
        $order = wc_get_order($orderId);
        if ($order) {
            // get available restaurant for order
            $getAvailableRestaurant = \GDelivery\Libs\Helper\Helper::getRestaurantsInCategory(
                $order->get_meta('current_product_category_id'),
                [
                    'fromLongitude' => $order->get_meta('customer_selected_address')->longitude,
                    'fromLatitude' => $order->get_meta('customer_selected_address')->latitude
                ]
            );
            if ($getAvailableRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $availableRestaurants = $getAvailableRestaurant->result;
            } else {
                $availableRestaurants = [];
            }

            $res->messageCode = 1;
            $res->message = 'Thành công';
            $res->result = [
                'order' => [
                    'id' => $order->get_id(),
                    'restaurant' => $order->get_meta('restaurant_object'),
                    '_order_shipping' => $order->get_shipping_total(),
                    'shipping_price' => $order->get_meta('shipping_price'),
                    'total_price' => $order->get_meta('total_price'),
                    'payment_method' => $order->get_meta('payment_method'),
                    'is_paid' => $order->get_meta('is_paid'),
                    'vendor_transport' => $order->get_meta('vendor_transport'),
                    'status' => $order->get_status(),
                    'payment_partner_transaction_id' => $order->get_meta('payment_partner_transaction_id'),
                ],
                'availableRestaurant' => $availableRestaurants
            ];
        } else {
            $res->messageCode = 0;
            $res->message = 'Đơn hàng không tồn tại';
        }
    } else {
        $res->messageCode = 0;
        $res->message = 'Cần truyền order id';
    }
}

\GDelivery\Libs\Helper\Response::returnJson($res);
