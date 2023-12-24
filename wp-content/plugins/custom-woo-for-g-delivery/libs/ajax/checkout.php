<?php
class AjaxCheckout extends \Abstraction\Core\AAjaxHook {

    private $tgsService;
    private $bookingService;
    private $masService;

    private function initDeliveryInfo($data = [])
    {
        $deliveryInfo = new \stdClass();

        $deliveryInfo->pickupAtRestaurant = $data['pickupAtRestaurant'];

        $deliveryInfo->recipientCellphone = $data['recipientCellphone'];
        $deliveryInfo->recipientName = $data['recipientName'];

        $deliveryInfo->deliveryTime = $data['deliveryTime'];
        $deliveryInfo->deliveryDate = $data['deliveryDate'];

        $deliveryInfo->note = $data['note'];
        $deliveryInfo->noteForDriver = $data['noteForDriver'];
        $deliveryInfo->useCutleryTool = $data['cutleryTool'] ?: 0;

        // process selected address
        $selectedAddress = new \stdClass();
        $selectedAddress->name = $deliveryInfo->recipientName;
        $selectedAddress->phone = $deliveryInfo->recipientCellphone;

        // get ward info if need
        if (isset($data['wardId']) && $data['wardId'] > 0 && $data['wardId'] < 999999) {
            $deliveryInfo->deliveryAddress = $data['deliveryAddress'];
            $selectedAddress->addressLine1 = $selectedAddress->address = $deliveryInfo->deliveryAddress;

            $selectedAddress->wardId = $data['wardId'];
            $selectedAddress->wardName = isset($data['wardName']) ? $data['wardName'] : '';

            $wardInfo = $this->bookingService->getWard($selectedAddress->wardId)->result;
            $selectedAddress->longitude = $wardInfo->longitude;
            $selectedAddress->latitude = $wardInfo->latitude;
        } else {
            // in case use Google map address
            $selectedAddress->wardId = null;

            $textDeliveryAddress = $data['deliveryAddress'];
            $addressParams = explode(',', $textDeliveryAddress);

            if (\count($addressParams) == 6) {
                // in case: "Tầng 5 TTTM Hà Nội Centerpoint, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội, Vietnam"
                $selectedAddress->addressLine1 = $selectedAddress->address = $deliveryInfo->deliveryAddress = trim($addressParams[0]).', '.trim($addressParams[1]);
                $selectedAddress->wardName = trim($addressParams[2]);
            } elseif (\count($addressParams) == 5) {
                // in case: "315 Trường Chinh, Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                $selectedAddress->addressLine1 = $selectedAddress->address = $deliveryInfo->deliveryAddress = trim($addressParams[0]);
                $selectedAddress->wardName = trim($addressParams[1]);
            } elseif (\count($addressParams) == 4) {
                // in case: "Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                $selectedAddress->addressLine1 = $selectedAddress->address = $deliveryInfo->deliveryAddress = trim($addressParams[0]);
                $selectedAddress->wardName = '';
            } else {
                $selectedAddress->addressLine1 = $selectedAddress->address = $deliveryInfo->deliveryAddress = $textDeliveryAddress;
                $selectedAddress->wardName = '';
            }
        }

        if (isset($data['wardName']) && $data['wardName']) {
            // in case request data is already has ward name, use it
            $selectedAddress->wardName = $data['wardName'];
        }

        if (isset($data['longitude'], $data['latitude']) && $data['longitude'] & $data['latitude']) {
            $selectedAddress->longitude = $data['longitude'];
            $selectedAddress->latitude = $data['latitude'];
        }

        $selectedAddress->id = isset($data['addressId']) ? $data['addressId'] : '';
        $selectedAddress->districtId = isset($data['districtId']) ? $data['districtId'] : '';
        $selectedAddress->districtName = isset($data['districtName']) ? $data['districtName'] : '';
        $selectedAddress->provinceId = isset($data['provinceId']) ? $data['provinceId'] : '';
        $selectedAddress->provinceName = isset($data['provinceName']) ? $data['provinceName'] : '';
        $selectedAddress->googleMapPlaceId = isset($data['googleMapPlaceId']) ? $data['googleMapPlaceId'] : null;

        // set to session
        \GDelivery\Libs\Helper\Helper::setDeliveryInfo($deliveryInfo);
        \GDelivery\Libs\Helper\Helper::setSelectedAddress($selectedAddress);
    }

