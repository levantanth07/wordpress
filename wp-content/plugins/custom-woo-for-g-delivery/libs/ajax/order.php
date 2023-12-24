<?php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AjaxOrder extends \Abstraction\Core\AAjaxHook {

    private $grabLogger;

    private $serializer;

    private $locationService;

    public function __construct()
    {
        parent::__construct();

        $this->grabLogger = new Logger('grab-status');
        $this->grabLogger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/delivery-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->grabLogger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $this->serializer = new \Symfony\Component\Serializer\Serializer(
            [
                new ObjectNormalizer(),
                new JsonEncoder()
            ]
        );

        $this->locationService = new \GDelivery\Libs\Location();

        add_action("wp_ajax_order_refund", [$this, "refund"]);
        add_action("wp_ajax_nopriv_order_refund", [$this, "mustLogin"]);

        add_action("wp_ajax_order_detail", [$this, "detail"]);
        add_action("wp_ajax_nopriv_order_refund", [$this, "mustLogin"]);

        add_action("wp_ajax_request_grab_transport", [$this, "requestGrabTransport"]);
        add_action("wp_ajax_nopriv_request_grab_transport", [$this, "mustLogin"]);
    }

    public function refund()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['id'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "order_refund")) {
                $order = wc_get_order($_REQUEST['id']);
                if ($order) {
                    $oldStatus = $order->get_status();
                    $status = \GDelivery\Libs\Helper\Order::STATUS_REFUNDED;
                    $currentUser = wp_get_current_user();

                    $note = "{$currentUser->ID}||{$oldStatus}||{$status}||Hoàn tiền bởi user: {$currentUser->user_login}__";
                    $order->set_status($status, $note);

                    // get order's customer
                    $customer = get_user_by('id', $order->get_customer_id());
                    if ($customer) {
                        // send sms
                        // message
                        $brandName = \GDelivery\Libs\Config::BRAND_NAME;
                        $hotline = \GDelivery\Libs\Config::BRAND_HOTLINE;
                        $message = new \GGGMnASDK\Abstraction\Object\SMS(
                            [
                                'receiver' => $customer->user_login,
                                'brandName' => \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_BRAND_NAME,
                                'message' => "{$brandName} da hoan tien thanh cong cho don hang {$order->get_id()}. Neu can ho tro vui long lien he {$hotline}."
                            ]
                        );
                        //\GDelivery\Libs\Helper\SMS::send($message, \GDelivery\Libs\Config::MESSAGE_SYSTEM_SMS_VENDOR);
                    }

                    $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                    $histories[] = [
                        'status' => $status,
                        'statusText' => 'Đã hoàn tiền',
                        'createdAt' => date_i18n('Y-m-d H:i:s')
                    ];
                    $order->update_meta_data('order_status_histories', $histories);

                    $order->update_meta_data('refunded_time', date_i18n('Y-m-d H:i:s'));
                    // save order
                    $order->save();

                    $orderService = new \GDelivery\Libs\Helper\Order();
                    $orderService->sendNotify($order->get_id(), $status);

                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Đơn hàng không tồn tại.';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass order id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function detail()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['id'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "order_detail")) {
                $order = wc_get_order($_REQUEST['id']);
                if ($order) {
                    $temp = new \stdClass();
                    $temp->id = $order->get_id();
                    $temp->shippingAddress = $order->get_shipping_address_1().', '.$order->get_billing_address_2();

                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $temp;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Đơn hàng không tồn tại.';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass order id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    /**
     * Request with Goong Address service
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function requestGrabTransport()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['orderId'], $_REQUEST['address'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "request_grab_transport")) {

                // get order
                $order = wc_get_order($_REQUEST['orderId']);
                if ($order) {
                    if ($order->get_total('number') > 2000000 && $order->get_meta('payment_method') == 'COD') {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = 'Chỉ được yêu cầu Grab vận chuyển với đơn COD nhỏ hơn 2 triệu';
                    } else {
                        $status = 'trans-requested';
                        $currentUser = wp_get_current_user();
                        $oldStatus = $order->get_status();
                        $note = "{$currentUser->ID}||{$oldStatus}||{$status}||Yêu cầu vận chuyển__";

                        $getTGSRestaurant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($order->get_meta('restaurant_code'));
                        $restaurant = $getTGSRestaurant->result->restaurant;
                        $customer = $order->data['shipping'];
                        $getShippingAddress = $this->locationService->getGoongAddressDeatailByPlaceId($_REQUEST['address']);

                        if ($getShippingAddress->messageCode == \Abstraction\Object\Message::SUCCESS) {
                            $shippingAddress = $getShippingAddress->result;

                            if ($order->get_meta('payment_method') == 'COD') {
                                $cod = new \GGGGESKD\Abstraction\Object\COD(
                                    [
                                        'enable' => true,
                                        'amount' => $order->get_total()
                                    ]
                                );
                            } else {
                                $cod = null;
                            }
                            try {
                                $dataGrabAuth = \GDelivery\Libs\Helper\Helper::getGrabAuthentication();

                                $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);

                                $orderId = 'GDelivery_'.$order->get_id();
                                $service = new \GGGGESKD\Abstraction\Object\Service(
                                    [
                                        'type' => \GGGGESKD\Abstraction\Object\Service::TYPE_INSTANT
                                    ]
                                );

                                // packages
                                $package = new \GGGGESKD\Abstraction\Object\Package(
                                    [
                                        'name' => 'Vận chuyển đơn hàng cho khách hàng '
                                            .$customer['first_name'],
                                        'description' => 'Vận chuyển đơn hàng cho khách hàng '
                                            .$customer['first_name'],
                                        'price' => $order->get_total('number'),
                                        'height' => 0,
                                        'width' => 0,
                                        'depth' => 0,
                                        'weight' => 0
                                    ]
                                );
                                // sender - origin
                                $sender = new \GGGGESKD\Abstraction\Object\People(
                                    [
                                        'firstName' => $restaurant->name,
                                        'phone' => $restaurant->telephone,
                                        'email' => $restaurant->email,
                                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                                            [
                                                'address' => $restaurant->address,
                                                'longitude' => $restaurant->longitude,
                                                'latitude' => $restaurant->latitude
                                            ]
                                        )
                                    ]
                                );
                                // recipient - destination
                                $recipient
                                    = new \GGGGESKD\Abstraction\Object\People(
                                    [
                                        'firstName' => $customer['first_name'],
                                        'phone' => $order->get_meta('_shipping_phone'),
                                        'email' => $order->data['billing']['email'],
                                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                                            [
                                                'address' => $shippingAddress->address,
                                                'longitude' => $shippingAddress->longitude,
                                                'latitude' => $shippingAddress->latitude
                                            ]
                                        )
                                    ]
                                );

                                $this->grabLogger->info(
                                    "Request Delivery: OrderId: {$orderId}; "
                                    ."; Service: "
                                    .\json_encode($this->serializer->normalize($service))
                                    ."; Package: "
                                    .\json_encode($this->serializer->normalize($package))
                                    ."; Sender: "
                                    .\json_encode($this->serializer->normalize($sender))
                                    ."; Recipient: "
                                    .\json_encode($this->serializer->normalize($recipient))
                                    ."; COD: "
                                    .\json_encode($this->serializer->normalize($cod))
                                );
                                $requestDeliveryData = $ge->requestDelivery($orderId, $service,
                                    [$package], $sender, $recipient, $cod);
                                $this->grabLogger->info("Request Delivery: OrderId: {$orderId}; DeliveryId: {$requestDeliveryData->getId()}; Status: {$requestDeliveryData->getStatus()}; Amount: {$requestDeliveryData->getQuote()->getAmount()}; DebugInfo: ".\json_encode($ge->getDebugInfo()));

                                // actual shipping fee
                                $order->update_meta_data('actual_shipping_fee', $requestDeliveryData->getQuote()->getAmount());

                                $mappingStatus = \GDelivery\Libs\Helper\GrabExpress::mappingStatus($requestDeliveryData->getStatus());

                                if (
                                    $mappingStatus == \GDelivery\Libs\Helper\Order::STATUS_TRANS_ALLOCATING
                                ) {
                                    // save status
                                    $order->update_status($mappingStatus, $note); // set new status
                                    $order->update_meta_data('vendor_transport', 'grab_express');
                                    $order->update_meta_data('restaurant-request-cancel-grab', 0);
                                    $order->update_meta_data('grab_delivery_object', $requestDeliveryData);

                                    // save order
                                    $order->save();

                                    // save report
                                    $report = new \GDelivery\Libs\Helper\Report();
                                    $report->updateOrder($order);

                                    $res->messageCode
                                        = \Abstraction\Object\Message::SUCCESS;
                                    $res->message
                                        = 'Đã cập nhật thông tin đơn hàng';
                                    $res->result = $order;
                                } else {
                                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                                    $res->message = "Lỗi khi yêu cầu Grab vận chuyển ({$requestDeliveryData->getStatus()})";
                                }
                            } catch (\Exception $e) {
                                $res->messageCode
                                    = \Abstraction\Object\Message::GENERAL_ERROR;
                                $res->message = 'Lỗi khi yêu cầu Grab vận chuyển: '
                                    .$e->getMessage();
                            }
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Không tìm được địa chỉ giao hàng';
                        }
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Đơn hàng không tồn tại.';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass order id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }
} // end class

// init
$ajaxOrder = new AjaxOrder();