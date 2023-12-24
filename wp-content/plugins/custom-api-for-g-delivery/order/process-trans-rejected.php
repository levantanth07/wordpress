<?php
/*
    ProcessTransRejectedOrder
*/

use Abstraction\Object\Result;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ProcessTransRejectedOrder extends \Abstraction\Core\AApiHook {

    private $logger;

    public function __construct()
    {
        parent::__construct();

        add_action( 'rest_api_init', function () {
            register_rest_route('api/v1/order', '/(?P<id>\d+)/process-trans-rejected', array(
                'methods' => 'POST',
                'callback' => [$this, "processTransRejectedOrder"],
            ));
        } );

        $this->logger = new Logger('g-backend');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/g-backend/process-trans-rejected-order-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
    }

    public function processTransRejectedOrder(WP_REST_Request $request)
    {
        $orderId = isset($request['id']) ? $request['id'] : null;
        if (!$orderId) {
            return $this->response(\Abstraction\Object\ApiMessage::NOT_FOUND, 'Cần truyền đúng orderId');
        }
        $order = wc_get_order($orderId);
        if (!$order) {
            return $this->response(\Abstraction\Object\ApiMessage::GENERAL_ERROR, 'Order không tồn tại');
        }
        $merchantId = $order->get_meta('merchant_id');
        $merchantShippingPartner = get_post_meta($merchantId, 'merchant_shipping_partner', true) ?? [];
        if (in_array('self', $merchantShippingPartner)) { // HAS SELF SHIPPING - DO NOT CANCEL
            return $this->response(\Abstraction\Object\ApiMessage::SUCCESS_WITHOUT_DATA, 'NO NEED TO CANCEL');
        }
        try{
            $this->doCancelOrder($order);
            $this->logger->info("ProcessTransRejectedOrder successfully. OrderId: $orderId");
        }catch(\Exception $e) {
            $this->logger->info("ProcessTransRejectedOrder has an error. OrderId: $orderId; " . $e->getMessage());
            return $this->response(\Abstraction\Object\ApiMessage::INTERNAL_SERVER_ERROR, 'ERROR');
        }
        return $this->response(\Abstraction\Object\ApiMessage::SUCCESS, 'OK');
    }

    /**
     * @param \WC_Order $order
     */
    private function doCancelOrder($order){
        $oldStatus = $order->get_status();
        $customerInfo = $order->get_meta('customer_info');
        $paymentMethod = $order->get_meta('payment_method');
        $paymentService = new \GDelivery\Libs\PaymentHubService();
        $status = \GDelivery\Libs\Helper\Order::STATUS_CANCELLED;
        // release amount if pay with BizAccount
        if (
            $oldStatus != 'cancelled'
            && $paymentMethod == \GDelivery\Libs\Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME
        ) {
            $customerNumber = $customerInfo->customerNumber;
            $holdId = $order->get_meta('gbiz_hold_id');
            if ($holdId) {
                $doReleaseBalance = $paymentService->releaseBalance($customerNumber, $holdId);
                if ($doReleaseBalance->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $order->delete_meta_data('gbiz_hold_id');
                }
            }
        }
        // process vouchers
        $selectedVouchers = $order->get_meta('selected_vouchers');
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $one) {
                $paymentService->cancelVoucher($one->code, $order);
            }
        }
        // status
        $order->update_status($status, 'Không tìm thấy tài xế'); // set new status
        $order->update_meta_data('cancelled_time', date_i18n('Y-m-d H:i:s'));
        $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
        $histories[] = [
            'status' => $status,
            'statusText' => 'Đã hủy',
            'createdAt' => date_i18n('Y-m-d H:i:s')
        ];
        $order->update_meta_data('order_status_histories', $histories);
        $order->save();
        $orderService = new \GDelivery\Libs\Helper\Order();
        $orderService->sendNotify($order->get_id(), $status);
        // $this->sendSMSToCustomer($order);
    }

    /**
     * @param \WC_Order $order
     */
    private function sendSMSToCustomer($order)
    {
        $customer = get_user_by('id', $order->get_customer_id());
        if (!$customer) {
            return;
        }
        $orderCode = 'ECOMMERCE_' . $order->get_id();
        $messageContent = "Đơn hàng {$orderCode} đã bị hủy vì lý do không tìm thấy tài xế giao hàng.";
        $receiver = $customer->user_login;
        $objectMessage = new \GGGMnASDK\Abstraction\Object\SMS(
            [
                'receiver' => $receiver,
                'brandName' => \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_BRAND_NAME,
                'message' => $messageContent
            ]
        );
        \GDelivery\Libs\Helper\SMS::send($objectMessage, \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_VENDOR);
    }

    private function response($messageCode, $message)
    {
        $res = new Result();
        $res->messageCode = $messageCode;
        $res->message = $message;
        return $res;
    }
}
$processTransRejectedOrder = new ProcessTransRejectedOrder();