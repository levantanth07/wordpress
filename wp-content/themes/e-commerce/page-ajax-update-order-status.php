<?php
/*
Template Name: Ajax Update Order Status
*/

use GGGMnASDK\Abstraction\Object\SMS as ObjectSMS;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

$res = new \Abstraction\Object\Result();
$tgsService = new \GDelivery\Libs\TGSService();
$bookingService = new \GDelivery\Libs\BookingService();
$paymentService = new \GDelivery\Libs\PaymentHubService();
$internalAffiliateService = new \GDelivery\Libs\InternalAffiliateService();
$masOfferService = new \GDelivery\Libs\MasOfferService();
$currentUser = wp_get_current_user();
$user = Permission::checkCurrentUserRole($currentUser);
$logger = new Logger('grab-status');
$logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/delivery-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
$logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
$serializer = new \Symfony\Component\Serializer\Serializer(
    [
        new ObjectNormalizer(),
        new JsonEncoder()
    ]
);

if (in_array($user->role, ['restaurant', 'operator', 'administrator', 'customer'])) {
    $orderId = $_REQUEST['id'] ?? null;
    $status = $_REQUEST['status'] ?? '';
    $requestNote = $_REQUEST['note'] ?? '';
    $restaurantCode = $_REQUEST['restaurant'] ?? 0;
    $extraData = $_REQUEST['extraData'] ?? [];
    $partner = $extraData['partner'] ?? '';
    $action = $_REQUEST['action'] ?? '';

    if ($orderId && $status) {
        $order = wc_get_order($orderId);

        if ($order) {

            $customerInfo = $order->get_meta('customer_info');
            $paymentMethod = $order->get_meta('payment_method');
            $orderTotal = \GDelivery\Libs\Helper\Helper::orderTotals($order);

            $paymentService = new \GDelivery\Libs\PaymentHubService();

            if (
                (
                    $user->role == 'customer' && $order->get_customer_id() == get_current_user_id() // just customer can update order himself
                )
                || in_array($user->role, ['restaurant', 'operator', 'administrator'])
            ) {
                $oldStatus = $order->get_status();
                $note = "{$currentUser->ID}||{$oldStatus}||{$status}||{$requestNote}__";
                $isCustomerNote = $user->role == 'customer' ? 1 : 0;
                $addByUser = $user->role != 'customer';

                // check status to process
                switch ($status) {
                    case 'completed' :
                        // in case change status to complete
                        $requestRkOrder = isset($_REQUEST['rkOrder']) ? $_REQUEST['rkOrder'] : [];

                        if ($requestRkOrder) {
                            $oldRkOrder = $order->get_meta('rkOrder');
                            $rkOrder = new \stdClass();

                            $rkOrder->guid = $oldRkOrder->guid;
                            $rkOrder->billNumber = $requestRkOrder['billNumber'];
                            $rkOrder->checkNumber = $requestRkOrder['checkNumber'];

                            // send transaction to MasOffer
                            if (isset($order->get_meta('mo_utm_data')->trafficId) && $order->get_meta('mo_utm_data')->trafficId != '') {
                                $responseMasOffer = $masOfferService->transaction($order, 1);
                                $masOffer = $order->get_meta('mo_utm_data');
                                $utmMasOffer = new \stdClass();
                                $utmMasOffer->trafficId = $masOffer->trafficId;
                                $utmMasOffer->utmSource = $masOffer->utmSource;
                                if ($responseMasOffer->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $utmMasOffer->isSuccess = 2;
                                } else {
                                    $utmMasOffer->isSuccess = 3;
                                    $utmMasOffer->message = $responseMasOffer->message;
                                }
                                $order->update_meta_data('mo_utm_data', $utmMasOffer);
                            }

                            // update order in internal affiliate
                            $internalAffiliateId = $order->get_meta('ggg_internal_affiliate');
                            if ($internalAffiliateId) {
                                $internalAffiliateService->updateOrderStatus($order, 'completed');
                            }

                            // status
                            $order->update_meta_data('rkOrder', $rkOrder);
                            $order->update_status($status, $note); // set new status
                            $order->update_meta_data('completed_time', date_i18n('Y-m-d H:i:s'));

                            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                            $histories[] = [
                                'status' => $status,
                                'statusText' => 'Hoàn thành',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);

                            $order->save();

                            $orderService = new \GDelivery\Libs\Helper\Order();
                            $orderService->sendNotify($order->get_id(), $status);

                            //$report = new \GDelivery\Libs\Helper\Report();
                            //$report->updateOrder($order);

                            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order;
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Cần nhập đẩy đủ thông tin order trên POS';
                        }
                        break;
                    case 'cancelled' :
                        // release amount if pay with BizAccount
                        if (
                            $oldStatus != 'cancelled'
                            && $paymentMethod == \GDelivery\Libs\Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME
                        ) {
                            $orderTotal = \GDelivery\Libs\Helper\Helper::orderTotals($order);
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
                            $beService = new \GDelivery\Libs\GBackendService();
                            foreach ($selectedVouchers as $one) {
                                $paymentService->cancelVoucher($one->code, $order);
                                $beService->cancelUtilize($one->code);
                            }
                        }

                        // operator note
                        if ($user->role == 'operator') {
                            $order->update_meta_data('operator_note', $requestNote);
                        } elseif ($user->role == 'customer') {
                            $order->update_meta_data('customer_note', $requestNote.' Khách hàng hủy đơn!');
                        }

                        // status
                        $order->update_status($status, $note); // set new status
                        $order->update_meta_data('cancelled_time', date_i18n('Y-m-d H:i:s'));

                        if ($order->get_meta('decrease_stock')) {
                            $products = [];
                            foreach ($order->get_items() as $oneItem) {
                                $currentProductId = (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) ? $oneItem->get_data()['variation_id'] : $oneItem->get_data()['product_id'];
                                $products[] = [
                                    'id' => $currentProductId,
                                    'quantity' => $oneItem->get_quantity(),
                                ];
                            }
                            $inventoryService = new \GDelivery\Libs\InventoryService();
                            $doIncreaseStock = $inventoryService->increaseStock($products);
                            if ($doIncreaseStock->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                $order->delete_meta_data('decrease_stock');
                            }
                        }

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
                        // report
                        //$report = new \GDelivery\Libs\Helper\Report();
                        //$report->updateOrder($order);

                        // update cancelled internal affiliate order
                        if ($order->get_meta('ggg_internal_affiliate')) {
                            $internalAffiliateService->updateOrderStatus($order, 'cancelled');
                        }

                        // todo change for MasOffer

                        // send transaction to MasOffer
                        if (isset($order->get_meta('mo_utm_data')->trafficId) && $order->get_meta('mo_utm_data')->trafficId != '') {
                            $responseMasOffer = $masOfferService->transaction($order, -1);
                            $masOffer = $order->get_meta('mo_utm_data');
                            $utmMasOffer = new \stdClass();
                            $utmMasOffer->trafficId = $masOffer->trafficId;
                            $utmMasOffer->utmSource = $masOffer->utmSource;
                            if ($responseMasOffer->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                $utmMasOffer->isSuccess = 2;
                            } else {
                                $utmMasOffer->isSuccess = 3;
                                $utmMasOffer->utmSource = $responseMasOffer->message;
                            }
                            $order->update_meta_data('mo_utm_data', $utmMasOffer);
                            $order->save();
                        }

                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = 'Đã cập nhật thông tin đơn hàng';
                        $res->result = $order;

                        break;
                    case 'trans-requested' :
                    case 'confirmed' :
                        ///////////////////////////////////////////////////////////
                        /// for case request vendor to transport
                        /// //////////////////////////////////////////////////////
                        if ($partner == 'grab_express') {
                            $grabExpress = new \GDelivery\Libs\Helper\GrabExpress();
                            $grabResult = $grabExpress->pushOrder($order);
                            if ($grabResult->messageCode == \Abstraction\Object\Message::GENERAL_ERROR) {
                                $res->messageCode = $grabResult->messageCode;
                                $res->message = $grabResult->message;
                                \GDelivery\Libs\Helper\Response::returnJson($res);
                                die;
                            }
                            $requestDeliveryData = $grabResult->result;
                            $order->update_meta_data('actual_shipping_fee', $requestDeliveryData->getQuote()->getAmount());
                            $mappingStatus = \GDelivery\Libs\Helper\GrabExpress::mappingStatus($requestDeliveryData->getStatus());
                            $needUpdateStatus = [
                                \GDelivery\Libs\Helper\Order::STATUS_TRANS_ALLOCATING,
                                \GDelivery\Libs\Helper\Order::STATUS_PROCESSING,
                                \GDelivery\Libs\Helper\Order::STATUS_CONFIRMED
                            ];
                            if (in_array($mappingStatus, $needUpdateStatus)) {
                                // save status
                                $order->update_status($mappingStatus, $note); // set new status
                                $order->update_meta_data('vendor_transport', 'grab_express');
                                $order->update_meta_data('restaurant-request-cancel-grab', 0);
                                $order->update_meta_data('grab_delivery_object', $requestDeliveryData);
                                $order->update_meta_data('trans_requested_time', date_i18n('Y-m-d H:i:s'));
                                $order->save();
                                // save report
                                $report = new \GDelivery\Libs\Helper\Report();
                                $report->updateOrder($order);
                                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                                $res->message = 'Đã cập nhật thông tin đơn hàng';
                                $res->result = $order;
                            } else {
                                $deliveryStatus = $requestDeliveryData->getStatus();
                                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                                $res->message = "Trạng thái chưa hỗ trợ ($deliveryStatus)";
                            }
                        } elseif($partner == 'golden_gate') {
                            $order->update_meta_data('vendor_transport', 'golden_gate');
                            goto defaultHandle;
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = "Chưa hỗ trợ vận chuyển: $partner";
                        }
                        break;
                    case 'trans-cancel' :
                        if ($partner == 'grab_express') {
                            $grabExpress = new \GDelivery\Libs\Helper\GrabExpress();
                            $grabResult = $grabExpress->cancelOrder($order);
                            if ($grabResult->messageCode == \Abstraction\Object\Message::SUCCESS) {                    
                                $order->update_status(\GDelivery\Libs\Helper\Order::STATUS_PENDING, 'Hủy vận chuyển Grab');
                                $order->delete_meta_data('vendor_transport');
                                $order->save();
                            }
                            $res->messageCode = $grabResult->messageCode;
                            $res->message = $grabResult->message;
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = "Chưa hỗ trợ vận chuyển: $partner";
                        }
                        break;
                    case 'request-support':

                        $selectedVouchers = $order->get_meta('selected_vouchers');
                        $rkCodeDiscounts = [];
                        $productIdDiscounts = [];
                        $isValidVoucher = false;
                        if ($selectedVouchers) {
                            foreach ($selectedVouchers as $voucher) {
                                $listProductChose = isset($voucher->listProductDiscountChosen) ? array_column($voucher->listProductDiscountChosen, 'productId') : false;
                                if (
                                    (
                                        $voucher->type == \GDelivery\Libs\Helper\Voucher::TYPE_DISCOUNT_PERCENT_ON_ITEM
                                        || $voucher->type == \GDelivery\Libs\Helper\Voucher::TYPE_DISCOUNT_CASH_ON_ITEM
                                    )
                                    && isset($voucher->selectedForRkItem)
                                    && count($voucher->selectedForRkItem) == 1
                                ) {
                                    $rkCodeDiscounts = array_merge($rkCodeDiscounts, $voucher->applyForRkItemCodes);
                                    $isValidVoucher = true;
                                } elseif (
                                    (
                                        $voucher->type == \GDelivery\Libs\Helper\Voucher::TYPE_DISCOUNT_PERCENT_ON_ITEM
                                        || $voucher->type == \GDelivery\Libs\Helper\Voucher::TYPE_DISCOUNT_CASH_ON_ITEM
                                    )
                                    && $listProductChose
                                ) {
                                    foreach ($voucher->listProductDiscountChosen as $productChosen) {
                                        $rkCodeDiscounts = array_merge($rkCodeDiscounts, [$productChosen['rkCode']]);
                                    }
                                }
                            }
                        }

                        // Check total price
                        $totalInstead = 0;
                        $totalCurrent = 0;
                        $isValid = true;
                        $rkCodes = [];
                        foreach ($order->get_items() as $oneItem) {
                            $lineItemId = $oneItem->get_meta('lineItemId');
                            $productId = $oneItem->get_meta("instead_of_product_id_{$lineItemId}");
                            $quantity = $oneItem->get_meta("instead_of_quantity_{$lineItemId}") ? $oneItem->get_meta("instead_of_quantity_{$lineItemId}") : 1;
                            $productInstead = "";
                            if ($productId) {
                                $getProduct = \GDelivery\Libs\Helper\Product::getDetailProduct($productId);
                                if ($getProduct->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $productInstead = $getProduct->result;
                                    $totalInstead += ($productInstead->salePrice ?: $productInstead->regularPrice)*$quantity;
                                } else {
                                    $isValid = false;
                                }
                                $totalCurrent += $oneItem->get_total();
                                if (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) {
                                    $rkCodes[] = get_field('product_variation_rk_code', $oneItem->get_data()['variation_id']);
                                } else {
                                    $rkCodes[] = get_field('product_rk_code', $oneItem->get_data()['product_id']);
                                }
                            }

                            $insteadToppings = $oneItem->get_meta("instead_of_topping_id_{$lineItemId}");
                            if ($insteadToppings) {
                                foreach ($insteadToppings as $topping) {
                                    $getTopping = \GDelivery\Libs\Helper\Product::getDetailProduct($topping);
                                    if ($getTopping->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                        $toppingInstead = $getTopping->result;
                                        $totalInstead += ($toppingInstead->salePrice ?: $toppingInstead->regularPrice)*$quantity;
                                    } else {
                                        $isValid = false;
                                    }
                                }
                            }
                            if ($lineItemId == $oneItem->get_meta('parentLineItemId')) {
                                $totalCurrent += $oneItem->get_total();
                                $rkCodes[] = get_field('product_rk_code', $oneItem->get_data()['product_id']);
                            }
                        }

                        if ($totalCurrent <= $totalInstead) {
                            if ($isValidVoucher && array_intersect($rkCodes, $rkCodeDiscounts)) {
                                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                                $res->message = 'Sản phẩm thay thế không hợp lệ!';
                            } else {
                                // status
                                //$order->delete_meta_data('request_change_product');
                                $order->update_meta_data('request_change_product', 'request_submitted');
                                $order->update_status($status, $note); // set new status
                                $order->update_meta_data('request_support_time', date_i18n('Y-m-d H:i:s'));
                                $order->save();

                                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                                $res->message = 'Đã cập nhật thông tin đơn hàng';
                                $res->result = $order;
                            }
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Số tiền của sản phẩm thay thế không hợp lệ';
                        }

                        break;
                    default:
                        defaultHandle:
                        ///////////////////////////////////////////////////////////
                        /// for case only update order status
                        /// //////////////////////////////////////////////////////

                        // general note
                        $order->add_order_note($note, $isCustomerNote, $addByUser);

                        // other note
                        if ($user->role == 'operator') {
                            $order->update_meta_data('operator_note', $requestNote);
                        } elseif ($user->role == 'restaurant') {
                            $order->update_meta_data('restaurant_note', $requestNote);
                        } else {
                            
                        }

                        // status
                        $order->update_status($status, $note); // set new status
                        if ($status == \GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING) {
                            $order->update_meta_data('transport_on_going_time', date_i18n('Y-m-d H:i:s'));
                        }

                        ///////////////////////////////////////////////////////////
                        // process transfer order
                        /////////////////////////////////////////////////////////
                        if ($restaurantCode && $restaurantCode != $order->get_meta('restaurant_code')) {
                            $getRestaurant = $bookingService->getRestaurant($restaurantCode);
                            if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                // check restaurant in G-Delivery
                                $getBachMaiRestaurant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($restaurantCode);
                                if ($getBachMaiRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $bachMaiRestaurant = $getBachMaiRestaurant->result;
                                    $bachMaiRestaurant->restaurant = $getRestaurant->result;

                                    $restaurantHistories = $order->get_meta('restaurant_histories');
                                    if (!$restaurantHistories) {
                                        $restaurantHistories = [];
                                    }
                                    $restaurantHistories[] = [
                                        'time' => time(),
                                        'restaurant' => $bachMaiRestaurant
                                    ];

                                    // save histories
                                    $order->update_meta_data('restaurant_histories', $restaurantHistories);

                                    // update new restaurant
                                    $order->update_meta_data('restaurant_code', $restaurantCode);
                                    $order->update_meta_data('restaurant_object', $getRestaurant->result);
                                    $order->update_meta_data('restaurant_in_bachmai', $bachMaiRestaurant);
                                    $order->update_meta_data('transfer_restaurant_time', date_i18n('Y-m-d H:i:s'));

                                    // recalculate shipping fee
                                    $categoryId = $order->get_meta('current_product_category_id');
                                    $brandId = get_field('product_category_brand_id', 'product_cat_' . $categoryId);
                                    $calculateShippingFee = \GDelivery\Libs\Helper\Helper::calculateShippingFee(
                                        $order->get_meta('customer_selected_address'),
                                        $getRestaurant->result,
                                        [
                                            'brandId' => $brandId
                                        ]
                                    );
                                    if ($calculateShippingFee->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                        $order->update_meta_data('actual_shipping_fee', $calculateShippingFee->result->total);
                                    }

                                    // reset is_created_rk_order
                                    $order->update_meta_data('is_created_rk_order', 0);

                                    // process selected voucher
                                    $selectedVouchers = $order->get_meta('selected_vouchers');
                                    // cancel utilize voucher
                                    foreach ($selectedVouchers as $one) {
                                        $paymentService->cancelVoucher($one->code, $order);
                                    }
                                    // re-utilize
                                    foreach ($selectedVouchers as $oneVoucher) {
                                        $paymentService->utilizeVoucher(
                                            $oneVoucher->code,
                                            $order->get_meta('restaurant_code'),
                                            $order
                                        );
                                    }
                                }
                            }
                        }

                        if ($status == \GDelivery\Libs\Helper\Order::STATUS_PROCESSING) {
                            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                            $histories[] = [
                                'status' => $status,
                                'statusText' => 'Nhà hàng đang chuẩn bị',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);
                            if ($partner == 'golden_gate') {
                                $order->update_meta_data('vendor_transport', 'golden_gate');
                            }
                        } elseif (
                            $status == \GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING
                        ) {
                            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                            $histories[] = [
                                'status' => $status,
                                'statusText' => 'Đang giao hàng',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);
                            // $order->update_meta_data('vendor_transport', 'golden_gate');
                        } elseif (
                            $status == \GDelivery\Libs\Helper\Order::STATUS_READY_TO_PICKUP
                        ) {
                            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                            $histories[] = [
                                'status' => $status,
                                'statusText' => 'Nhà hàng đã chuẩn bị xong',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);
                        } elseif (
                            $status == \GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT
                        ) {
                            $histories = $order->get_meta('order_status_histories') ? $order->get_meta('order_status_histories') : [];
                            $histories[] = [
                                'status' => $status,
                                'statusText' => 'Khách hàng hủy đơn',
                                'createdAt' => date_i18n('Y-m-d H:i:s')
                            ];
                            $order->update_meta_data('order_status_histories', $histories);
                        }

                        if ($status == \GDelivery\Libs\Helper\Order::STATUS_PENDING) {
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
                                if ($doDecreaseStock->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $order->update_meta_data('decrease_stock', 1);
                                }
                            }
                        }

                        if ($order->save()) {
                            //$report = new \GDelivery\Libs\Helper\Report();
                            //$report->updateOrder($order);

                            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                            $res->message = 'Đã cập nhật thông tin đơn hàng';
                            $res->result = $order;
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = 'Lỗi khi cập nhật thông tin đơn hàng';
                        }
                }

                // only admin using this function with operator UI
                // in case change from cancel to pending and order with BizAccount
                // manual hold amount
                if (
                    $status != 'cancelled'
                    && $oldStatus == 'cancelled'
                    && $paymentMethod == \GDelivery\Libs\Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME
                ) {
                    // hold amount if pay with BizAccount
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

            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Be Honest, play fair!';
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