<?php
/*
Api for Order

*/

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Abstraction\Object\ApiMessage;
use GDelivery\Libs\Helper\Helper;

class OrderApi extends \Abstraction\Core\AApiHook {

    private $logger;
    private $bookingService;
    private $paymentService;
    private $serializer;
    private $masService;


    public function __construct()
    {
        parent::__construct();

        $this->bookingService = new \GDelivery\Libs\BookingService();
        $this->paymentService = new \GDelivery\Libs\PaymentHubService();
        $this->masService = new \GDelivery\Libs\MasOfferService();

        $this->serializer = new \Symfony\Component\Serializer\Serializer(
            [
                new ObjectNormalizer(),
                new JsonEncoder()
            ]
        );

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1/orders', '/customer/list', array(
                'methods' => 'GET',
                'callback' => [$this, "listOrderByCustomer"],
            ) );
            register_rest_route( 'api/v1/orders', '/customer/(?P<id>\d+)/detail', array(
                'methods' => 'GET',
                'callback' => [$this, "getOrderByCustomer"],
            ) );
            register_rest_route( 'api/v1/orders', '/create', array(
                'methods' => 'POST',
                'callback' => [$this, "createOrder"],
            ) );
            register_rest_route( 'api/v1/orders', '/(?P<id>\d+)/update', array(
                'methods' => 'PUT',
                'callback' => [$this, "updateOrder"],
            ) );
            register_rest_route( 'api/v1/orders', '/(?P<id>\d+)/update-status', array(
                'methods' => 'PUT',
                'callback' => [$this, "updateOrderStatus"],
            ) );
            register_rest_route( 'api/v1/orders', '/(?P<id>\d+)/delete', array(
                'methods' => 'DELETE',
                'callback' => [$this, "deleteOrder"],
            ) );
            register_rest_route( 'api/v1', '/order/(?P<id>\d+)/update-payment-data', array(
                'methods' => 'PUT',
                'callback' => [$this, "updatePaymentDataOrder"],
            ) );
            register_rest_route( 'api/v1', '/order/make-item-note', array(
                'methods' => 'POST',
                'callback' => [$this, "makeItemNote"],
            ) );
            register_rest_route( 'api/v1', '/order/make-change-item', array(
                'methods' => 'POST',
                'callback' => [$this, "makeChangeItem"],
            ) );
        } );

        $this->logger = new Logger('be-gdelivery');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/be-gdelivery/be-gdelivery-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
    }

    public function createOrder(WP_REST_Request $request)
    {
        $res = new Result();
        try {

            $this->logger->info("Request BE Create Order DATA: ".\json_encode($request->get_params()));

            // Todo Validate Request Data
            $deliveryInfo = $request['deliveryInfo'];
            $customerNumber = $request['customerNumber'];
            $merchantId = $request['merchantId'];
            //$categoryId = $request['categoryId'];

            $validate = $this->validateOrder($request->get_params());
            if ($validate->status) {
                $customer = get_user_by('login', $customerNumber);

                if(!$customer) {
                    $cellphone = $customerNumber;
                    $user_id = wp_insert_user(
                        [
                            'user_login' => $cellphone,
                            'user_pass' => isset($request['password']) ? $request['password'] : \GDelivery\Libs\Helper\User::randomPassword(8),
                            'first_name' => $cellphone,
                            'last_name' => '',
                            'user_email' => $cellphone.'@fake_email.com',
                            'role' => 'customer'
                        ]
                    );
                    if ($user_id) {
                        $customer = get_user_by( 'login', $customerNumber );
                    }
                }

                if ($customer) {

                    $restaurantCode = $request['restaurantCode'];
                    $options = [
                        'fromLatitude' => $deliveryInfo['latitude'] ?? null,
                        'fromLongitude' => $deliveryInfo['longitude'] ?? null
                    ];
                    $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchant($merchantId, $options);
                    if ($getMerchant->messageCode == Message::SUCCESS) {

                        $merchant = $getMerchant->result;

                        $validTime = \GDelivery\Libs\Helper\Order::checkTimeOrder(
                            $merchant,
                            $deliveryInfo['deliveryDate'],
                            $deliveryInfo['deliveryTime']
                        );
                        if (!$validTime['isValid']) {
                            $res->messageCode = \Abstraction\Object\Message::MOVED_PERMANENTLY;
                            $res->message = 'Thời gian hẹn giao hàng không còn hiệu lực. Vui lòng kiểm tra lại';
                            $res->result = [
                                'validDate' => $validTime['validDate'],
                                'validTimes' => $validTime['validTimes'],
                            ];

                            \GDelivery\Libs\Helper\Response::returnJson($res);
                            die;
                        }

                        $paymentMethod = isset($request['paymentMethod']) ? $request['paymentMethod'] : null;
                        if ($paymentMethod) {

                            // Now we create the order
                            $order = wc_create_order();

                            $recipientName = $deliveryInfo['recipientName'];
                            $recipientCellphone = $deliveryInfo['recipientCellphone'];
                            $addressLine1 = $deliveryInfo['deliveryAddress'];
                            $pickupAtRestaurant = (isset($deliveryInfo['pickupAtRestaurant']) && $deliveryInfo['pickupAtRestaurant']) ? 1 : 0;
                            $isDeliveryNow = (isset($deliveryInfo['isDeliveryNow']) && $deliveryInfo['isDeliveryNow']) ? 1 : 0;

                            // shipping address
                            if ($pickupAtRestaurant == 1) {
                                $shippingAddress = [
                                    'first_name' => $recipientName,
                                    'phone' => $recipientCellphone,
                                    'email' => $customer->user_email,
                                    'address_1' => 'Nhận tại cửa hàng',
                                ];
                            } else {
                                $shippingAddress = [
                                    'first_name' => $recipientName,
                                    'email' => $customer->user_email,
                                    'phone' => $recipientCellphone,
                                    'address_1' => $addressLine1,
                                    'address_2' => "{$deliveryInfo['wardName']}, {$deliveryInfo['districtName']}, {$deliveryInfo['provinceName']}",
                                ];
                            }


                            $order->update_meta_data(
                                'is_delivery_now',
                                $isDeliveryNow
                            );

                            $order->update_meta_data(
                                'is_pickup_at_restaurant',
                                $pickupAtRestaurant
                            );

                            $billingAddress = [
                                'first_name' => $recipientName,
                                'last_name' => '',
                                'company' => '',
                                'email' => wp_get_current_user()->user_email,
                                'phone' => $recipientCellphone,
                                'address_1' => $addressLine1,
                                'address_2' => "{$deliveryInfo['wardName']}, {$deliveryInfo['districtName']}, {$deliveryInfo['provinceName']}",
                                'city' => '',
                                'state' => '',
                                'postcode' => '',
                                'country' => ''
                            ];

                            $order->set_address($billingAddress, 'billing');
                            $order->set_address($shippingAddress, 'shipping');

                            $totalDiscount = $request['totalDiscount'];
                            $coupons = $request['coupons'];

                            //$processedVouchers = [];
                            $processingClmVouchers = get_option('processing_clm_vouchers', []);
                            if ($totalDiscount > 0) {
                                foreach (
                                    $coupons as $couponCode => $coupon
                                ) {
                                    $order->add_coupon($couponCode, $totalDiscount);
                                    $processingClmVouchers[] = $coupon['code'];
                                    //$processedVouchers[] = (object)$coupon;
                                }
                            }

                            $items = $request['items'];
                            $arrProductId = [];
                            foreach ($items as $key => $item) {
                                $price = $item['salePrice'] ?: $item['regularPrice'];
                                if (isset($item['variationId']) && $item['variationId']) {
                                    $tempProductId = $item['variationId'];
                                } else {
                                    $tempProductId = $item['productId'];
                                }
                                $arrProductId[] = $tempProductId;
                                $tempProductInfo = wc_get_product($tempProductId);
                                $indexItem = $order->add_product($tempProductInfo, $item['quantity']);
                                wc_update_order_item_meta($indexItem, '_line_total', $price * $item['quantity']);
                                //wc_update_order_item_meta($indexItem, '_line_subtotal', $price * $item['quantity']);
                                if (isset($item['modifier']) && $item['modifier']) {
                                    $modifiers = [];
                                    foreach ($item['modifier'] as $itemModifier) {
                                        $modifier = new \stdClass();
                                        $modifier->categoryId = $itemModifier['categoryId'];
                                        $modifier->categoryName = $itemModifier['categoryName'];
                                        $modifier->name = $itemModifier['name'];
                                        $dataModifiers = [];
                                        foreach ($itemModifier['data'] as $itemDataModifier) {
                                            $dataModifier = new \stdClass();
                                            $dataModifier->id =  $itemDataModifier['id'];
                                            $dataModifier->name =  $itemDataModifier['name'];
                                            $dataModifiers[] = $dataModifier;
                                        }
                                        $modifier->data = $dataModifiers;
                                        $modifiers[] = $modifier;
                                    }
                                    wc_update_order_item_meta($indexItem, 'modifier', $modifiers);
                                }
                                if (isset($item['comboData']) && $item['comboData']) {
                                    wc_update_order_item_meta($indexItem, 'comboData', $item['comboData']);
                                }
                                if (isset($item['lineItemId']) && $item['lineItemId']) {
                                    wc_update_order_item_meta($indexItem, 'lineItemId', $item['lineItemId']);
                                }
                                if (isset($item['parentLineItemId']) && $item['parentLineItemId']) {
                                    wc_update_order_item_meta($indexItem, 'parentLineItemId', $item['parentLineItemId']);
                                }
                                if (isset($item['comboProductItemLineItemId']) && $item['comboProductItemLineItemId']) {
                                    wc_update_order_item_meta($indexItem, 'comboProductItemLineItemId', $item['comboProductItemLineItemId']);
                                }
                                wc_update_order_item_meta($indexItem, 'salePrice', $item['salePrice']);
                                wc_update_order_item_meta($indexItem, 'regularPrice', $item['regularPrice']);
                                wc_update_order_item_meta($indexItem, 'priceAfterTax', $item['priceAfterTax']);
                                wc_update_order_item_meta($indexItem, 'sapPriceAfterTax', $item['sapPriceAfterTax']);
                            }

                            $category = wp_get_object_terms($arrProductId, 'merchant-category');
                            $brands = Helper::getCategoryByLevel($category, 0);
                            $arrBrandName = wp_list_pluck($brands, 'name');
                            $order->update_meta_data('brands', implode(', ', $arrBrandName));

                            $note = $deliveryInfo['note'];
                            $noteForDriver = $deliveryInfo['noteForDriver'];
                            $order->set_customer_id($customer->id);
                            $order->set_customer_note($note);  // Add the note
                            $order->update_meta_data('note_for_driver', $noteForDriver);

                            $invoice = isset($request['invoice']) ? $request['invoice'] : ['info' => 0];

                            // set shipping fee
                            $shipping = $request['shipping'];
                            $totalShipping = isset($shipping['total']) ? (float)$shipping['total'] : 0;
                            if ($totalShipping > 0) {
                                $order->set_shipping_tax(isset($shipping['tax']) ? (float)$shipping['tax'] : 0);
                                $order->update_meta_data('shipping_price',
                                    isset($shipping['price']) ? (float)$shipping['price'] : 0);
                            } else {
                                $order->set_shipping_tax(0);
                                $order->update_meta_data('shipping_price', 0);
                            }
                            $order->set_shipping_total($totalShipping);
                            $order->update_meta_data('shipping_distance', $shipping['distance'] ?? 0);

                            $totalTax = $request['totalTax'];
                            // add total tax
                            $order->set_cart_tax($totalTax);
                            $order->update_meta_data('total_tax', $totalTax);

                            // add discount total
                            $totalDiscount = isset($request['totalDiscount']) ? (float)$request['totalDiscount'] : 0;
                            $order->set_discount_total($totalDiscount);

                            // total price
                            $totalPrice = isset($request['totalPrice']) ? (float)$request['totalPrice'] : 0;
                            $order->update_meta_data('total_price', $totalPrice);

                            // total
                            $total = isset($request['total']) ? (float)$request['total'] : 0;
                            $order->set_total($total);

                            // total pay sum
                            $totalAfterTax = isset($request['totalAfterTax']) ? (float)$request['totalAfterTax'] : 0;
                            $order->update_meta_data('total_after_tax', $totalAfterTax);

                            // totalBeforeTax
                            $totalBeforeTax = isset($totals['totalBeforeTax']) ? (float)$totals['totalBeforeTax'] : 0;
                            $order->update_meta_data('total_before_tax', $totalBeforeTax);

                            // total pay sum
                            $totalPaySum = isset($request['totalPaySum']) ? (float)$request['totalPaySum'] : 0;
                            $order->update_meta_data('total_pay_sum', $totalPaySum);

                            if (!empty($request['sale_channel'])) {
                                $order->update_meta_data('sale_channel', $request['sale_channel']);
                            }

                            // total Price Without Shipping
                            $totalPriceWithoutShipping = isset($request['totalPriceWithoutShipping']) ? (float)$request['totalPriceWithoutShipping'] : 0;
                            $order->update_meta_data('total_price_without_shipping', $totalPriceWithoutShipping);

                            // save
                            $order->save();

                            // update meta
                            $deliveryTime = $deliveryInfo['deliveryTime'];
                            update_post_meta($order->get_id(), 'delivery_time', $deliveryTime);

                            $deliveryDate = $deliveryInfo['deliveryDate'];
                            update_post_meta($order->get_id(), 'delivery_date', $deliveryDate);

                            // restaurant
                            $order->update_meta_data('merchant_in_cms', $merchant);
                            $order->update_meta_data('merchant_id', $merchant->id);
                            $order->update_meta_data('restaurant_code', $restaurantCode);
                            $order->update_meta_data('restaurant_object', $merchant->restaurant);
                            $order->update_meta_data('restaurant_in_tgs', $merchant);

                            $restaurantHistories = [];
                            $restaurantHistories[] = [
                                'time' => time(),
                                'restaurant' => $merchant
                            ];
                            update_post_meta(
                                $order->get_id(),
                                'restaurant_histories',
                                $restaurantHistories
                            );

                            // payment
                            update_post_meta($order->get_id(), 'payment_method', $paymentMethod);

                            $provinceBrand = $merchant->restaurant->province->id . '_' . $merchant->restaurant->brand->id;
                            $order->update_meta_data('province_brand', $provinceBrand); // use for operator filter

                            // invoice
                            update_post_meta($order->get_id(), 'customer_invoice', $invoice);

                            // product category
                            /*update_post_meta(
                                $order->get_id(),
                                'current_product_category_id',
                                $categoryId
                            );*/

                            $selectedAddress = $this->prepareAddress($deliveryInfo);
                            update_post_meta($order->get_id(), 'customer_selected_address', $selectedAddress);

                            // actual shipping fee
                            update_post_meta($order->get_id(), 'actual_shipping_fee', $totalShipping);

                            $useCutleryTool = (isset($deliveryInfo['cutleryTool']) && $deliveryInfo['cutleryTool']) ? 1 : 0;
                            update_post_meta($order->get_id(), 'use_cutlery_tool', $useCutleryTool);

                            // Todo hỏi lại
                            // customer info
                            $order->update_meta_data('customer_info', isset($request['customerInfo']) ? (object) $request['customerInfo'] : '');

                            $utm = new \stdClass();
                            $utm->utmSource = isset($request['utm_source']) ? $request['utm_source'] : '';
                            $utm->utmMedium = isset($request['utm_medium']) ? $request['utm_medium'] : '';
                            $utm->utmCampaign = isset($request['utm_campaign']) ? $request['utm_campaign'] : '';
                            $utm->utmContent = isset($request['utm_content']) ? $request['utm_content'] : '';
                            $utm->utmLocation = isset($request['utm_location']) ? $request['utm_location'] : '';
                            $utm->utmTerm = isset($request['utm_term']) ? $request['utm_term'] : '';

                            $order->update_meta_data('utm_data', $utm);

                            $masOffer = new \stdClass();
                            $masOffer->trafficId = isset($request['mo_traffic_id']) ? $request['mo_traffic_id'] : '';
                            if (isset($request['mo_traffic_id']) && $request['mo_traffic_id'] != '') {
                                $masOffer->isSuccess = 1; // 1 pending, 2 success, 3 failed
                            }
                            if (isset($request['mo_utm_source']) && $request['mo_utm_source'] == 'masoffer') {
                                $masOffer->utmSource = $request['mo_utm_source'];
                            }
                            $order->update_meta_data('mo_utm_data', $masOffer);

                            // Save the order again for sure
                            $order->save();

                            // todo for nghi.bui: gọi api sang Masoffer với trạng thái pending
                            if (isset($request['mo_traffic_id']) && $request['mo_traffic_id'] != '') {
                                $requestMasOffer = $this->masService->transaction($order, 0);
                                if ($requestMasOffer->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $masOffer->isSuccess = 2;
                                } else {
                                    $masOffer->isSuccess = 3;
                                }
                                $order->update_meta_data('mo_utm_data', $masOffer);
                                $order->save();
                            }

                            // save processing clm vouchers
                            update_option('processing_clm_vouchers', $processingClmVouchers);

                            // Save the order again for sure
                            $order->save();
                            if ($paymentMethod == 'COD') {
                                $histories[] = [
                                    'status' => 'pending',
                                    'statusText' => 'Chờ xác nhận',
                                    'createdAt' => date_i18n('Y-m-d H:i:s')
                                ];
                                $order->update_meta_data('order_status_histories', $histories);

                                // Todo minus product inventory
                                $products = [];
                                foreach ($items as $item) {
                                    if (isset($item['variationId']) && $item['variationId']) {
                                        $tempProductId = $item['variationId'];
                                    } else {
                                        $tempProductId = $item['productId'];
                                    }
                                    $products[] = [
                                        'id' => $tempProductId,
                                        'quantity' => $item['quantity'],
                                    ];
                                }
                                $inventoryService = new \GDelivery\Libs\InventoryService();
                                $doDecreaseStock = $inventoryService->decreaseStock($products);
                                if ($doDecreaseStock->messageCode == Message::SUCCESS) {
                                    $order->update_meta_data('decrease_stock', 1);
                                }
                                $order->save();
                            }

                            $res->result = $order->get_id();
                            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                            $res->message = 'success';

                        }
                    } else {
                        $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                        $res->message = 'Không tìm thấy nhà hàng!';
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                    $res->message = 'User not exits!';
                }
            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = $validate->message;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi tạo đơn trên Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        $this->logger->info("Response BE Create Order  Response: ".\json_encode($res));

        return $res;
    }

    public function updateOrder(WP_REST_Request $request)
    {
        $orderId = isset($request['id']) ? $request['id'] : null;
        $res = new Result();
        $paymentMethod = $request['paymentMethod'] ?? null;
        try {
            if (empty($paymentMethod)) {
                return $res;
            }
            $validate = $this->validateOrderUpdate($request->get_params());
            if ($validate->status) {
                if ($orderId) {
                    $order = wc_get_order($orderId);
                    if ($order) {
                        // Check restaurant closed.
                        $selectedRestaurant = $order->get_meta('merchant_in_cms');
                        \GDelivery\Libs\Helper\Restaurant::checkStatusRestaurant($selectedRestaurant, $res);

                        $customer = get_user_by('id', $order->get_customer_id());

                        // Todo Validate Request Data
                        $deliveryInfo = $request['deliveryInfo'];
                        $recipientName = $deliveryInfo['recipientName'];
                        $recipientCellphone = $deliveryInfo['recipientCellphone'];
                        $addressLine1 = $deliveryInfo['deliveryAddress'];
                        $pickupAtRestaurant = $deliveryInfo['pickupAtRestaurant'] ?? 0;
                        $note = $request['note'] ?? '';

                        $categoryId = $order->get_meta('current_product_category_id');

                        $merchantId = isset($request['merchantId']) ? $request['merchantId'] : $order->get_meta('merchant_id');
                        $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchant($merchantId);
                        if ($getMerchant->messageCode == Message::SUCCESS) {

                            $merchant = $getMerchant->result;
                            $validTime = \GDelivery\Libs\Helper\Order::checkTimeOrder(
                                $merchant,
                                $deliveryInfo['deliveryDate'],
                                $deliveryInfo['deliveryTime']
                            );
                            if (!$validTime['isValid']) {
                                $res->messageCode = \Abstraction\Object\ApiMessage::MOVED_PERMANENTLY;
                                $res->message = 'Thời gian hẹn giao hàng không còn hiệu lực. Vui lòng kiểm tra lại';
                                $res->result = [
                                    'validDate' => $validTime['validDate'],
                                    'validTimes' => $validTime['validTimes'],
                                ];

                                \GDelivery\Libs\Helper\Response::returnJson($res);
                                die;
                            }

                            // shipping address
                            if ($pickupAtRestaurant == 1) {
                                $shippingAddress = [
                                    'first_name' => $recipientName,
                                    'phone' => $recipientCellphone,
                                    'email' => $customer->user_email,
                                    'address_1' => 'Nhận tại cửa hàng',
                                ];
                            } else {
                                $shippingAddress = [
                                    'first_name' => $recipientName,
                                    'email' => $customer->user_email,
                                    'phone' => $recipientCellphone,
                                    'address_1' => $addressLine1,
                                ];
                            }
                            $order->update_meta_data('is_pickup_at_restaurant', $pickupAtRestaurant);

                            $billingAddress = [
                                'first_name' => $recipientName,
                                'last_name' => '',
                                'company' => '',
                                'email' => wp_get_current_user()->user_email,
                                'phone' => $recipientCellphone,
                                'address_1' => $addressLine1,
                                'city' => '',
                                'state' => '',
                                'postcode' => '',
                                'country' => ''
                            ];

                            $order->set_address($billingAddress, 'billing');
                            $order->set_address($shippingAddress, 'shipping');
                            $order->set_customer_note($note);  // Add the note

                            // update delivery time
                            $deliveryTime = $deliveryInfo['deliveryTime'];
                            $order->update_meta_data('delivery_time', $deliveryTime);
                            $deliveryDate = $deliveryInfo['deliveryDate'];
                            $order->update_meta_data('delivery_date', $deliveryDate);
                            
                            if (isset($deliveryInfo['isDeliveryNow'])) {
                                $isDeliveryNow = $deliveryInfo['isDeliveryNow'] ? 1 : 0;
                                $order->update_meta_data('is_delivery_now', $isDeliveryNow);
                            }

                            // restaurant
                            $statusProcess = true;
                            $messageProcess = '';
                            $jsonRestaurant = $order->get_meta('restaurant_object');
                            if ($merchant->restaurantCode && $merchant->restaurantCode != $jsonRestaurant->code) {
                                $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($merchantId);
                                if ($getMerchant->messageCode == Message::SUCCESS) {

                                    $merchant = $getMerchant->result;
                                    update_post_meta($order->get_id(), 'merchant_in_cms', $merchant);
                                    update_post_meta($order->get_id(), 'merchant_id', $merchant->id);
                                    update_post_meta($order->get_id(), 'restaurant_code', $merchant->restaurantCode);
                                    update_post_meta($order->get_id(), 'restaurant_object', $merchant->restaurant);
                                    update_post_meta($order->get_id(), 'restaurant_in_tgs', $merchant);

                                    $restaurantHistories = [];
                                    $restaurantHistories[] = [
                                        'time' => time(),
                                        'restaurant' => $merchant
                                    ];
                                    update_post_meta(
                                        $order->get_id(),
                                        'restaurant_histories',
                                        $restaurantHistories
                                    );

                                    $provinceBrand = $merchant->province->id . '_' . $merchant->brand->id;
                                    update_post_meta($order->get_id(), 'province_brand',
                                        $provinceBrand); // use for operator filter
                                } else {
                                    $statusProcess = false;
                                    $messageProcess = $getMerchant->message;
                                }
                            }

                            // payment
                            $order->update_meta_data('payment_method', $paymentMethod);

                            if ($paymentMethod == 'COD') {

                                // Todo minus product inventory
                                if (!$order->get_meta('decrease_stock')) {
                                    $products = [];
                                    foreach ($order->get_items() as $oneItem) {
                                        $currentProductId = (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) ? $oneItem->get_data()['variation_id'] : $oneItem->get_data()['product_id'];
                                        $products[] = [
                                            'id' => $currentProductId,
                                            'quantity' => $oneItem->get_quantity(),
                                        ];
                                    }
                                    $inventoryService = new \GDelivery\Libs\InventoryService();
                                    $doDecreaseStock = $inventoryService->decreaseStock($products);
                                    if ($doDecreaseStock->messageCode == Message::SUCCESS) {
                                        $order->update_meta_data('decrease_stock', 1);
                                    }
                                }

                                $arrStatus = [];
                                $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                                if ($histories) {
                                    $arrStatus = array_column($histories, 'pending');
                                }

                                if (!in_array('pending', $arrStatus)) {
                                    $histories[] = [
                                        'status' => 'pending',
                                        'statusText' => 'Chờ xác nhận',
                                        'createdAt' => date_i18n('Y-m-d H:i:s')
                                    ];
                                    $order->update_meta_data('order_status_histories', $histories);
                                }

                            }

                            // invoice
                            if (isset($request['invoice']) && !empty($request['invoice'])) {
                                $invoice = isset($request['invoice']) ? $request['invoice'] : ['info' => 0];
                                $order->update_meta_data('customer_invoice', $invoice);
                            }

                            // Calculate shipping
                            /*if (get_option('google_map_service_address') == 'goong_address') {
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
                            );*/
                            // set shipping fee
                            $totals = $request['totals'];
                            $shipping = $totals['shipping'];
                            $totalShipping = isset($shipping['total']) ? (float)$shipping['total'] : 0;
                            if ($totalShipping > 0) {
                                $order->set_shipping_tax(isset($shipping['tax']) ? (float)$shipping['tax'] : 0);
                                $order->update_meta_data('shipping_price', isset($shipping['price']) ? (float)$shipping['price'] : 0);
                            } else {
                                $order->set_shipping_tax(0);
                                $order->update_meta_data('shipping_price', 0);
                            }
                            $order->set_shipping_total($totalShipping);
                            $order->update_meta_data('shipping_distance', $shipping['distance'] ?? 0);

                            // add total tax
                            $totalTax = $totals['totalVat'];
                            $order->set_cart_tax($totalTax);
                            $order->update_meta_data('total_tax', $totalTax);

                            // total price
                            $order->update_meta_data('total_price', $totals['totalPrice']);

                            // $order->set_discount_total($totals->totalDiscount);
                            $order->set_total($totals['totalOrder']);
                            $order->update_meta_data('actual_shipping_fee', $totalShipping);

                            // totalAfterTax
                            $totalAfterTax = isset($totals['totalAfterTax']) ? (float)$totals['totalAfterTax'] : 0;
                            $order->update_meta_data('total_after_tax', $totalAfterTax);

                            // totalBeforeTax
                            $totalBeforeTax = isset($totals['totalBeforeTax']) ? (float)$totals['totalBeforeTax'] : 0;
                            $order->update_meta_data('total_before_tax', $totalBeforeTax);

                            $order->update_meta_data('total_pay_sum', $totals['totalOrder']);

                            $oldAddress = $order->get_meta('customer_selected_address');
                            $selectedAddress = $this->prepareAddress($deliveryInfo, $oldAddress);
                            update_post_meta($order->get_id(), 'customer_selected_address', $selectedAddress);

                            $histories = [];
                            if ($paymentMethod == 'COD') {
                                $status = "pending";
                                $order->update_status($status, '', true);
                                GDelivery\Libs\Helper\Call::makeAcall($order); // calling order
                                GDelivery\Libs\Helper\Mail::send($order); // send mail order
                            } else {
                                // other ways, wait for payment, other process will update order
                                $histories[] = [
                                    'status' => 'waiting-payment',
                                    'statusText' => 'Chờ thanh toán',
                                    'createdAt' => date_i18n('Y-m-d H:i:s')
                                ];
                                $order->update_meta_data('order_status_histories', $histories);

                                $status = "waiting-payment";
                                $order->update_status($status, "Chờ thanh toán {$paymentMethod}", true);
                                $order->update_meta_data('is_paid', 0);
                            }

                            if ($statusProcess) {
                                // Save the order again for sure
                                $order->save();

                                $temp = new \stdClass();
                                $temp->id = $order->get_id();

                                $res->result = $temp;
                                $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                                $res->message = 'success';
                            } else {
                                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                                $res->message = $messageProcess;
                            }
                        } else {
                            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                            $res->message = $getMerchant->message;
                        }

                    } else {
                        $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                        $res->message = 'Order not exits!';
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                    $res->message = 'Order id is required.';
                }

            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = $validate->message;
            }
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function updateOrderStatus(WP_REST_Request $request)
    {
        $res = new Result();
        try {
            $orderId = isset($request['id']) ? $request['id'] : null;
            $status = isset($request['status']) ? $request['status'] : '' ;
            $requestNote = isset($request['note']) ? $request['note'] : '';
            $restaurantCode = isset($request['restaurant']) ? $request['restaurant'] : 0;

            $isCustomerNote = (isset($request['role']) && $request['role'] == 'customer') ? 1 : 0;
            $addByUser = isset($request['role']) && $request['role'] != 'customer';
            $role = isset($request['role']) ? $request['role'] : null;

            if ($orderId && $status) {
                $order = wc_get_order($orderId);

                if ($order) {

                    if (isset($request['selectedVouchers']) && $request['selectedVouchers']) {
                        $selectedVouchers = [];
                        foreach ($request['selectedVouchers'] as $voucher) {
                            $selectedVouchers[] = (object) $voucher;
                        }

                        $order->update_meta_data(
                            'selected_vouchers',
                            $selectedVouchers
                        );
                        $order->save();
                    }

                    if (isset($request['processingClmVouchers']) && $request['processingClmVouchers']) {
                        $processingClmVouchers = get_option('processing_clm_vouchers', []);
                        $clmVouchers = array_merge($processingClmVouchers, $request['processingClmVouchers']);
                        update_option('processing_clm_vouchers', $clmVouchers);
                    }

                    if (isset($request['gggInternalAffiliate']) && $request['gggInternalAffiliate']) {
                        $gggInternalAffiliate = new \stdClass();
                        $gggInternalAffiliate->referralCode = $request['gggInternalAffiliate']['referralCode'];
                        $gggInternalAffiliate->orderId = $request['gggInternalAffiliate']['orderId'];
                        $order->update_meta_data('ggg_internal_affiliate', $gggInternalAffiliate);
                        $order->save();
                    }

                    $customer = get_user_by('id',  $order->get_customer_id());
                    $oldStatus = $order->get_status();
                    $note = "{$customer->ID}||{$oldStatus}||{$status}||{$requestNote}__";

                    switch ($status) {
                        case 'completed':
                            // in case change status to complete
                            $requestRkOrder = isset($_REQUEST['rkOrder']) ? $_REQUEST['rkOrder'] : [];
                            if ($requestRkOrder) {
                                $oldRkOrder = $order->get_meta('rkOrder');
                                $rkOrder = new \stdClass();

                                $rkOrder->guid = $oldRkOrder->guid;
                                $rkOrder->billNumber = $requestRkOrder['billNumber'];
                                $rkOrder->checkNumber = $requestRkOrder['checkNumber'];

                                // Todo send transaction to MasOffer

                                // status
                                $order->update_meta_data('rkOrder', $rkOrder);
                                $order->update_status($status, $note); // set new status
                                $order->save();

                                // Todo send report?
                                /*$report = new \GDelivery\Libs\Helper\Report();
                                $report->updateOrder($order);*/

                                $res->messageCode = Message::SUCCESS;
                                $res->message = 'Đã cập nhật thông tin đơn hàng';
                                $res->result = $order->get_data();
                            } else {
                                $res->messageCode = Message::GENERAL_ERROR;
                                $res->message = 'Cần nhập đẩy đủ thông tin order trên POS';
                            }
                            break;

                        case 'need-to-cancel' :
                            // in case need to cancel
                            $order->update_status($status, $note);
                            $order->update_meta_data('care_status', $oldStatus); // save old status before request cancel
                            $order->save();

                            $res->messageCode = Message::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order->get_data();
                            break;

                        case 'cancelled' :
                            // process vouchers
                            $selectedVouchers = $order->get_meta('selected_vouchers');
                            if ($selectedVouchers) {
                                $paymentService = new \GDelivery\Libs\PaymentHubService();
                                foreach ($selectedVouchers as $one) {
                                    $paymentService->cancelVoucher($one->code, $order);
                                }
                            }

                            // status
                            $order->update_status($status, $note); // set new status
                            $order->save();

                            /*$report = new \GDelivery\Libs\Helper\Report();
                            $report->updateOrder($order);*/

                            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order->get_data();

                            break;

                        case 'waiting-payment':
                            $note = isset($request['note']) ? $request['note'] : null;
                            $paymentRequestId = isset($request['paymentRequestId']) ? $request['paymentRequestId'] : null;
                            $paymentRequestObject = isset($request['paymentRequestObject']) ? $request['paymentRequestObject'] : null;
                            $order->update_status("waiting-payment", $note, true);
                            $order->update_meta_data('is_paid', 0);
                            $order->update_meta_data('payment_request_id', $paymentRequestId);
                            $order->update_meta_data('payment_request_object', $paymentRequestObject);

                            $status = "waiting-payment";
                            $histories[] = [
                                'status' => 'waiting-payment',
                                'statusText' => 'Chờ thanh toán',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);

                            $order->save();

                            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order->get_data();

                            break;

                        case 'pending':
                            $order->update_status("pending", '', true);
                            $order->save();

                            $paymentMethod = $order->get_meta('payment_method');
                            if ($paymentMethod == 'COD') {
                                // Todo Remove comment send mail
                                GDelivery\Libs\Helper\Mail::send($order);
                            } elseif (
                                $paymentMethod != 'COD'
                                && (double) $order->get_meta('total_pay_sum') <= 0
                            ) {

                                $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                                $histories[] = [
                                    'status' => 'pending',
                                    'statusText' => 'Chờ xác nhận',
                                    'createdAt' => date_i18n('Y-m-d H:i:s')
                                ];
                                $order->update_meta_data('order_status_histories', $histories);

                                $order->update_meta_data('is_paid', 1);
                                $order->save();
                                GDelivery\Libs\Helper\Mail::send($order);
                            }

                            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order->get_data();

                            break;

                        default:
                            ///////////////////////////////////////////////////////////
                            /// for case only update order status
                            /// //////////////////////////////////////////////////////

                            // general note
                            $order->add_order_note($note, $isCustomerNote, $addByUser);

                            // other note
                            if ($role == 'operator') {
                                $order->update_meta_data('operator_note', $requestNote);
                            } elseif ($role == 'restaurant') {
                                $order->update_meta_data('restaurant_note', $requestNote);
                            } else {

                            }

                            // status
                            $order->update_status($status, $note); // set new status

                            ///////////////////////////////////////////////////////////
                            // process transfer order
                            /////////////////////////////////////////////////////////
                            if ($restaurantCode && $restaurantCode != $order->get_meta('restaurant_code')) {
                                $getRestaurant = $this->bookingService->getRestaurant($restaurantCode);
                                if ($getRestaurant->messageCode == Message::SUCCESS) {
                                    // check restaurant in G-Delivery
                                    $getTGSRestaurant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($restaurantCode);
                                    if ($getTGSRestaurant->messageCode == Message::SUCCESS) {
                                        $tgsRestaurant = $getTGSRestaurant->result;
                                        $tgsRestaurant->restaurant = $getRestaurant->result;

                                        $restaurantHistories = $order->get_meta('restaurant_histories');
                                        if (!$restaurantHistories) {
                                            $restaurantHistories = [];
                                        }
                                        $restaurantHistories[] = [
                                            'time' => time(),
                                            'restaurant' => $tgsRestaurant
                                        ];

                                        // save histories
                                        $order->update_meta_data('restaurant_histories', $restaurantHistories);

                                        // update new restaurant
                                        $order->update_meta_data('restaurant_code', $restaurantCode);
                                        $order->update_meta_data('restaurant_object', $getRestaurant->result);
                                        $order->update_meta_data('restaurant_in_tgs', $tgsRestaurant);

                                        // recalculate shipping fee
                                        $calculateShippingFee = \GDelivery\Libs\Helper\Helper::calculateShippingFee($order->get_meta('customer_selected_address'), $getRestaurant->result);
                                        if ($calculateShippingFee->messageCode == Message::SUCCESS) {
                                            $order->update_meta_data('actual_shipping_fee', $calculateShippingFee->result->total);
                                        }

                                        // reset is_created_rk_order
                                        $order->update_meta_data('is_created_rk_order', 0);

                                        // process selected voucher
                                        $selectedVouchers = $order->get_meta('selected_vouchers');
                                        // cancel utilize voucher
                                        foreach ($selectedVouchers as $one) {
                                            $this->paymentService->cancelVoucher($one->code, $order);
                                        }
                                        // re-utilize
                                        foreach ($selectedVouchers as $oneVoucher) {
                                            $this->paymentService->utilizeVoucher(
                                                $oneVoucher->code,
                                                $order->get_meta('restaurant_code'),
                                                $order
                                            );
                                        }
                                    }
                                }
                            }

                            if ($order->save()) {
                                $report = new \GDelivery\Libs\Helper\Report();
                                $report->updateOrder($order);

                                $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                                $res->message = 'Đã cập nhật thông tin đơn hàng';
                                $res->result = $order->get_data();
                            } else {
                                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                                $res->message = 'Lỗi khi cập nhật thông tin đơn hàng';
                            }

                            break;
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                    $res->message = 'Order không tồn tại';
                }
            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = 'Order Id và Status là bắt buộc';
            }


        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function deleteOrder(WP_REST_Request $request)
    {
        $res = new Result();
        try {
            $orderId = isset($request['id']) ? $request['id'] : null;
            $order = wc_get_order($orderId);
            if ($order) {
                $order->delete(true);
                $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                $res->message = 'success';
            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = 'Order không tồn tại';
            }
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function listOrderByCustomer(WP_REST_Request $request)
    {
        $res = new Result();
        try {
            $customerNumber = $request['cellphone'];
            $customer = get_user_by( 'login', $customerNumber );
            if ($customer) {

                $args = [
                    'customer_id' => $customer->id,
                    'limit' => isset($request['perPage']) ? (int) $request['perPage'] : self::LIMIT,
                    'page' => isset($request['page']) ? (int) $request['page'] : 1
                ];

                if (isset($request['fromDate'], $request['toDate']) && $request['fromDate'] && $request['toDate']) {
                    $fromDate = $request['fromDate'];
                    $toDate = $request['toDate'];
                    $args['date_created'] = "{$fromDate}...{$toDate}";
                } else if (isset($request['date_created']) && $request['date_created'] != '') {
                    $args['date_created'] = $request['date_created'];
                }

                if (isset($request['status'])) {
                    $args['status'] = explode(',', $request['status']);
                }
                $customerOrders = wc_get_orders($args);

                $orders = [];
                foreach ($customerOrders as $oneOrder) {
                    $totals = \GDelivery\Libs\Helper\Helper::orderTotals($oneOrder);
                    //$jsonRestaurant = $oneOrder->get_meta('restaurant_object');
                    $merchantId = $oneOrder->get_meta('merchant_id');
                    $getMerchant = $merchantId ? \GDelivery\Libs\Helper\Helper::getMerchant($merchantId, ['post_status' => true]) : null;

                    $items = [];
                    foreach ($oneOrder->get_items() as $item) {
                        $items[] = [
                            'name' => $item['name'],
                            'productId' =>  $item['product_id'],
                            'variationId' => $item['variation_id'],
                            'quantity' => $item['quantity'],
                            'taxClass' => $item['tax_class'],
                            'subtotal' => (double) $item['subtotal'],
                            'subtotalTax' => (double) $item['subtotal_tax'],
                            'total' => (double) $item['total'],
                            'totalTax' => (double) $item['total_tax'],
                            'salePrice' => (double) $item->get_meta('salePrice'),
                            'regularPrice' => (double) $item->get_meta('regularPrice'),
                            'priceAfterTax' => (double) $item->get_meta('priceAfterTax'),
                            'sapPriceAfterTax' => (double) $item->get_meta('sapPriceAfterTax'),
                            'thumbnail' => get_the_post_thumbnail_url($item['product_id']) ? get_the_post_thumbnail_url($item['product_id']) : get_bloginfo('template_url').'/assets/images/no-product-image.png',
                            'lineItemId' => $item->get_meta('lineItemId'),
                            'parentLineItemId' => $item->get_meta('parentLineItemId'),
                            'modifier' => $item->get_meta('modifier')
                        ];
                    }

                    $paymentMethodCode = $oneOrder->get_meta('payment_method');
                    $paymentMethodLogo = '';
                    $listPaymentMethod = \GDelivery\Libs\Helper\PaymentMethod::getListPaymentMethod([
                        'code' => $paymentMethodCode
                    ]);
                    if ($listPaymentMethod) {
                        $paymentMethodLogo = get_field('payment_method_logo', $listPaymentMethod[0]->ID);
                    }

                    $shippingVendor = new \stdClass();
                    if ($oneOrder->get_meta('vendor_transport') == 'grab_express') {
                        $deliveryObj = $oneOrder->get_meta('grab_delivery_object');
                        if ($deliveryObj) {
                            if (
                                $deliveryObj->getSchedule()
                                && isset($deliveryObj->getSchedule()->pickupTimeFrom)
                                && isset($deliveryObj->getSchedule()->pickupTimeTo)
                            ) {
                                $timeFrom = new DateTime($deliveryObj->getSchedule()->pickupTimeFrom);
                                $timeFrom->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));

                                $timeTo = new DateTime($deliveryObj->getSchedule()->pickupTimeTo);
                                $timeTo->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                            } else {
                                $timeFrom = null;
                                $timeTo = null;
                            }
                            $shippingVendor->timeFrom = $timeFrom ? $timeFrom->format('d/m/Y H:i') : '';
                            $shippingVendor->timeTo = $timeTo ? $timeTo->format('d/m/Y H:i') : '';
                            $shippingVendor->id = $deliveryObj->getId();
                            $shippingVendor->driverName = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getName() : '';
                            $shippingVendor->license = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getLicense() : '';
                            $shippingVendor->phone = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getPhone() : '';
                        }
                    }

                    $dateCreate = new DateTime($oneOrder->get_date_created());
                    $dateCreate->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                    $orders[] = [
                        'id' => $oneOrder->get_id(),
                        'orderGuid' => isset($oneOrder->get_meta('rkOrder')->guid) ? $oneOrder->get_meta('rkOrder')->guid : '',
                        'merchant' => $getMerchant->result,
                        'items' => $items,
                        'paymentMethod' => [
                            'code' => $paymentMethodCode,
                            'requestId' => (int) $oneOrder->get_meta('payment_request_id'),
                            'methodName' => \GDelivery\Libs\Helper\Helper::textPaymentMethod($oneOrder->get_meta('payment_method')),
                            'logo' => $paymentMethodLogo,
                            'status' => (int) $oneOrder->get_meta('is_paid'),
                            'statusName' => $paymentMethodCode == 'COD' ? '' : (($oneOrder->get_meta('is_paid') == 1) ? 'Đã thanh toán' : 'Chờ thanh toán')
                        ],
                        'delivery' => [
                            'recipientCellphone' => $oneOrder->get_shipping_phone(),
                            'recipientName' => $oneOrder->get_shipping_first_name(),
                            'address' => $oneOrder->get_address( 'shipping' )['address_1'],
                            'address2' => $oneOrder->get_address( 'shipping' )['address_2'],
                            'date' => $oneOrder->get_meta('delivery_date'),
                            'time' => $oneOrder->get_meta('delivery_time'),
                            'note' => $oneOrder->get_customer_note(),
                            'pickupAtRestaurant' => (bool) $oneOrder->get_meta('is_pickup_at_restaurant'),
                            'isDeliveryNow' => (bool) $oneOrder->get_meta('is_delivery_now'),
                            'vendorTransport' => $shippingVendor
                        ],
                        'customerInvoice' => $oneOrder->get_meta('customer_invoice'),
                        'shippingFee' => $totals->shipping,
                        'totalPrice' => $totals->totalPrice,
                        'totalDiscount' => $totals->totalDiscount,
                        'totalTax' => $totals->totalTax,
                        'totalCashVoucher' => $totals->totalCashVoucher,
                        'shipping' => $oneOrder->shipping->total ?? 0,
                        'subTotal' => (double) $oneOrder->get_meta('total_price_without_shipping'),
                        'total' => $oneOrder->get_total(),
                        'totalPaySum' => $totals->totalPaySum,
                        'totalAfterTax' => (double) $oneOrder->get_meta('total_after_tax'),
                        'status' => $oneOrder->get_status(),
                        'note' => $oneOrder->get_customer_note(),
                        'createdAt' => $dateCreate->format('d/m/Y H:i')
                    ];
                }

                $res->result = $orders;
                $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                $res->message = 'Success';

            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = 'User not exits!';
            }
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function getOrderByCustomer(WP_REST_Request $request)
    {
        $res = new Result();
        try {

            if (isset($request['cellphone']) && $request['cellphone']) {

                $customerNumber = $request['cellphone'];
                $customer = get_user_by( 'login', $customerNumber );
                if ($customer) {

                    $getOrder = wc_get_order($request['id']);
                    if ($getOrder && $getOrder->get_customer_id() == $customer->id) {
                        // selected vouchers
                        $selectedVouchers = $getOrder->get_meta('selected_vouchers');
                        $campaigns = [];
                        $vouchers = [];
                        if ($selectedVouchers) {
                            foreach ($selectedVouchers as $selectedVoucher) {
                                if ($selectedVoucher->type == 1) {
                                    $campaigns[]= [
                                        'title' => $selectedVoucher->name,
                                        'name' => $selectedVoucher->code,
                                        'discount' => $selectedVoucher->denominationValue,
                                        'type' => $selectedVoucher->type
                                    ];
                                } else {
                                    if (isset($selectedVoucher->selectedForProductId)) {
                                        $vouchers[] = [
                                            'title' => $selectedVoucher->name,
                                            'name' => $selectedVoucher->code,
                                            'discount' => $selectedVoucher->denominationValue,
                                            'type' => $selectedVoucher->type,
                                            'selectedForProductId' => $selectedVoucher->selectedForProductId,
                                            'selectedForRkItem' => (isset($selectedVoucher->selectedForRkItem) && $selectedVoucher->selectedForRkItem) ? $selectedVoucher->selectedForRkItem : null,
                                            'listProductDiscountChosen' => (isset($selectedVoucher->listProductDiscountChosen) && $selectedVoucher->listProductDiscountChosen) ? $selectedVoucher->listProductDiscountChosen : null
                                        ];
                                    } else {
                                        $vouchers[] = [
                                            'title' => $selectedVoucher->name,
                                            'name' => $selectedVoucher->code,
                                            'discount' => $selectedVoucher->denominationValue,
                                            'type' => $selectedVoucher->type,
                                            'selectedForRkItem' => (isset($selectedVoucher->selectedForRkItem) && $selectedVoucher->selectedForRkItem) ? $selectedVoucher->selectedForRkItem : null,
                                            'listProductDiscountChosen' => (isset($selectedVoucher->listProductDiscountChosen) && $selectedVoucher->listProductDiscountChosen) ? $selectedVoucher->listProductDiscountChosen : null
                                        ];
                                    }
                                }
                            }
                        }

                        $jsonRestaurant = $getOrder->get_meta('restaurant_object');
                        $totals = \GDelivery\Libs\Helper\Helper::orderTotals($getOrder);

                        $items = [];
                        $orderListItem = $getOrder->get_items();

                        foreach ($orderListItem as $item) {
                            $productId = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
                            $orderItem = [
                                'orderId' => $item->get_order_id(),
                                'name' => $item->get_name(),
                                'productId' => $productId,
                                'variationId' => $item->get_variation_id(),
                                'quantity' => (int) $item->get_quantity(),
                                'taxClass' => $item->get_tax_class(),
                                'subTotal' => (double) $item->get_subtotal(),
                                'subtotalTax' => (double) $item->get_subtotal_tax(),
                                'total' =>  (double) $item->get_total(),
                                'totalTax' => (double) $item->get_total_tax(),
                                'salePrice' => (double) $item->get_meta('salePrice'),
                                'regularPrice' => (double) $item->get_meta('regularPrice'),
                                'priceAfterTax' => (double) $item->get_meta('priceAfterTax'),
                                'sapPriceAfterTax' => (double) $item->get_meta('sapPriceAfterTax'),
                                'thumbnail' => get_the_post_thumbnail_url($item->get_product_id()) ? get_the_post_thumbnail_url($item->get_product_id()) : get_bloginfo('template_url').'/assets/images/no-product-image.png',
                                'rkCode' => get_field('product_rk_code', $productId),
                                'lineItemId' => $item->get_meta('lineItemId'),
                                'parentLineItemId' => $item->get_meta('parentLineItemId'),
                                'modifier' => $item->get_meta('modifier'),
                                'comboData' => $item->get_meta('comboData'),
                                'taxRateValue' => \GDelivery\Libs\Helper\Product::getTaxRateValue($productId)
                            ];
                            if (!empty($orderItem['comboData'])) {
                                $orderItem['salePrice'] = $orderItem['regularPrice'] = $this->calculateComboPrice($item, $orderListItem);
                            }
                            $items[] = $orderItem;
                        }

                        $merchantId = $getOrder->get_meta('merchant_id');
                        $merchant = \GDelivery\Libs\Helper\Helper::getMerchant($merchantId, ['post_status' => true]);

                        $shippingVendor = new \stdClass();
                        if ($getOrder->get_meta('vendor_transport') == 'grab_express') {
                            $deliveryObj = $getOrder->get_meta('grab_delivery_object');
                            if ($deliveryObj) {
                                if ($deliveryObj->getSchedule()) {
                                    $timeFrom = new DateTime($deliveryObj->getSchedule()->pickupTimeFrom);
                                    $timeFrom->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));

                                    $timeTo = new DateTime($deliveryObj->getSchedule()->pickupTimeTo);
                                    $timeTo->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                                } else {
                                    $timeFrom = null;
                                    $timeTo = null;
                                }
                                $shippingVendor->vendorName = "Grab express";
                                $shippingVendor->timeFrom = $timeFrom ? $timeFrom->format('d/m/Y H:i') : '';
                                $shippingVendor->timeTo = $timeTo ? $timeTo->format('d/m/Y H:i') : '';
                                $shippingVendor->id = $deliveryObj->getId();
                                $shippingVendor->driverName = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getName() : '';
                                $shippingVendor->license = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getLicense() : '';
                                $shippingVendor->phone = $deliveryObj->getDriver() ? $deliveryObj->getDriver()->getPhone() : '';
                            }
                        } elseif ($getOrder->get_meta('vendor_transport') == 'golden_gate') {
                            $shippingVendor->vendorName = "Golden Gate";
                        }
                        $paymentRequestObject = $getOrder->get_meta('payment_request_object');
                        $paymentMethodCode = $getOrder->get_meta('payment_method');
                        $selectedAddress = $getOrder->get_meta('customer_selected_address');
                        //$term = get_term($getOrder->get_meta('current_product_category_id'));
                        //$term->brandId = get_field('product_category_brand_id', 'product_cat_'.$term->term_id);
                        $paymentMethodLogo = $paymentMethodCode == 'COD' ? '' : ($paymentRequestObject->partner->logo ?? '');
                        $listPaymentMethod = \GDelivery\Libs\Helper\PaymentMethod::getListPaymentMethod([
                            'code' => $paymentMethodCode
                        ]);
                        if ($listPaymentMethod) {
                            $paymentMethodLogo = get_field('payment_method_logo', $listPaymentMethod[0]->ID);
                        }

                        $createdAt = new DateTime($getOrder->get_date_created());
                        $createdAt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));

                        $updatedAt = new DateTime($getOrder->get_date_modified());
                        $updatedAt->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));

                        $statusHistories = $getOrder->get_meta('order_status_histories');
                        $orderStatus = $getOrder->get_status();

                        $order = [
                            'id' => $getOrder->get_id(),
                            'status' => $orderStatus,
                            'statusHistories' => $statusHistories,
                            'orderGuid' => $getOrder->get_meta('rkOrder') ? $getOrder->get_meta('rkOrder')->guid : '',
                            'totalPrice' => $totals->totalPrice,
                            'totalDiscount' => $totals->totalDiscount,
                            'campaignDetail' => $campaigns,
                            'totalTax' => $totals->totalTax,
                            'subTotal' => (double) $getOrder->get_meta('total_price_without_shipping'),
                            'totalCashVoucher' => $totals->totalCashVoucher,
                            'voucherDetail' => $vouchers,
                            'totalShipping' => $totals->shipping->total ?? 0,
                            'shippingFee' => $totals->shipping,
                            'total' => (double) $getOrder->get_meta('total_price_without_shipping'),
                            'totalPaySum' => $totals->totalPaySum,
                            'totalAfterTax' => (double) $getOrder->get_meta('total_after_tax'),
                            'billing' => [
                                'first_name' => $getOrder->get_billing_first_name(),
                                'last_name' => $getOrder->get_billing_last_name(),
                                'company' => $getOrder->get_billing_company(),
                                'address_1' => $getOrder->get_billing_address_1(),
                                'address_2' => $getOrder->get_billing_address_2(),
                                'city' => $getOrder->get_billing_city(),
                                'state' => $getOrder->get_billing_state(),
                                'postcode' => $getOrder->get_billing_postcode(),
                                'country' => $getOrder->get_billing_country(),
                                'email' => $getOrder->get_billing_email(),
                                'phone' => $getOrder->get_billing_phone(),
                            ],
                            'shipping' => [
                                'first_name' => $getOrder->get_shipping_first_name(),
                                'last_name' => $getOrder->get_shipping_last_name(),
                                'company' => $getOrder->get_shipping_company(),
                                'address_1' => $getOrder->get_shipping_address_1(),
                                'address_2' => $getOrder->get_shipping_address_2(),
                                'city' => $getOrder->get_shipping_city(),
                                'state' => $getOrder->get_shipping_state(),
                                'postcode' => $getOrder->get_shipping_postcode(),
                                'country' => $getOrder->get_shipping_country(),
                            ],
                            'paymentMethod' => [
                                'code' => $paymentMethodCode,
                                'requestId' => (int) $getOrder->get_meta('payment_request_id'),
                                'methodName' => \GDelivery\Libs\Helper\Helper::textPaymentMethod($getOrder->get_meta('payment_method')),
                                'logo' => $paymentMethodLogo,
                                'status' => (int) $getOrder->get_meta('is_paid'),
                                'statusName' => $paymentMethodCode == 'COD' ? '' : (($getOrder->get_meta('is_paid') == 1) ? 'Đã thanh toán' : 'Chờ thanh toán')
                            ],
                            'delivery' => [
                                'recipientCellphone' => $getOrder->get_shipping_phone(),
                                'recipientName' => $getOrder->get_shipping_first_name(),
                                'address' => $getOrder->get_address( 'shipping' )['address_1'],
                                'address2' => $getOrder->get_address( 'shipping' )['address_2'],
                                'date' => $getOrder->get_meta('delivery_date'),
                                'time' => $getOrder->get_meta('delivery_time'),
                                'note' => $getOrder->get_customer_note(),
                                'pickupAtRestaurant' => (bool) $getOrder->get_meta('is_pickup_at_restaurant'),
                                'isDeliveryNow' => (bool) $getOrder->get_meta('is_delivery_now'),
                                'vendorTransport' => $shippingVendor
                            ],
                            'createdAt' => $createdAt->format('d/m/Y H:i:s'), //gmdate('d/m/Y H:i', $getOrder->get_date_created('class')->getTimestamp()),
                            'updatedAt' => $updatedAt->format('d/m/Y H:i:s'),
                            'items' => $items,
                            'merchant' => $merchant->result,
                            'selectedAddress' => $selectedAddress,
                            'customerInvoice' => $getOrder->get_meta('customer_invoice'),
                            'note' => $getOrder->get_customer_note(),
                            'refundedTime' => $getOrder->get_meta('refunded_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('refunded_time'))) : null,
                            'cancelledTime' => $getOrder->get_meta('cancelled_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('cancelled_time'))) : null,
                            'transportOnGoingTime' => $getOrder->get_meta('transport_on_going_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('transport_on_going_time'))) : null,
                            'processingTime' => $getOrder->get_meta('processing_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('processing_time'))) : null,
                            'transRequestedTime' => $getOrder->get_meta('trans_requested_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('trans_requested_time'))) : null,
                            'transAcceptedTime' => $getOrder->get_meta('transport_accepted_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('transport_accepted_time'))) : null,
                            'completedTime' => $getOrder->get_meta('completed_time') ? date('d/m/Y H:i:s', strtotime($getOrder->get_meta('completed_time'))) : null
                        ];

                        $res->result = $order;
                        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                        $res->message = 'Success';

                    } else {
                        $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                        $res->message = 'This order is not yours!';
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                    $res->message = 'User not exits!';
                }
            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
                $res->message = 'Param cellphone is required.';
            }

        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function calculateComboPrice($orderItem, $orderListItem)
    {
        $comboData = $orderItem->get_meta('comboData');
        if ($comboData['priceTaxType'] == 1) {
            $price = (double) $orderItem->get_meta('salePrice') > 0 ? (double) $orderItem->get_meta('salePrice') : (double) $orderItem->get_meta('regularPrice'); 
            return $price;
        }
        $comboPrice = 0;
        $comboLineItem = $orderItem->get_meta('lineItemId');
        foreach($orderListItem as $tmpOrderItem) {
            if ($comboLineItem == $tmpOrderItem->get_meta('parentLineItemId')) {
                $price = (double) $tmpOrderItem->get_meta('salePrice') > 0 ? (double) $tmpOrderItem->get_meta('salePrice') : (double) $tmpOrderItem->get_meta('regularPrice');
                $comboPrice += $price * (int) $tmpOrderItem->get_quantity();
            }
        }
        return $comboPrice;
    }

    public function updatePaymentDataOrder(WP_REST_Request $request)
    {
        $res = new Result();
        $data = json_decode($request->get_body());
        try {
            if (isset($data->cellphone) && $data->cellphone) {

                $customerNumber = $data->cellphone;
                $customer = get_user_by( 'login', $customerNumber );
                if ($customer) {
                    $getOrder = wc_get_order($request['id']);
                    if (
                        $getOrder
                        && $getOrder->get_customer_id() == $customer->id
                    ) {
                        if ($getOrder->get_status() == 'waiting-payment') {
                            $metaData = $data->metadata;
                            $options = $data->options;
                            // Update meta data order
                            foreach ($metaData as $key => $value) {
                                $getOrder->update_meta_data($key, $value);
                            }

                            // Update payment time
                            if (isset($metaData->is_paid) && $metaData->is_paid == 1) {
                                if (isset($options->partner_payment_time)) {
                                    $partnerPaymentTime = $options->partner_payment_time;
                                    $getOrder->set_date_paid(strtotime($partnerPaymentTime));
                                }
                                $getOrder->set_status('pending');

                                // Todo minus product inventory
                                if (!$getOrder->get_meta('decrease_stock')) {
                                    $products = [];
                                    foreach ($getOrder->get_items() as $oneItem) {
                                        $currentProductId = (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) ? $oneItem->get_data()['variation_id'] : $oneItem->get_data()['product_id'];
                                        $products[] = [
                                            'id' => $currentProductId,
                                            'quantity' => $oneItem->get_quantity(),
                                        ];
                                    }
                                    $inventoryService = new \GDelivery\Libs\InventoryService();
                                    $doDecreaseStock = $inventoryService->decreaseStock($products);
                                    if ($doDecreaseStock->messageCode == Message::SUCCESS) {
                                        $getOrder->update_meta_data('decrease_stock', 1);
                                    }
                                }

                                $histories = $getOrder->get_meta('order_status_histories') ? $getOrder->get_meta('order_status_histories') : [];
                                $histories[] = [
                                    'status' => 'pending',
                                    'statusText' => 'Chờ xác nhận',
                                    'createdAt' => date_i18n('Y-m-d H:i:s')
                                ];
                                $getOrder->update_meta_data('order_status_histories', $histories);
                            }
                            $getOrder->save();
                            // todo send mail
                            GDelivery\Libs\Helper\Mail::send($getOrder);
                        }

                        $res->messageCode = ApiMessage::SUCCESS;
                        $res->message = 'Success';
                        $res->result = $getOrder->get_meta('payment_request_id');

                    } else {
                        $res->messageCode = ApiMessage::GENERAL_ERROR;
                        $res->message = 'This order is not yours!';
                    }
                } else {
                    $res->messageCode = ApiMessage::GENERAL_ERROR;
                    $res->message = 'User not exits!';
                }
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = 'Param cellphone is required.';
            }

        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function makeItemNote(WP_REST_Request $request)
    {
        $res = new Result();

        $params = $request->get_params();
        $order = wc_get_order($params['orderId']);
        $requestNote = $params['note'];

        if ($order) {

            foreach ($order->get_items() as $oneItem) {
                $lineItemId = $oneItem->get_meta('lineItemId');
                if ($lineItemId == $params['lineItemId']) {
                    $indexItem = $oneItem->get_id();
                    wc_update_order_item_meta($indexItem, "instead_of_product_note_{$lineItemId}", $requestNote);
                }
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'success';

        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không tìm thấy đơn hàng';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function makeChangeItem(WP_REST_Request $request)
    {
        $res = new Result();
        $orderId = $_REQUEST['id'] ?? null;
        if ($orderId) {
            $order = wc_get_order($orderId);
            $insteadValid = true;
            $insteadValidMessage = '';
            $selectedVouchers = $order->get_meta('selected_vouchers');
            $items = [];
            $newItems = [];
            $lineItemIds = [];
            foreach ($order->get_items() as $oneItem) {
                $newItem = new stdClass();
                $lineItemId = $oneItem->get_meta('lineItemId');
                $parentLineItemId = $oneItem->get_meta('parentLineItemId');
                $productId = $oneItem->get_meta("instead_of_product_id_{$lineItemId}");
                $rkCode = '';
                if ($productId) {
                    $getProduct = \GDelivery\Libs\Helper\Product::getDetailProduct($productId);
                    if ($getProduct->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $productInstead = $getProduct->result;
                        $rkCode = $productInstead->rkCode;
                        $newItem->productId = $productInstead->wooId;
                        $indexItem = $oneItem->get_id();
                        $quantity = $oneItem->get_meta("instead_of_quantity_{$lineItemId}");
                        $newItem->quantity = $quantity;
                        $newItem->lineItemId = $lineItemId;
                        $newItem->name = $productInstead->name;
                        $newItem->salePrice = $productInstead->salePrice;
                        $newItem->regularPrice = $productInstead->regularPrice;
                        $newItem->priceAfterTax = $productInstead->priceAfterTax;
                        $newItem->sapPriceAfterTax = $productInstead->sapPriceAfterTax;
                        $newItem->taxRateValue = $productInstead->taxRateValue;
                        $lineItemIds[] = $lineItemId;
                        $insteadModifiers = $oneItem->get_meta("instead_of_modifier_{$lineItemId}");
                        if ($insteadModifiers) {
                            $newItem->modifiers = $insteadModifiers;
                        }
                        $newItems[] = $newItem;

                    } else {
                        $insteadValid = false;
                        $insteadValidMessage .= "Sản phẩm ID: {$productId} không hợp lệ";
                    }
                } else {
                    if (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) {
                        $rkCode = get_field('product_variation_rk_code', $oneItem->get_data()['variation_id']);
                        $newItem->productId = $oneItem->get_data()['variation_id'];
                    } else {
                        $rkCode = get_field('product_rk_code', $oneItem->get_data()['product_id']);
                        $newItem->productId = $oneItem->get_data()['product_id'];
                    }
                    $newItem->quantity = (int)$oneItem->get_quantity();
                    $newItem->name = $oneItem->get_name();
                    $newItem->salePrice = (double)$oneItem->get_meta('salePrice');
                    $newItem->regularPrice = (double)$oneItem->get_meta('regularPrice');
                    $newItem->priceAfterTax = (double)$oneItem->get_meta('priceAfterTax');
                    $newItem->sapPriceAfterTax = (double)$oneItem->get_meta('sapPriceAfterTax');
                    $newItem->lineItemId = $lineItemId;
                    $newItem->parentLineItemId = $oneItem->get_meta('parentLineItemId');
                    $newItem->taxRateValue = \GDelivery\Libs\Helper\Product::getTaxRateValue($productId);
                }
                $newItem->rkCode = $rkCode;
                $items[] = $newItem;
            }

            foreach ($order->get_items() as $itemId => $oneItem) {
                $indexItem = $oneItem->get_id();
                $lineItemId = $oneItem->get_meta('lineItemId');
                $insteadToppings = $oneItem->get_meta("instead_of_topping_id_{$lineItemId}");
                if ($insteadToppings) {
                    foreach ($insteadToppings as $topping) {
                        $getTopping = \GDelivery\Libs\Helper\Product::getDetailProduct($topping);
                        if ($getTopping->messageCode == \Abstraction\Object\Message::SUCCESS) {
                            $toppingInstead = $getTopping->result;

                            $newItem = new stdClass();
                            $newItem->productId = $toppingInstead->wooId;
                            $quantity = $oneItem->get_meta("instead_of_quantity_{$lineItemId}");
                            $newItem->quantity = $quantity;
                            $newItem->name = $toppingInstead->name;
                            $newItem->salePrice = $toppingInstead->salePrice;
                            $newItem->regularPrice = $toppingInstead->regularPrice;
                            $newItem->priceAfterTax = $toppingInstead->priceAfterTax;
                            $newItem->sapPriceAfterTax = $toppingInstead->sapPriceAfterTax;
                            $newItem->lineItemId = uniqid();
                            $newItem->parentLineItemId = $lineItemId;
                            $newItem->taxRateValue = $toppingInstead->taxRateValue;
                            $items[] = $newItem;
                            $newItems[] = $newItem;
                        } else {
                            $insteadValid = false;
                            $insteadValidMessage .= "Sản phẩm ID: {$topping} không hợp lệ";
                        }
                    }
                }
            }
            if ($insteadValid) {
                foreach ($items as &$item) {
                    if (isset($item->parentLineItemId) && in_array($item->parentLineItemId, $lineItemIds)) {
                        unset($item);
                    }
                }
                unset($item);

                $products = \GDelivery\Libs\Helper\Balance::productDiscountAndTax($items, $selectedVouchers);
                $totalTax = 0;
                foreach ($products as $product) {
                    $totalTax += $product['tax'];
                }

                // Todo old total
                $totalPrice = (float)$order->get_meta('total_price');
                $totalDiscount = (float)$order->get_discount_total('number');
                $totalPaySum = (float)$order->get_meta('total_pay_sum');

                $shippingObj = new \stdClass();
                $shippingObj->price = (float)$order->get_meta('shipping_price');
                $shippingObj->tax = $order->get_shipping_tax('number');
                $shippingObj->total = (float)$order->get_shipping_total('number');
                $totalTaxOld = $order->get_meta('total_tax');

                // Todo new total
                $totalWithoutShipping = 0;
                foreach ($items as $item) {
                    $totalWithoutShipping += ($item->salePrice ?: $item->regularPrice) * $item->quantity;
                }
                $newTotalPrice = $totalWithoutShipping + $shippingObj->price;
                $newTotalTax = $totalTax + $shippingObj->tax;
                $newTotalPaySum = $newTotalPrice + $newTotalTax - $totalDiscount;


                if ($newTotalPrice >= $totalPrice) {
                    $totalCashVoucher = 0;
                    $totalDiscount = 0;
                    foreach ($selectedVouchers as $voucher) {
                        if ($voucher->type == 1) {
                            $totalCashVoucher += $voucher->denominationValue;
                        } else {
                            $totalDiscount += $voucher->denominationValue;
                        }
                    }

                    // Todo remove and add new
                    foreach ($order->get_items() as $item) {
                        $lineItemId = $item->get_meta('lineItemId');
                        $parentLineItemId = $item->get_meta('parentLineItemId');
                        if (
                            in_array($lineItemId, $lineItemIds)
                            || (
                                $parentLineItemId
                                && in_array($parentLineItemId, $lineItemIds)
                            )
                        ) {
                            wc_delete_order_item($item->get_id());
                        }
                    }

                    foreach ($newItems as $item) {
                        $newIndexItem = $order->add_product(wc_get_product($item->productId), $item->quantity);
                        $newItemPrice = $item->salePrice ?: $item->regularPrice;
                        wc_update_order_item_meta($newIndexItem, '_line_total', $newItemPrice * $item->quantity);

                        if (isset($item->modifiers) && $item->modifiers) {
                            $modifiers = [];
                            foreach ($item->modifiers as $itemModifier) {
                                $modifier = new \stdClass();
                                $modifier->categoryId = $itemModifier->categoryId;
                                $modifier->categoryName = $itemModifier->categoryName;
                                $dataModifiers = [];
                                foreach ($itemModifier->data as $itemDataModifier) {
                                    $dataModifier = new \stdClass();
                                    $dataModifier->id = $itemDataModifier->id;
                                    $dataModifier->name = $itemDataModifier->name;
                                    $dataModifiers[] = $dataModifier;
                                }
                                $modifier->data = $dataModifiers;
                                $modifiers[] = $modifier;
                            }
                            wc_update_order_item_meta($newIndexItem, 'modifier', $modifiers);
                        }
                        if (isset($item->lineItemId) && $item->lineItemId) {
                            wc_update_order_item_meta($newIndexItem, 'lineItemId', $item->lineItemId);
                        }
                        if (isset($item->parentLineItemId) && $item->parentLineItemId) {
                            wc_update_order_item_meta($newIndexItem, 'parentLineItemId', $item->parentLineItemId);
                        }
                        wc_update_order_item_meta($newIndexItem, 'salePrice', $item->salePrice);
                        wc_update_order_item_meta($newIndexItem, 'regularPrice', $item->regularPrice);
                        wc_update_order_item_meta($newIndexItem, 'priceAfterTax', $item->priceAfterTax);
                        wc_update_order_item_meta($newIndexItem, 'sapPriceAfterTax', $item->sapPriceAfterTax);
                    }

                    // total tax
                    $order->set_cart_tax($newTotalTax);
                    $order->update_meta_data('total_tax', $newTotalTax);

                    // total price
                    $order->update_meta_data('total_price', $newTotalPrice);
                    $order->set_total($newTotalPrice - $totalDiscount + $newTotalTax);
                    $order->update_meta_data('old_total_price', $totalPrice);

                    $order->update_meta_data('total_pay_sum', $newTotalPaySum);
                    $order->update_meta_data('total_price_without_shipping', $totalWithoutShipping);
                    $order->update_meta_data('old_total_pay_sum', $totalPaySum);

                    $order->delete_meta_data('is_request_change_product');
                    $order->update_meta_data('request_change_product', 'success');

                    // $order->update_status($status, $note); // set new status
                    $order->save();

                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'success';
                    $res->result = $order;
                } else {
                    // Todo lỗi
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Số tiền của sản phẩm thay thế không hợp lệ';
                }
            } else {
                // Todo lỗi
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = $insteadValidMessage;
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không tìm thấy đơn hàng';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function prepareAddress($data = [], $oldAddress = null)
    {
        $selectedAddress = new \stdClass();
        $selectedAddress->addressId = $data['addressId'] ?? (empty($oldAddress) ? '' : $oldAddress->addressId);
        $selectedAddress->alias = $data['alias'] ?? (empty($oldAddress) ? '' : $oldAddress->alias);
        $selectedAddress->name = $data['recipientName'];
        $selectedAddress->phone = $data['recipientCellphone'];
        $selectedAddress->addressLine1 = $selectedAddress->address = $data['deliveryAddress'];
        $selectedAddress->wardId = (int) $data['wardId'];
        $selectedAddress->wardName = $data['wardName'] ?? (empty($oldAddress) ? '' : $oldAddress->wardName);
        $selectedAddress->districtId = (int) ($data['districtId'] ?? (empty($oldAddress) ? '' : $oldAddress->districtId));
        $selectedAddress->districtName = $data['districtName'] ?? (empty($oldAddress) ? '' : $oldAddress->districtName);
        $selectedAddress->provinceId = (int) ($data['provinceId'] ?? (empty($oldAddress) ? '' : $oldAddress->provinceId));
        $selectedAddress->provinceName = $data['provinceName'] ?? (empty($oldAddress) ? '' : $oldAddress->provinceName);
        $selectedAddress->longitude = $data['longitude'] ?? (empty($oldAddress) ? '' : $oldAddress->longitude);
        $selectedAddress->latitude = $data['latitude'] ?? (empty($oldAddress) ? '' : $oldAddress->latitude);

        // get ward info
//        $wardInfo = $this->bookingService->getWard($selectedAddress->wardId)->result;
//        $selectedAddress->longitude = $wardInfo->longitude;
//        $selectedAddress->latitude = $wardInfo->latitude;

        return $selectedAddress;
    }

    public function validateOrder($data)
    {

        $response = new \stdClass();
        $response->status = true;
        $response->message = '';

        $validate = $this->validate($data);
        if (!$validate->valid) {
            $response->status = false;
            $response->message = $validate->message;
            return $response;
        }

        $deliveryInfo = $data['deliveryInfo'];
        $validateDeliveryInfo = $this->validateDeliveryInfo($deliveryInfo);
        if (!$validateDeliveryInfo->valid) {
            $response->status = false;
            $response->message = $validateDeliveryInfo->message;
            return $response;
        }

        /*if (isset($data['coupons']) && !empty($data['coupons'])) {
            $validateCoupons = $this->validateCoupons($data['coupons']);
            if (!$validateCoupons->valid) {
                $response->status = false;
                $response->message = $validateCoupons->message;
                return $response;
            }
        }


        $items = $data['items'];
        foreach ($items as $item) {
            $validateItems = $this->validateItems($item);
            if (!$validateItems->valid) {
                $response->status = false;
                $response->message = $validateItems->message;
                return $response;
            }
        }*/

        $shipping = $data['shipping'];
        $validateShipping = $this->validateShipping($shipping);
        if (!$validateShipping->valid) {
            $response->status = false;
            $response->message = $validateShipping->message;
            return $response;
        }

        return $response;
    }

    public function validateOrderUpdate($data)
    {
        $response = new \stdClass();
        $response->status = true;
        $response->message = '';

        $required = ['recipientName', 'recipientCellphone', 'deliveryTime', 'deliveryDate', 'deliveryAddress'];
        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;

                return $response;
            }
        }

        return $response;
    }

    public function validate($data)
    {
        $response = new \stdClass();

        $required = ['paymentMethod', 'customerNumber', 'restaurantCode', 'totalPriceWithoutShipping', 'totalPrice', 'totalDiscount', 'totalTax', 'totalCashVoucher', 'shippingTotal', 'total', 'totalPaySum'];
        $numbers = ['restaurantCode', 'totalPriceWithoutShipping', 'totalPrice', 'totalDiscount', 'totalTax', 'totalCashVoucher', 'shippingTotal', 'total', 'totalPaySum'];

        $response->valid = true;
        $response->message = '';

        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;
                continue;
            }
        }

        if ($response->valid) {
            foreach ($data as $key => $value) {
                if (in_array($key, $numbers) && !is_numeric((float) $value)) {
                    $response->valid = false;
                    $response->message = 'Giá trị của : '. $key . ' phải là số';
                    continue;
                }
            }
        }

        return $response;
    }

    public function validateDeliveryInfo($data) {
        $response = new \stdClass();

        $required = ['recipientName', 'recipientCellphone', 'deliveryTime', 'deliveryDate', 'deliveryAddress', 'wardId', 'wardName', 'districtId', 'districtName', 'provinceId', 'provinceName'];
        $numbers = ['wardId', 'districtId', 'provinceId'];

        $response->valid = true;
        $response->message = '';

        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;
                continue;
            }
        }

        if ($response->valid) {
            foreach ($data as $key => $value) {
                if (in_array($key, $numbers) && !is_numeric((float) $value)) {
                    $response->valid = false;
                    $response->message = 'Giá trị của : '. $key . ' phải là số';
                    continue;
                }

                if(($key == 'recipientCellphone') && (!preg_match('/^[0-9]{10}+$/', $value))) {
                    $response->valid = false;
                    $response->message = 'Số điện thoại không hợp lệ';
                    continue;
                }
            }
        }

        return $response;
    }

    public function validateCoupons($data) {

        $response = new \stdClass();

        $required = ['code', 'denominationValue', 'type'];
        $numbers = ['wardId', 'districtId', 'provinceId'];

        $response->valid = true;
        $response->message = '';

        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;
                continue;
            }
        }

        if ($response->valid) {
            foreach ($data as $key => $value) {
                if (in_array($key, $numbers) && !is_numeric((float) $value)) {
                    $response->valid = false;
                    $response->message = 'Giá trị của : '. $key . ' phải là số';
                    continue;
                }
            }
        }

        return $response;
    }

    public function validateShipping($data) {
        $response = new \stdClass();

        $required = ['price', 'tax', 'total'];
        $numbers = ['price', 'tax', 'total'];

        $response->valid = true;
        $response->message = '';

        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;
                continue;
            }
        }

        if ($response->valid) {
            foreach ($data as $key => $value) {
                if (in_array($key, $numbers) && !is_numeric((float) $value)) {
                    $response->valid = false;
                    $response->message = 'Giá trị của : '. $key . ' phải là số';
                    continue;
                }
            }
        }

        return $response;
    }

    public function validateItems($data) {

        $response = new \stdClass();

        $required = ['name', 'productId', 'quantity', 'subTotal', 'total'];
        $numbers = ['subTotal', 'subTotalTax', 'total', 'totalTax', 'quantity', 'variationId'];

        $response->valid = true;
        $response->message = '';

        $keys = array_keys($data);
        foreach ($required as $key) {
            if (!in_array($key, $keys)) {
                $response->valid = false;
                $response->message = 'Cần truyền đầy đủ thông tin: '. $key;
                continue;
            }
        }

        if ($response->valid) {
            foreach ($data as $key => $value) {
                if (in_array($key, $numbers) && !is_numeric((float) $value)) {
                    $response->valid = false;
                    $response->message = 'Giá trị của : '. $key . ' phải là số';
                    continue;
                }
            }
        }

        return $response;
    }
}

// init
$orderApi = new OrderApi();