    public function __construct()
    {
        parent::__construct();

        $this->tgsService = new \GDelivery\Libs\TGSService();
        $this->bookingService = new \GDelivery\Libs\BookingService();
        $this->masService = new \GDelivery\Libs\MasOfferService();

        // set delivery info
        add_action("wp_ajax_set_delivery_info", [$this, "setDeliveryInfo"]);
        add_action("wp_ajax_nopriv_set_delivery_info", [$this, "mustLogin"]);

        // select address
        add_action("wp_ajax_select_address", [$this, "selectAddress"]);
        add_action("wp_ajax_nopriv_select_address", [$this, "mustLogin"]);

        // set pickup at restaurant
        add_action("wp_ajax_set_pickup_at_restaurant", [$this, "setPickupAtRestaurant"]);
        add_action("wp_ajax_nopriv_set_pickup_at_restaurant", [$this, "mustLogin"]);

        // create order
        add_action("wp_ajax_create_order", [$this, "createOrder"]);
        add_action("wp_ajax_nopriv_create_order", [$this, "mustLogin"]);

    }

    public function setDeliveryInfo()
    {
        $res = new \Abstraction\Object\Result();
        $selectedRestaurant = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant();
        // Return error when restaurant empty.
        \GDelivery\Libs\Helper\Restaurant::selectedRestaurantEmpty($selectedRestaurant, $res);

        // Return error when restaurant closed.
        \GDelivery\Libs\Helper\Restaurant::checkStatusRestaurant($selectedRestaurant, $res);

        if (isset($_REQUEST['beHonest'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "set_delivery_info")) {

                $this->initDeliveryInfo($_REQUEST);

                $validTime = \GDelivery\Libs\Helper\Order::checkTimeOrder(
                    \GDelivery\Libs\Helper\Helper::getCurrentCategory(),
                    $_REQUEST['deliveryDate'],
                    $_REQUEST['deliveryTime']
                );
                if (!$validTime['isValid']) {
                    $res->messageCode = \Abstraction\Object\Message::MOVED_PERMANENTLY;
                    $res->message = 'Thời gian hẹn giao hàng không còn hiệu lực. Vui lòng kiểm tra lại';
                    $res->result = $validTime['validTimes'];

                    \GDelivery\Libs\Helper\Response::returnJson($res);
                    die;
                }

                // get current customer address
                $selectedAddress = \GDelivery\Libs\Helper\Helper::getSelectedAddress();

                // save customer address
                if (isset($selectedAddress->id) && $selectedAddress->id) {
                    $saveAddress = $this->tgsService->updateAddress(
                        $selectedAddress->id,
                        [
                            'name' => isset($selectedAddress->name) && $selectedAddress->name ? $selectedAddress->name : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->fullName,
                            'phone' => isset($selectedAddress->phone) && $selectedAddress->phone ? $selectedAddress->phone : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->cellphone,
                            'addressLine1' => $selectedAddress->addressLine1,
                            'wardId' => $selectedAddress->wardId,
                            'wardName' => $selectedAddress->wardName,
                            'districtId' => $selectedAddress->districtId,
                            'districtName' => $selectedAddress->districtName,
                            'provinceId' => $selectedAddress->provinceId,
                            'provinceName' => $selectedAddress->provinceName,
                            'longitude' => $selectedAddress->longitude,
                            'latitude' => $selectedAddress->latitude,
                            'googleMapPlaceId' => $selectedAddress->googleMapPlaceId
                        ],
                        \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                    );
                } else {
                    // save selected address
                    $saveAddress = $this->tgsService->addNewAddress(
                        [
                            'name' => isset($selectedAddress->name) && $selectedAddress->name ? $selectedAddress->name : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->fullName,
                            'phone' => isset($selectedAddress->phone) && $selectedAddress->phone ? $selectedAddress->phone : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->cellphone,
                            'addressLine1' => $selectedAddress->addressLine1,
                            'wardId' => $selectedAddress->wardId,
                            'wardName' => $selectedAddress->wardName,
                            'districtId' => $selectedAddress->districtId,
                            'districtName' => $selectedAddress->districtName,
                            'provinceId' => $selectedAddress->provinceId,
                            'provinceName' => $selectedAddress->provinceName,
                            'longitude' => $selectedAddress->longitude,
                            'latitude' => $selectedAddress->latitude,
                            'googleMapPlaceId' => $selectedAddress->googleMapPlaceId
                        ],
                        \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                    );
                }

                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                $res->message = 'Thành công';
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

    public function setPickupAtRestaurant()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "set_pickup_at_restaurant")) {

                $deliveryInfo = \GDelivery\Libs\Helper\Helper::getDeliveryInfo();

                if ($deliveryInfo) {
                    $deliveryInfo->pickupAtRestaurant = $_REQUEST['pickupAtRestaurant'];
                } else {
                    $deliveryInfo = new \stdClass();
                    $deliveryInfo->pickupAtRestaurant = $_REQUEST['pickupAtRestaurant'];
                }

                // set to session
                \GDelivery\Libs\Helper\Helper::setDeliveryInfo($deliveryInfo);

                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                $res->message = 'Thành công';
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

    public function selectAddress()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['id'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "select_address")) {
                // get address info
                $getAddress = $this->tgsService->getAddressInfo(
                    $_REQUEST['id'],
                    \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                );
                if ($getAddress->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $address = $getAddress->result;
                    // current province
                    $selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();
                    if (get_option('google_map_service_address') == 'goong_address') {
                        if ($selectedProvince->id == $address->provinceId) {
                            // set more ward's long/lat to customer address
                            $getWardInfo = $this->bookingService->getWard($address->wardId);
                            if ($getWardInfo->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                $address->longitude = $getWardInfo->result->longitude;
                                $address->latitude = $getWardInfo->result->latidue;
                            }

                            \GDelivery\Libs\Helper\Helper::setSelectedAddress($address);

                            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                            $res->message = 'Thành công';
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Địa chỉ bạn chọn không phù hợp với nhà hàng phục vụ đơn';
                        }
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = 'Thành công';
                        \GDelivery\Libs\Helper\Helper::setSelectedAddress($address);
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Không tìm thấy thông tin địa chỉ';
                }
                // set to session
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

    public function createOrder()
    {
        $res = new \Abstraction\Object\Result();
        $selectedRestaurant = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant();
        // Return error when restaurant empty.
        \GDelivery\Libs\Helper\Restaurant::selectedRestaurantEmpty($selectedRestaurant, $res);

        // Return error when restaurant closed.
        \GDelivery\Libs\Helper\Restaurant::checkStatusRestaurant($selectedRestaurant, $res);

        // process login
        if (isset($_REQUEST['beHonest'], $_REQUEST['paymentMethod'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "create_order")) {
                if (!WC()->cart->is_empty()) {

                    if (isset($_REQUEST['deliveryInfo']) && $_REQUEST['deliveryInfo']['recipientCellphone']) {
                        $phoneNumber = $_REQUEST['deliveryInfo']['recipientCellphone'];
                        $isPhoneNumber = preg_match('(^(09|03|07|08|05)+([0-9]{8})$)', $phoneNumber);
                        if (!$isPhoneNumber) {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Vui lòng nhập số điện thoại người nhận hợp lệ, ví dụ: 0962471230';
                            \GDelivery\Libs\Helper\Response::returnJson($res);
                            die;
                        }
                    }

                    // payment service
                    $paymentService = new \GDelivery\Libs\PaymentHubService();
                    // internal affiliate server
                    $internalAffService = new \GDelivery\Libs\InternalAffiliateService();

                    // if delivery info passed, init them first
                    if (isset($_REQUEST['deliveryInfo'])) {
                        $this->initDeliveryInfo($_REQUEST['deliveryInfo']);

                        $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
                        $validTime = \GDelivery\Libs\Helper\Order::checkTimeOrder(
                            $currentCategory,
                            $_REQUEST['deliveryInfo']['deliveryDate'],
                            $_REQUEST['deliveryInfo']['deliveryTime']
                        );
                        if (!$validTime['isValid']) {
                            $res->messageCode = \Abstraction\Object\Message::MOVED_PERMANENTLY;
                            $res->message = 'Thời gian hẹn giao hàng không còn hiệu lực. Vui lòng kiểm tra lại';
                            $res->result = $validTime['validTimes'];

                            \GDelivery\Libs\Helper\Response::returnJson($res);
                            die;
                        }

                        // current customer address
                        $selectedAddress = \GDelivery\Libs\Helper\Helper::getSelectedAddress();

                        // save customer address
                        if (isset($selectedAddress->id) && $selectedAddress->id) {
                            $saveAddress = $this->tgsService->updateAddress(
                                $selectedAddress->id,
                                [
                                    'name' => isset($selectedAddress->name) && $selectedAddress->name ? $selectedAddress->name : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->fullName,
                                    'phone' => isset($selectedAddress->phone) && $selectedAddress->phone ? $selectedAddress->phone : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->cellphone,
                                    'addressLine1' => $selectedAddress->addressLine1,
                                    'wardId' => $selectedAddress->wardId,
                                    'wardName' => $selectedAddress->wardName,
                                    'districtId' => $selectedAddress->districtId,
                                    'districtName' => $selectedAddress->districtName,
                                    'provinceId' => $selectedAddress->provinceId,
                                    'provinceName' => $selectedAddress->provinceName,
                                    'longitude' => $selectedAddress->longitude,
                                    'latitude' => $selectedAddress->latitude,
                                    'googleMapPlaceId' => $selectedAddress->googleMapPlaceId
                                ],
                                \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                            );
                        } else {
                            // save selected address
                            $saveAddress = $this->tgsService->addNewAddress(
                                [
                                    'name' => isset($selectedAddress->name) && $selectedAddress->name ? $selectedAddress->name : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->fullName,
                                    'phone' => isset($selectedAddress->phone) && $selectedAddress->phone ? $selectedAddress->phone : \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo->cellphone,
                                    'addressLine1' => $selectedAddress->addressLine1,
                                    'wardId' => $selectedAddress->wardId,
                                    'wardName' => $selectedAddress->wardName,
                                    'districtId' => $selectedAddress->districtId,
                                    'districtName' => $selectedAddress->districtName,
                                    'provinceId' => $selectedAddress->provinceId,
                                    'provinceName' => $selectedAddress->provinceName,
                                    'longitude' => $selectedAddress->longitude,
                                    'latitude' => $selectedAddress->latitude,
                                    'googleMapPlaceId' => $selectedAddress->googleMapPlaceId
                                ],
                                \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                            );
                        }
                    }

                    // delivery info
                    $deliveryInfo = \GDelivery\Libs\Helper\Helper::getDeliveryInfo();

                    $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
                    $validTime = \GDelivery\Libs\Helper\Order::checkTimeOrder(
                        $currentCategory,
                        $deliveryInfo->deliveryDate,
                        $deliveryInfo->deliveryTime
                    );
                    if (!$validTime['isValid']) {
                        $res->messageCode = \Abstraction\Object\Message::MOVED_PERMANENTLY;
                        $res->message = 'Thời gian hẹn giao hàng không còn hiệu lực. Vui lòng kiểm tra lại';
                        $res->result = $validTime['validTimes'];

                        \GDelivery\Libs\Helper\Response::returnJson($res);
                        die;
                    }

                    // selected address
                    $selectedAddress = \GDelivery\Libs\Helper\Helper::getSelectedAddress();

                    $deliveryTime = $deliveryInfo->deliveryTime;
                    $deliveryDate = $deliveryInfo->deliveryDate;
                    $pickupAtRestaurant = $deliveryInfo->pickupAtRestaurant;
                    $recipientName = $deliveryInfo->recipientName;
                    $recipientCellphone = $deliveryInfo->recipientCellphone;
                    $deliveryAddress = $deliveryInfo->deliveryAddress;

                    $paymentMethod = isset($_REQUEST['paymentMethod']) ? $_REQUEST['paymentMethod'] : null;

                    $note = $deliveryInfo->note;
                    $noteForDriver = $deliveryInfo->noteForDriver;

                    $invoice = isset($_REQUEST['invoice']) ? $_REQUEST['invoice'] : ['info' => 0];

                    $useCutleryTool = isset($deliveryInfo->useCutleryTool) ? $deliveryInfo->useCutleryTool : 0;

                    if ($paymentMethod) {

                        // Now we create the order
                        $order = wc_create_order();

                        // cart
                        $cartObject = WC()->cart;

                        // check pickup order at restaurant
                        $shippingFee = 0;
                        if ($pickupAtRestaurant == 1) {
                            if (get_option('google_map_service_address') == 'goong_address') {
                                $shippingVendor = 'restaurant';
                            } else {
                                $shippingVendor = 'grab_express';
                            }

                            $totals = \GDelivery\Libs\Helper\Helper::calculateCartTotals(
                                $cartObject,
                                [
                                    'pickupAtRestaurant' => $pickupAtRestaurant,
                                    'shippingVendor' => $shippingVendor
                                ]
                            );

                            // shipping address
                            $shippingAddress = array(
                                'first_name' => $recipientName,
                                'phone' => $recipientCellphone,
                                'email' => wp_get_current_user()->user_email,
                                'address_1' => 'Nhận tại cửa hàng',
                            );
                            $order->update_meta_data('is_pickup_at_restaurant', 1);
                        } else {
                            if (get_option('google_map_service_address') == 'goong_address') {
                                $shippingVendor = 'restaurant';
                            } else {
                                $shippingVendor = 'grab_express';
                            }

                            $totals = \GDelivery\Libs\Helper\Helper::calculateCartTotals(
                                $cartObject,
                                [
                                    'pickupAtRestaurant' => 0,
                                    'recalculateShippingFee' => true,
                                    'paymentMethod' => $paymentMethod,
                                    'shippingVendor' => $shippingVendor
                                ]
                            );

                            // shipping address
                            $shippingAddress = array(
                                'first_name' => $recipientName,
                                'email' => wp_get_current_user()->user_email,
                                'phone' => $recipientCellphone,
                                'address_1' => $selectedAddress->addressLine1,
                                'address_2' => "{$selectedAddress->wardName}, {$selectedAddress->districtName}, {$selectedAddress->provinceName}",
                            );

                            $order->update_meta_data('is_pickup_at_restaurant',
                                0);
                        }
                        // billing address
                        $billingAddress = array(
                            'first_name' => $recipientName,
                            'last_name' => '',
                            'company' => '',
                            'email' => wp_get_current_user()->user_email,
                            'phone' => $recipientCellphone,
                            'address_1' => $selectedAddress->addressLine1,
                            'address_2' => "{$selectedAddress->wardName}, {$selectedAddress->districtName}, {$selectedAddress->provinceName}",
                            'city' => '',
                            'state' => '',
                            'postcode' => '',
                            'country' => ''
                        );

                        $order->set_address($billingAddress, 'billing');
                        $order->set_address($shippingAddress, 'shipping');

                        // process discount
                        if ($totals->totalDiscount > 0) {
                            foreach (
                                $cartObject->get_coupons() as $couponCode =>
                                $couponObject
                            ) {
                                // todo check more rule about coupon
                                // firstly, add first coupon code and discount total
                                $order->add_coupon($couponCode,
                                    $totals->totalDiscount);
                            }
                        }

                        foreach (
                            WC()->cart->get_cart() as $cart_item_key =>
                            $cart_item
                        ) :
                            $_product = $cart_item['data'];
                            // Only display if allowed
                            if (!apply_filters('woocommerce_widget_cart_item_visible',
                                    true, $cart_item, $cart_item_key)
                                || !$_product->exists()
                                || $cart_item['quantity'] == 0) {
                                continue;
                            }

                            if (isset($cart_item['variation_id']) && $cart_item['variation_id'] != '') {
                                $order->add_product(wc_get_product($cart_item['variation_id']), $cart_item['quantity']);
                            } else {
                                $order->add_product(wc_get_product($cart_item['product_id']), $cart_item['quantity']);
                            }

                        endforeach;

                        $order->set_customer_id(get_current_user_id());
                        $order->set_customer_note($note);  // Add the note
                        $order->update_meta_data('note_for_driver',
                            $noteForDriver); // note for driver

                        // set shipping fee
                        if ($totals->shippingFee > 0) {
                            $order->set_shipping_tax(\GDelivery\Libs\Helper\Helper::getTempShippingFee()->tax);
                            $order->update_meta_data('shipping_price', \GDelivery\Libs\Helper\Helper::getTempShippingFee()->price);
                            $order->update_meta_data('shipping_distance', \GDelivery\Libs\Helper\Helper::getTempShippingFee()->distance);
                        } else {
                            $order->set_shipping_tax(0);
                            $order->update_meta_data('shipping_price', 0);
                            $order->update_meta_data('shipping_distance', 0);
                        }
                        $order->set_shipping_total($totals->shippingFee);

                        // add total tax
                        $order->set_cart_tax($totals->totalTax);
                        $order->update_meta_data('total_tax', $totals->totalTax);

                        // add discount total
                        $order->set_discount_total($totals->totalDiscount);

                        // total price
                        $order->update_meta_data('total_price', $totals->totalPrice);

                        // total
                        $order->set_total($totals->total);

                        // save
                        $order->save();

                        // update meta
                        $order->update_meta_data('delivery_time', $deliveryTime);
                        $order->update_meta_data('delivery_date', $deliveryDate);
                        // restaurant
                        $order->update_meta_data('restaurant_in_tgs',
                            \GDelivery\Libs\Helper\Helper::getSelectedRestaurant());
                        $order->update_meta_data('restaurant_code',
                            \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant->code);
                        $order->update_meta_data('restaurant_object',
                            \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant);
                        $restaurantHistories = [];
                        $restaurantHistories[] = [
                            'time' => time(),
                            'restaurant' => \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()
                        ];
                        $order->update_meta_data(
                            'restaurant_histories',
                            $restaurantHistories
                        );
                        // payment
                        $order->update_meta_data('payment_method', $paymentMethod);
                        // permission
                        $provinceBrand
                            = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant->province->id
                            .'_'
                            .\GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant->brand->id;
                        $order->update_meta_data('province_brand', $provinceBrand); // use for operator filter
                        // invoice
                        $order->update_meta_data('customer_invoice', $invoice);
                        // product category
                        $order->update_meta_data('current_product_category_id', \GDelivery\Libs\Helper\Helper::getCurrentCategory()->term_id);
                        // customer selected address
                        $order->update_meta_data('customer_selected_address', \GDelivery\Libs\Helper\Helper::getSelectedAddress());
                        // actual shipping fee
                        $order->update_meta_data('actual_shipping_fee', $totals->shippingFee);

                        // use cutlery tool
                        $order->update_meta_data('use_cutlery_tool', $useCutleryTool);

                        // customer info
                        $order->update_meta_data('customer_info', \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerInfo);

                        // process and save cookie create cookie
                        // utm
                        $utm = new \stdClass();
                        $utm->utmSource = isset($_COOKIE['utm_source']) ? $_COOKIE['utm_source'] : '';
                        $utm->utmMedium = isset($_COOKIE['utm_medium']) ? $_COOKIE['utm_medium'] : '';
                        $utm->utmCampaign = isset($_COOKIE['utm_campaign']) ? $_COOKIE['utm_campaign'] : '';
                        $utm->utmContent = isset($_COOKIE['utm_content']) ? $_COOKIE['utm_content'] : '';
                        $utm->utmLocation = isset($_COOKIE['utm_location']) ? $_COOKIE['utm_location'] : '';
                        $utm->utmTerm = isset($_COOKIE['utm_term']) ? $_COOKIE['utm_term'] : '';

                        $order->update_meta_data('utm_data', $utm);

                        $masOffer = new \stdClass();
                        $masOffer->trafficId = isset($_COOKIE['mo_traffic_id']) ? $_COOKIE['mo_traffic_id'] : '';
                        if (isset($_COOKIE['mo_traffic_id']) && $_COOKIE['mo_traffic_id'] != '') {
                            $masOffer->isSuccess = 1; // 1 pending, 2 success, 3 failed
                        }
                        if (isset($_COOKIE['mo_utm_source']) && $_COOKIE['mo_utm_source'] == 'masoffer') {
                            $masOffer->utmSource = $_COOKIE['mo_utm_source'];
                        }
                        $order->update_meta_data('mo_utm_data', $masOffer);

                        // Save the order again for sure
                        $order->save();

                        // todo for nghi.bui: gọi api sang Masoffer với trạng thái pending
                        if (isset($_COOKIE['mo_traffic_id']) && $_COOKIE['mo_traffic_id'] != '') {
                            $requestMasOffer = $this->masService->transaction($order, 0);
                            if ($requestMasOffer->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                $masOffer->isSuccess = 2;
                            } else {
                                $masOffer->isSuccess = 3;
                            }
                            $order->update_meta_data('mo_utm_data', $masOffer);
                            $order->save();
                        }


                        // return object
                        $returnData = new \stdClass();
                        $returnData->id = $order->get_id();
                        $returnData->totals = $totals;

                        // process selected voucher
                        $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();

                        $processVouchers = true;
                        $processedVouchers = [];
                        $errorVoucher = '';
                        $processingClmVouchers = get_option('processing_clm_vouchers', []);
                        $hasInternalAffiliateVoucher = false;
                        foreach ($selectedVouchers as $oneVoucher) {
                            if ($oneVoucher->partnerId == 14) {
                                // save to db processing voucher
                                $processingClmVouchers[] = $oneVoucher->code;
                                $processedVouchers[] = $oneVoucher;
                            } elseif ($oneVoucher->partnerId == 999) {
                                $hasInternalAffiliateVoucher = true;

                                // create affiliate order
                                $doCreateOrder = $internalAffService->createOrder($order, $oneVoucher->code);

                                // trigger to delete order in internal affiliate
                                $processedVouchers[] = $oneVoucher;

                                if ($doCreateOrder->messageCode == \Abstraction\Object\Message::SUCCESS) {

                                } else {
                                    $processVouchers = false;
                                    $errorVoucher = $doCreateOrder->message;
                                }
                            } else {
                                $doUtilize = $paymentService->utilizeVoucher(
                                    $oneVoucher->code,
                                    \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant->code,
                                    $order,
                                    $oneVoucher->partnerId
                                );
                                if ($doUtilize->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $processedVouchers[] = $oneVoucher;
                                } else {
                                    $processVouchers = false;
                                    $errorVoucher = $doUtilize->message;
                                    break;
                                }
                            }
                        }

                        if ($processVouchers) {
                            // save selected voucher to order
                            $order->update_meta_data(
                                'selected_vouchers',
                                $processedVouchers
                            );

                            // save processing clm vouchers
                            update_option('processing_clm_vouchers', $processingClmVouchers);

                            if ($paymentMethod == 'COD') {
                                $order->update_status("pending", '', true);
                                $returnData->requestPayment = null;

                                // calling new order
                                GDelivery\Libs\Helper\Call::makeAcall($order);

                                // send mail new order
                                GDelivery\Libs\Helper\Mail::send($order);
                            } else {
                                // other ways, wait for payment, other process will update order
                                $order->update_status("waiting-payment", "Chờ thanh toán {$paymentMethod}", true);

                                $order->update_meta_data('is_paid', 0);
                                $requestPayment = $paymentService->requestPayment(
                                    $paymentMethod,
                                    $totals->totalPaySum,
                                    \GDelivery\Libs\Helper\Helper::getSelectedRestaurant()->restaurant->regionName,
                                    $order
                                );

                                if ($requestPayment->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $order->update_meta_data('payment_request_id', $requestPayment->result['requestId']);
                                    $order->update_meta_data('payment_request_object',
                                        $requestPayment->result);
                                }
                                $returnData->requestPayment = $requestPayment;
                            }

                            // Save the order again for sure
                            $order->save();

                            // push data to report
                            $report = new \GDelivery\Libs\Helper\Report();
                            $report->saveOrder(
                                $order,
                                \GDelivery\Libs\Helper\Helper::getSelectedRestaurant(), $pickupAtRestaurant
                            );

                            // process internal affiliate if needed
                            if (
                                !$hasInternalAffiliateVoucher
                                && isset($_COOKIE['ggg_internal_affiliate'])
                                && $_COOKIE['ggg_internal_affiliate']
                            ) {
                                $internalAffService->createOrder(
                                    $order,
                                    $_COOKIE['ggg_internal_affiliate']
                                );
                            }

                            // todo process for Mas Offer if needed

                            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                            $res->message = 'Thành công';
                            $res->result = $returnData;

                            // clear after checkout
                            \GDelivery\Libs\Helper\Helper::clearDataAfterCheckout();
                        } else {
                            // cancel utilize voucher
                            foreach ($processedVouchers as $one) {
                                if ($one->partnerId == 14) {

                                } elseif ($one->partnerId == 999) {
                                    $internalAffService->updateOrderStatus($order, 'cancelled');
                                } else {
                                    $paymentService->cancelVoucher($one->code, $order);
                                }
                            }

                            $order->delete(true);

                            $res->messageCode
                                = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = $errorVoucher;
                        }
                    } else {
                        $res->messageCode
                            = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = 'Vui lòng chọn hình thức thanh toán.';
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Giỏ hàng đang trống, vui lòng chọn lại sản phẩm, hoặc vào Danh sách đơn hàng để tiếp tục.';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Vui lòng đăng nhập lại và thực hiện tiếp';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Cần truyền đầy đủ thông tin order';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    } // create order

} //end class

// init class
$checkoutAjax = new AjaxCheckout();
