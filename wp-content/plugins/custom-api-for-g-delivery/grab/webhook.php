<?php
/*
Webhook for Grab Express
*/

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use GGGMnASDK\Abstraction\Object\SMS as ObjectSMS;
use GDelivery\Libs\Helper\GrabExpress;
use GDelivery\Libs\GBackendService;

class GrabWebhook extends \Abstraction\Core\AApiHook {

    const ECOMMERCE_ORDER_PREFIX = 'ECOMMERCE';
    const VENDOR_NAME = 'grab_express';

    private $logger;
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new Logger('grab-webhook');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/webhook-'.date('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', 'grab/webhook', array(
                'methods' => 'POST',
                'callback' => [$this, "webhookHandle"],
            ) );
        } );
    }

    public function webhookHandle( WP_REST_Request $request )
    {
        $this->requestId = $requestId = \uniqid();
        $this->logger->info("Receive Webhook from Grab; request id: {$requestId}; content: {$request->get_body()}");
        $webhookParams = json_decode($request->get_body());
        
        $deliveryInfo = \GGGGESKD\Abstraction\Object\Delivery::initFromGrabExpress($webhookParams);
        $orderIdData = explode('_', $deliveryInfo->getOrderId());
        if (empty($orderIdData) || $orderIdData[0] != self::ECOMMERCE_ORDER_PREFIX) {
            return $this->response($requestId, \Abstraction\Object\ApiMessage::BAD_REQUEST, 'Cant parse order id');
        }

        $order = wc_get_order($orderIdData[1]);
        if (!$order) {
            return $this->response($requestId, \Abstraction\Object\ApiMessage::NOT_FOUND, 'Order is not exit');
        }

        $grabStatus = $deliveryInfo->getStatus();
        $mappedGrabStatus = GrabExpress::mappingStatus($grabStatus);
        if (!$mappedGrabStatus) {
            return $this->response($requestId, \Abstraction\Object\ApiMessage::BAD_REQUEST, 'Status is not allow to process');
        }

        $order->update_status($mappedGrabStatus, 'Grab change status');
        $this->updateOrderMeta($order, $deliveryInfo);
        $savedOrder = $order->save();
        /**if ($savedOrder) {
            if ($grabStatus == GrabExpress::IN_DELIVERY_STATUS) {
                $this->sendSMSToCustomer($order);
            }   
        }*/

        if ($grabStatus == GrabExpress::FAILED_STATUS) {
            $this->processTransNotFound($order);
        }

        $this->updateReport($order);

        return $this->response($requestId, \Abstraction\Object\ApiMessage::SUCCESS, 'Thành công');
    }

    /**
     * @param \WC_Order $order
     */
    function processTransNotFound($order)
    {
        $gBackendService = new GBackendService();
        $gBackendService->dispatchHandleTransRejectedOrder($order->get_id());
    }

    /**
     * @param \WC_Order $order
     */
    private function updateReport($order)
    {
        $report = new \GDelivery\Libs\Helper\Report();
        $report->updateOrder($order);
    }

    /**
     * @param \WC_Order $order
     */
    private function sendSMSToCustomer($order)
    {
        $customer = get_user_by('id', $order->get_customer_id());
        if (!$customer) {
            return $this->logger->info("Response Webhook for Grab; request id: {$this->requestId}; status: 404; message: can not find customer with id {$order->get_customer_id()}");
        }
        $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchant($order->get_meta('merchant_id'));
        $restaurant = $getMerchant->result->restaurant;
        $messageContent = "Đơn hàng " . self::ECOMMERCE_ORDER_PREFIX . " sẽ được giao đến quý khách trong 30 - 35 phút nữa. Nếu có bất cứ vấn đề gì vui lòng gọi cho hotline hỗ trợ {$restaurant->telephone}.";
        $receiver = $customer->user_login;

        $objectMessage = new ObjectSMS(
            [
                'receiver' => $receiver,
                'brandName' => \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_BRAND_NAME,
                'message' => $messageContent
            ]
        );

        \GDelivery\Libs\Helper\SMS::send($objectMessage, \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_VENDOR);
    }

    /**
     * @param \WC_Order $order
     * @param \GGGGESKD\Abstraction\Object\Delivery $deliveryInfo delivery info from Grab Express
     */
    private function updateOrderMeta(&$order, $deliveryInfo)
    {
        $mapping = [
            'transport_allocating_time' => [GrabExpress::ALLOCATING_STATUS],
            'transport_accepted_time' => [GrabExpress::PICKING_UP_STATUS],
            'transport_on_going_time' => [GrabExpress::IN_DELIVERY_STATUS],
            'transport_returned_time' => [GrabExpress::IN_RETURN_STATUS, GrabExpress::RETURNED_STATUS],
            'transport_delivered_time' => [GrabExpress::COMPLETED_STATUS],
            'transport_rejected_time' => [GrabExpress::CANCELED_STATUS, GrabExpress::FAILED_STATUS],
        ];
        $grabStatus = $deliveryInfo->getStatus();
        $filterMetaKey = array_filter($mapping, function($listGrabStatus, $transportMetaKey) use ($grabStatus) {
            return in_array($grabStatus, $listGrabStatus);
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($filterMetaKey)) {
            $transportMetaKey = array_key_first($filterMetaKey);
            $order->update_meta_data($transportMetaKey, date_i18n('Y-m-d H:i:s'));
        }

        // save grab object
        $exitingDeliveryInfo = $order->get_meta('grab_delivery_object');
        if ($exitingDeliveryInfo) {
            $exitingDeliveryInfo->setStatus($deliveryInfo->getStatus());
            $exitingDeliveryInfo->setDriver($deliveryInfo->getDriver());
            update_post_meta($order->get_id(), 'grab_delivery_object', $exitingDeliveryInfo);
        } else {
            update_post_meta($order->get_id(), 'grab_delivery_object', $deliveryInfo);
        }

        $mappedGrabStatus = GrabExpress::mappingStatus($grabStatus);
        if ($grabStatus == GrabExpress::ALLOCATING_STATUS) {
            $order->update_meta_data('vendor_transport', self::VENDOR_NAME);
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
    }

    private function response($requestId, $messageCode, $message)
    {
        $this->logger->info("Response Webhook for Grab; request id: {$requestId}; status: {$messageCode}; message: {$message}");
        $res = new Result();
        $res->messageCode = $messageCode;
        $res->message = $message;
        Response::returnJson($res);
        die;
    }
}

$grabWebhook = new GrabWebhook();