<?php
/*
Template Name: Grab webhook handler
*/

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use GGGMnASDK\Abstraction\Object\SMS as ObjectSMS;
use GDelivery\Libs\Helper\GrabExpress;

$strBody = file_get_contents('php://input');
$input = json_decode($strBody);

$requestId = \uniqid();
$logger = new Logger('grab-webhook');
$logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/webhook-'.date('Y-m-d').'.log', Logger::DEBUG));
$logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

// log received request
$logger->info("Receive Webhook from Grab; request id: {$requestId}; content: {$strBody}");

// parse to delivery object
$deliveryInfo = \GGGGESKD\Abstraction\Object\Delivery::initFromGrabExpress($input);

// request order id in format: ECOMMERCE_123, so to get order id, need to part that string and get the second element
$arrTemp = explode('_', $deliveryInfo->getOrderId());
if ($arrTemp && $arrTemp[0] == 'ECOMMERCE') {
    $order = wc_get_order($arrTemp[1]);

    if ($order) {
        $grabStatus = $deliveryInfo->getStatus();
        $mappedGrabStatus = GrabExpress::mappingStatus($grabStatus);
        if ($mappedGrabStatus) {
            // check trans-going send sms
            $orderStatus = $order->get_status();

            switch ($grabStatus) {
                case GrabExpress::ALLOCATING_STATUS:
                    $order->update_meta_data('transport_allocating_time', date_i18n('Y-m-d H:i:s'));
                    break;

                case GrabExpress::PICKING_UP_STATUS:
                    $order->update_meta_data('transport_accepted_time', date_i18n('Y-m-d H:i:s'));
                    break;

                case GrabExpress::IN_DELIVERY_STATUS:
                    $order->update_meta_data('transport_on_going_time', date_i18n('Y-m-d H:i:s'));
                    break;

                case GrabExpress::IN_RETURN_STATUS:
                case GrabExpress::RETURNED_STATUS:
                    $order->update_meta_data('transport_returned_time', date_i18n('Y-m-d H:i:s'));
                    break;

                case GrabExpress::COMPLETED_STATUS:
                    $order->update_meta_data('transport_delivered_time', date_i18n('Y-m-d H:i:s'));
                    break;

                case GrabExpress::CANCELED_STATUS:
                case GrabExpress::FAILED_STATUS:
                    $order->update_meta_data('transport_rejected_time', date_i18n('Y-m-d H:i:s'));
                    break;
            }
            $order->update_status($mappedGrabStatus, 'Grab change status');

            // save grab object
            $exitingDeliveryInfo = $order->get_meta('grab_delivery_object');
            if ($exitingDeliveryInfo) {
                $exitingDeliveryInfo->setStatus($deliveryInfo->getStatus());
                $exitingDeliveryInfo->setDriver($deliveryInfo->getDriver());

                update_post_meta($order->get_id(), 'grab_delivery_object', $exitingDeliveryInfo);
            } else {
                update_post_meta($order->get_id(), 'grab_delivery_object', $deliveryInfo);
            }

            if ($grabStatus == GrabExpress::COMPLETED_STATUS) {
                $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                $histories[] = [
                    'status' => $mappedGrabStatus,
                    'statusText' => 'Đã giao hàng',
                    'createdAt' => date_i18n('Y-m-d H:i:s')
                ];
                $order->update_meta_data('order_status_histories', $histories);
            }

            if ($grabStatus == GrabExpress::PICKING_UP_STATUS) {
                $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                $histories[] = [
                    'status' => $mappedGrabStatus,
                    'statusText' => 'Đang giao hàng',
                    'createdAt' => date_i18n('Y-m-d H:i:s')
                ];
                $order->update_meta_data('order_status_histories', $histories);
            }

            // save order
            if ($order->save()) {
                if ($grabStatus == GrabExpress::IN_DELIVERY_STATUS) {
                    // Send SMS for customer
                    /*$customer = get_user_by('id', $order->get_customer_id()); // get order's customer
                    if ($customer) {
                        $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchant($order->get_meta('merchant_id'));
                        $restaurant = $getMerchant->result->restaurant;
                        $messageContent = "Đơn hàng G-delivery sẽ được giao đến quý khách trong 30 - 35 phút nữa. Nếu có bất cứ vấn đề gì vui lòng gọi cho hotline hỗ trợ {$restaurant->telephone}.";
                        $receiver = $customer->user_login;

                        $objectMessage = new ObjectSMS(
                            [
                                'receiver' => $receiver,
                                'brandName' => \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_BRAND_NAME,
                                'message' => $messageContent
                            ]
                        );

                        \GDelivery\Libs\Helper\SMS::send($objectMessage, \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_VENDOR);
                    }*/
                }
            }

            // save report
            $report = new \GDelivery\Libs\Helper\Report();
            $report->updateOrder($order);

            $responseCode = 200;
            $responseMessage = 'ok';
        } else {
            $responseCode = 400;
            $responseMessage = 'Status not allow to process ';
        }
    } else {
        $responseCode = 404;
        $responseMessage = 'order not exit';
    }
} else {
    $responseCode = 400;
    $responseMessage = 'cant parse order id';
}

// finally, log response
$logger->info("Response Webhook for Grab; request id: {$requestId}; status: {$responseCode}; message: {$responseMessage}");

http_response_code($responseCode);
echo $responseMessage;