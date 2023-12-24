<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;
use GDelivery\Libs\Helper\Order;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class GrabExpress {

    const QUEUEING_STATUS = 'QUEUEING';
    const ALLOCATING_STATUS = 'ALLOCATING';
    const CANCELED_STATUS = 'CANCELED';
    const PENDING_PICKUP_STATUS = 'PENDING_PICKUP';
    const FAILED_STATUS = 'FAILED';
    const PICKING_UP_STATUS = 'PICKING_UP';
    const PENDING_DROP_OFF_STATUS = 'PENDING_DROP_OFF';
    const IN_DELIVERY_STATUS = 'IN_DELIVERY';
    const IN_RETURN_STATUS = 'IN_RETURN';
    const RETURNED_STATUS = 'RETURNED';
    const COMPLETED_STATUS = 'COMPLETED';

    const ECOMMERCE_ORDER_PREFIX = 'ECOMMERCE';

    private $auth, $redis, $logger, $loggerStatus;

    /**
     * @param \WC_Order $order
     */
    public static function mappingStatus($grabStatus) {
        
        $mappingStatus = [
            Order::STATUS_CONFIRMED => [self::QUEUEING_STATUS],
            Order::STATUS_TRANS_ALLOCATING => [self::ALLOCATING_STATUS],
            Order::STATUS_PROCESSING => [self::PICKING_UP_STATUS],
            Order::STATUS_TRANS_GOING => [self::IN_DELIVERY_STATUS],
            Order::STATUS_TRANS_RETURNED => [self::IN_RETURN_STATUS, self::RETURNED_STATUS],
            Order::STATUS_TRANS_DELIVERED => [self::COMPLETED_STATUS],
            Order::STATUS_TRANS_REJECTED => [self::FAILED_STATUS],
        ];

        $filterStatus = array_filter($mappingStatus, function($listGrabStatus, $gggStatus) use ($grabStatus) {
            return in_array($grabStatus, $listGrabStatus);
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($filterStatus)) {
            return '';
        }

        return array_key_first($filterStatus);
    }

    public function __construct()
    {
        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );

        $this->logger = new Logger('grab-express');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/grab-quote-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));


        $this->loggerStatus = new Logger('grab-status');
        $this->loggerStatus->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/delivery-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->loggerStatus->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    private function getGrabAuthentication()
    {
        $startTime = microtime(true);

        $dataGrabAuth = get_option('dataGrabAuth');

        if ($dataGrabAuth && $dataGrabAuth->getToken()->getExpiry() > time()) {
            $ge = $dataGrabAuth;
        } else {
            try {
                $auth = new \GGGGESKD\Service\Authentication(
                    \GDelivery\Libs\Config::GRAB_ENV,
                    [
                        'clientId' => \GDelivery\Libs\Config::GRAB_CLIENT_ID,
                        'clientSecret' => \GDelivery\Libs\Config::GRAB_CLIENT_SECRET,
                    ]
                );

                // start log
                $this->logger->info("Grab Authentication with: clientId: ".\GDelivery\Libs\Config::GRAB_CLIENT_ID."; clientSecret: ".\GDelivery\Libs\Config::GRAB_CLIENT_SECRET);

                // do auth
                $auth->auth();

                // result log
                $this->logger->info((microtime(true) - $startTime)."||||Authentication: {$auth->getToken()->getAccessToken()}");

                update_option('dataGrabAuth', $auth);

                $this->auth = $auth;
                $ge = $auth;
            } catch (\Exception $e) {
                $this->logger->error("Grab Authentication: Exception: {$e->getMessage()}");
                $ge = null;
            }
        }

        return $ge;
    }

    public function calculateShippingFee($restaurant, $customerAddress, $extraPriceFee = null)
    {
        $getRestaurantInfo = getMerchantByCode($restaurant->code);
        $dataGrabAuth = Helper::getGrabAuthentication();

        $res = new Result();

        if ($getRestaurantInfo->result->allowGrabExpress == 1 && isset($dataGrabAuth)) {
            $startRequestTime = microtime(true);

            $cacheKey = 'GEQuote:'.md5($restaurant->longitude.$restaurant->latitude.$customerAddress->longitude.$customerAddress->latitude);

            $getCache = $this->redis->get($cacheKey);

            if ($getCache) {
                $quoteData = unserialize($getCache);
            } else {
                // use package symfony/serializer
                $serializer = new Serializer(
                    [new ObjectNormalizer()],
                    [new JsonEncoder()]
                );

                try {
                    $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);

                    $orderId = \uniqid();

                    $service = new \GGGGESKD\Abstraction\Object\Service(
                        [
                            'type' => \GGGGESKD\Abstraction\Object\Service::TYPE_INSTANT
                        ]
                    );

                    // packages
                    $package = new \GGGGESKD\Abstraction\Object\Package(
                        [
                            'name' => 'Vận chuyển đơn hàng cho khách hàng ',
                            'description' => 'Vận chuyển đơn hàng cho khách hàng ',
                            'price' => 0,
                            'height' => 0,
                            'width' => 0,
                            'depth' => 0,
                            'weight' => 0
                        ]
                    );

                    // sender - origin
                    $sender = new \GGGGESKD\Abstraction\Object\People(
                        [
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
                    $recipient = new \GGGGESKD\Abstraction\Object\People(
                        [
                            'address' => new \GGGGESKD\Abstraction\Object\Address(
                                [
                                    'address' => $customerAddress->address,
                                    'longitude' => $customerAddress->longitude,
                                    'latitude' => $customerAddress->latitude
                                ]
                            )
                        ]
                    );

                    // start log
                    $this->logger->info("Request Quote: Service: ".json_encode($serializer->normalize($service))."; Package: ".json_encode($serializer->normalize([$package]))."; Sender: ".json_encode($serializer->normalize($sender))."; Recipient: ".json_encode($serializer->normalize($recipient)).";");

                    // request
                    $quoteData = $ge->quote($orderId, $service, [$package], $sender, $recipient);

                    // response log
                    $this->logger->info((microtime(true) - $startRequestTime)."||||Quote Data: Amount: {$quoteData->getAmount()}{$quoteData->getCurrency()->getSymbol()}; Distance: {$quoteData->getDistance()}m; quoteData: ".json_encode($serializer->normalize($quoteData)).";");

                    // set to cache
                    $this->redis->set($cacheKey, serialize($quoteData));
                } catch (\GGGGESKD\Exception\ApiRequestException $e) {
                    if ($e->getResponse()) {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getResponse()->getBody()->getContents();
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getRequest();
                    }
                    $this->logger->error((microtime(true) - $startRequestTime)."||||Request Quote; ApiException: {$e->getMessage()}");
                } catch (\Exception $e) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to get quote: '.$e->getMessage();
                    $this->logger->error((microtime(true) - $startRequestTime)."||||Request Quote; Exception: {$e->getMessage()}");
                }
            }

            if ($quoteData) {
                $temp = new \stdClass();

                $extraAmount = 0;
                if ($extraPriceFee !== null) {
                    $extraAmount = $extraPriceFee;
                }

                // cause grab quote amount include tax, do need to re-calculate price/tax and total
                // for all number is rounded, recalculate tax first, round tax up
                // calculate price base on rounded tax
                // and total is rounded price + rounded tax
                $roundTax = ceil(($quoteData->getAmount() / 1.1) * 0.1 + $extraAmount * 0.1);
                $temp->price = ($roundTax * 10);
                $temp->tax = $roundTax;
                $temp->total = $temp->price + $temp->tax;
                $temp->actualAmountIncludeTax = $quoteData->getAmount(); // also include actual amount include tax

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi lấy thông tin báo giá';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Nhà hàng không hỗ trợ tính phí Grab';
        }

        return $res;
    }

    /**
     * @param \WC_Order $order
     * 
     * @return Result
     */
    public function pushOrder($order)
    {
        $res = new Result();
        $paymentMethod = $order->get_meta('payment_method');
        if ($order->get_total('number') > 2000000 && $paymentMethod == 'COD') {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Chỉ được yêu cầu Grab vận chuyển với đơn COD nhỏ hơn 2 triệu';
            return $res;
        }
        $getGDeliveryRestaurant = \GDelivery\Libs\Helper\Helper::getMerchant($order->get_meta('merchant_id'));
        $restaurant = $getGDeliveryRestaurant->result->restaurant;
        $customer = $order->data['shipping'];
        $customerAddress = $order->get_meta('customer_selected_address');
        if ($paymentMethod == 'COD') {
            $totalPaySum = $order->get_meta('total_pay_sum');
            $cod = new \GGGGESKD\Abstraction\Object\COD(
                [
                    'enable' => true,
                    'amount' => $totalPaySum
                ]
            );
        } else {
            $cod = null;
        }
        try {
            $serializer = new \Symfony\Component\Serializer\Serializer(
                [
                    new ObjectNormalizer(),
                    new JsonEncoder()
                ]
            );
            $dataGrabAuth = \GDelivery\Libs\Helper\Helper::getGrabAuthentication();

            $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);

            $orderId = self::ECOMMERCE_ORDER_PREFIX . '_' . $order->get_id();
            $service = new \GGGGESKD\Abstraction\Object\Service(
                [
                    'type' => \GGGGESKD\Abstraction\Object\Service::TYPE_INSTANT
                ]
            );

            $schedule = null;
            $isScheduleShipping = $order->get_meta('is_delivery_now') != 1 ? true : false;
            if ($isScheduleShipping) {
                $deliveryTime = explode(' - ', $order->get_meta('delivery_time'));
                $deliveryDate = explode('/', $order->get_meta('delivery_date'));
                $currentTime = strtotime(date_i18n('Y-m-d H:i:s'));
                $customerDeliveryTime = strtotime(join('-', array_reverse($deliveryDate)) . ' ' . $deliveryTime[0]);

                if ($currentTime >= $customerDeliveryTime) {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Đã quá thời gian giao hàng';
                    return $res;
                }

                $distanceData = (new \GDelivery\Libs\Location())->vincentyGreatCircleDistance(
                    $restaurant->latitude,
                    $restaurant->longitude,
                    $customerAddress->latitude,
                    $customerAddress->longitude
                );
                $distanceRestaurantToCustomer = round($distanceData->result * 1.3 / 1000, 2);
                $shippingTimeInMinutes = floor($distanceRestaurantToCustomer * 5 + 20);
                $shippingTimeFromAsInt = $customerDeliveryTime - $shippingTimeInMinutes*60;
                $schedule = new \GGGGESKD\Abstraction\Object\Schedule(
                    [
                        'pickupTimeFrom' => date_i18n(\DateTimeInterface::RFC3339, $shippingTimeFromAsInt),
                        'pickupTimeTo' => date_i18n(\DateTimeInterface::RFC3339, $shippingTimeFromAsInt + 15*60),
                    ]
                );                
            }

            // packages
            $package = new \GGGGESKD\Abstraction\Object\Package(
                [
                    'name' => 'Vận chuyển đơn hàng cho khách hàng ' . $customer['first_name'],
                    'description' => 'Vận chuyển đơn hàng cho khách hàng ' . $customer['first_name'],
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
            $recipient = new \GGGGESKD\Abstraction\Object\People(
                [
                    'firstName' => $customer['first_name'],
                    'phone' => $order->get_shipping_phone(),
                    'email' => $order->data['billing']['email'],
                    'address' => new \GGGGESKD\Abstraction\Object\Address(
                        [
                            'address' => $customerAddress->address,
                            'longitude' => $customerAddress->longitude,
                            'latitude' => $customerAddress->latitude
                        ]
                    )
                ]
            );

            $this->loggerStatus->info(
                "Request Delivery: OrderId: {$orderId}; "
                ."; Service: ".\json_encode($serializer->normalize($service))
                ."; Package: ".\json_encode($serializer->normalize($package))
                ."; Sender: ".\json_encode($serializer->normalize($sender))
                ."; Recipient: ".\json_encode($serializer->normalize($recipient))
                ."; COD: ".\json_encode($serializer->normalize($cod))
                ."; Schedule: ".\json_encode($serializer->normalize($schedule))
            );
            $requestDeliveryData = $ge->requestDelivery($orderId, $service, [$package], $sender, $recipient, $cod, $schedule);
            $this->loggerStatus->info("Request Delivery: OrderId: {$orderId}; DeliveryId: {$requestDeliveryData->getId()}; Status: {$requestDeliveryData->getStatus()}; Amount: {$requestDeliveryData->getQuote()->getAmount()}; DebugInfo: ".\json_encode($ge->getDebugInfo()));
            
            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Tạo đơn Grab thành công';
            $res->result = $requestDeliveryData;
            return $res;
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Lỗi khi yêu cầu Grab vận chuyển: '.$e->getMessage();
            return $res; 
        }   
    }

    /**
     * @param \WC_Order $order
     * 
     * @return Result
     */
    public function cancelOrder($order)
    {
        $res = new Result();
        $grabData = $order->get_meta('grab_delivery_object');
        $dataGrabAuth = \GDelivery\Libs\Helper\Helper::getGrabAuthentication();
        $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);
        try{
            $grabCancelResult = $ge->cancelDelivery($grabData->getId());
            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = "Đã hủy [Grab Express] thành công";
            $this->loggerStatus->info("[Grab Express] Cancel Delivery: OrderId: {$order->get_id()}; Response: " . \json_encode($grabCancelResult));
        }catch(\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = "Có lỗi khi thực hiện hủy [Grab Express]";
        }
        return $res;
    }

} // end class
