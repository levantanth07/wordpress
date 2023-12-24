<?php
/*
Template Name: Ajax Update Order Info
*/
$currentUser = wp_get_current_user();
$paymentService = new \GDelivery\Libs\PaymentHubService();

if (!$currentUser) {
    header('Location: '.site_url('wp-login.php'));
}

$res = new \Abstraction\Object\Result();
$user = Permission::checkCurrentUserRole($currentUser);

// process login
if (in_array($user->role, ['operator', 'administrator'])) {
    $orderId = isset($_REQUEST['orderId']) ? $_REQUEST['orderId'] : null;

    if ($orderId) {
        // update order info
        $is_paid = isset($_REQUEST['is_paid']) ? $_REQUEST['is_paid'] : 0;
        $payment_method = isset($_REQUEST['payment_method']) ? $_REQUEST['payment_method'] : "";
        $payment_partner_transaction_id = isset($_REQUEST['payment_partner_transaction_id']) ? $_REQUEST['payment_partner_transaction_id'] : null;
        $vendor_transport  = isset($_REQUEST['vendor_transport']) ? $_REQUEST['vendor_transport'] : "";
        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : 0;

        $order = wc_get_order( $orderId );
        $customerInfo = $order->get_meta('customer_info');

        // data cũ
        $oldPaymentMethod = $order->get_meta('payment_method');
        $oldStatus = $order->get_status();
        $order_old = [
            'id' => $order->get_id(),
            '_order_shipping' => $order->get_shipping_total(),
            'shipping_price' => $order->get_meta('shipping_price'),
            'total_price' => $order->get_meta('total_price'),
            'payment_method' => $oldPaymentMethod,
            'is_paid' => $order->get_meta('is_paid'),
            'vendor_transport' => $order->get_meta('vendor_transport'),
            'status' => $oldStatus,
            'payment_partner_transaction_id' => $order->get_meta('payment_partner_transaction_id'),
        ];

        if ($payment_method == \GDelivery\Libs\Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME) {
            $orderTotal = \GDelivery\Libs\Helper\Helper::orderTotals($order);
            if ($status == 'cancelled') {
                if ($oldStatus != 'cancelled') {
                    // release amount if pay with BizAccount change any status to cancelled
                    $customerNumber = $customerInfo->customerNumber;
                    $holdId = $order->get_meta('gbiz_hold_id');
                    if ($holdId) {
                        $doReleaseBalance = $paymentService->releaseBalance($customerNumber, $holdId);
                        if ($doReleaseBalance->messageCode == \Abstraction\Object\Message::SUCCESS) {
                            $order->delete_meta_data('gbiz_hold_id');
                        }
                    }
                }
            } else {
                if ($oldStatus == 'cancelled') {
                    // hold amount if pay with BizAccount change any status to cancelled
                    $customerNumber = $customerInfo->customerNumber;
                    $options = [
                        'referTransaction' => \GDelivery\Libs\Config::PAYMENT_HUB_GBIZ_SOURCE . '_' . $orderId
                    ];
                    $doHoldBalance = $paymentService->holdBalance($customerNumber, $orderTotal->totalPaySum ?? 0, $options);
                    if ($doHoldBalance->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $holdId = $doHoldBalance->result->id;
                        $order->update_meta_data('gbiz_hold_id', $holdId);
                    }
                }
            }
        }

        $order->update_meta_data('is_paid', $is_paid);
        $order->update_meta_data('payment_method', $payment_method);
        $order->update_meta_data('payment_partner_transaction_id', $payment_partner_transaction_id);
        $order->update_meta_data('vendor_transport', $vendor_transport);

        $fnSaveOrderHistories = function () use ($order, $status) {
            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
            $histories[] = [
                'status' => $status,
                'statusText' => \GDelivery\Libs\Helper\Order::$arrayStatus[$status] ?? $status,
                'createdAt' => date_i18n('Y-m-d H:i:s')
            ];
            $order->update_meta_data('order_status_histories', $histories);
        };
        $fnSaveOrderHistories();

        if ($user->role == 'operator') {
            if ($order->get_status() == 'waiting-payment' && $status == 'pending') {
                $order->set_status($status);
            }
        } elseif ($user->role == 'administrator') {
            $order->set_status($status);
        }
        $order_new = [
            'id' => $orderId,
            'payment_method' => $payment_method,
            'is_paid' => $is_paid,
            'vendor_transport' => $vendor_transport,
            'status' => $status,
            'payment_partner_transaction_id' => $payment_partner_transaction_id,
        ];

        $order->save();

        // save to report
        $report = new \GDelivery\Libs\Helper\Report();
        $report->updateOrder($order);

        // loger
        $endPoint = 'ajax-update-order-info';
        $sendData = [
            'user_id_change' => $currentUser->ID,
            'order_old' => $order_old,
            'order_new' => $order_new
        ];
        $logger = new \Monolog\Logger('order');
        $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(ABSPATH.'/logs/order/update-order-info-'.date_i18n('Y-m-d').'.log', \Monolog\Logger::DEBUG));
        $logger->info("Request update order, RequestId: {$orderId}; EndPoint: {$endPoint}; Data: ".\json_encode($sendData));

        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
        $res->message = "Cập nhật thông tin đơn hàng thành công";
    } else {
        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
        $res->message = 'Cần truyền orderId';
    }
} else {
    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
    $res->message = 'Vui lòng đăng nhập lại và thực hiện tiếp';
}

header('Content-Type: application/json');
echo \json_encode($res);