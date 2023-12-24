<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\GBackendService;
use GDelivery\Libs\TgsServiceV2;

class Order {
    // const Action
    const ACTION_CONFIRM = 'confirm';
    const ACTION_NEED_TO_TRANSFER = 'needToTransfer';
    const ACTION_READY_TO_PICKUP = 'readyToPickUp';
    const ACTION_RESTAURANT_TRANSPORT = 'restaurantTransport';
    const ACTION_VENDOR_TRANSPORT = 'vendorTransport';
    const ACTION_CANCEL_VENDOR_TRANSPORT = 'cancelVendorTransport';
    const ACTION_VENDOR_SCHEDULE_TRANSPORT = 'vendorScheduleTransport';
    const ACTION_CANCEL = 'cancel';
    const ACTION_NEED_TO_CANCEL = 'needToCancel';
    const ACTION_COMPLETE = 'complete';
    const ACTION_CHANGE_RESTAURANT = 'changeRestaurant';
    const ACTION_REFUNDED = 'refund';
    const ACTION_REQUEST_SUPPORT = 'requestSupport';

    // status
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_PROCESSING = 'processing';

    const STATUS_NEED_TO_TRANSFER = 'need-to-transfer';
    const STATUS_WAITING_PAYMENT = 'waiting-payment';

    const STATUS_TRANS_REQUESTED = 'trans-requested';
    const STATUS_TRANS_ACCEPTED = 'trans-accepted';
    const STATUS_TRANS_CANCEL = 'trans-cancel';

    const STATUS_READY_TO_PICKUP = 'ready-to-pickup';
    const STATUS_TRANS_GOING = 'trans-going';
    const STATUS_TRANS_DELIVERED = 'trans-delivered';
    const STATUS_TRANS_RETURNED = 'trans-returned';
    const STATUS_TRANS_REJECTED = 'trans-rejected';
    const STATUS_TRANS_ALLOCATING = 'trans-allocating';
    const STATUS_REQUEST_SUPPORT = 'request-support';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CUSTOMER_REJECT = 'customer-reject';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NEED_TO_CANCEL = 'need-to-cancel';

    const STATUS_TRANS_COMING_PICK_UP = 'coming-pick-up';

    //const Action Grab
    const ACTION_TRANSPORT_ALLOCATING = 'actionTransportAllocating';
    const ACTION_TRANSPORT_PICKING_UP = 'actionTransportPickingUp';
    const ACTION_TRANSPORT_IN_DELIVERY = 'actionTransportInDelivery';
    const ACTION_TRANSPORT_IN_RETURN = 'actionTransportInReturn';
    const ACTION_TRANSPORT_COMPLETED = 'actionTransportCompleted';
    const ACTION_TRANSPORT_CANCELED = 'actionTransportCanceled';
    const ACTION_TRANSPORT_FAILED = 'actionTransportFailed';
    const ACTION_TRANSPORT_RETURNED = 'actionTransportReturned';

    public static $orderStatusForCustomer = [
        self::STATUS_PENDING => 'Chờ xác nhận',
        self::STATUS_PROCESSING => 'Xác nhận',
        self::STATUS_READY_TO_PICKUP => 'Đã sẳn sàng để nhận',
        self::STATUS_WAITING_PAYMENT => 'Chờ thanh toán',
        self::STATUS_TRANS_GOING => 'Tài xế đang giao hàng',
        self::STATUS_TRANS_ALLOCATING => 'Đang tìm tài xê',
        self::STATUS_COMPLETED => 'Thành công',
        self::STATUS_CANCELLED => 'Đã hủy',
        self::STATUS_REFUNDED => 'Đã hoàn tiền',
        self::STATUS_REQUEST_SUPPORT => 'Yêu cầu hỗ trợ',
        self::STATUS_TRANS_REJECTED => 'Không tìm thấy tài xế',
        self::STATUS_TRANS_COMING_PICK_UP => 'Tài xế đang đến nhận hàng',
        self::STATUS_CUSTOMER_REJECT => 'Đã hủy',
    ];

    public static $arrayStatus = [
        self::STATUS_WAITING_PAYMENT => 'Chờ thanh toán',
        self::STATUS_PENDING => 'Chờ xác nhận',
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_REQUEST_SUPPORT => 'Yêu cầu hỗ trợ',
        self::STATUS_TRANS_ALLOCATING => 'Đang tìm tài xế',
        self::STATUS_TRANS_DELIVERED => 'Đã giao hàng',
        self::STATUS_PROCESSING => 'Nhà hàng đang chuẩn bị',
        self::STATUS_READY_TO_PICKUP => 'Nhà hàng đã chuẩn bị xong',
        self::STATUS_TRANS_REJECTED => 'Không tìm thấy tài xế',
        self::STATUS_TRANS_GOING => 'Đang giao hàng',
        self::STATUS_TRANS_CANCEL => 'Hủy tìm tài xế',
        self::STATUS_CUSTOMER_REJECT => 'Khách hàng không nhận đơn',
        self::STATUS_COMPLETED => 'Hoàn thành',
        self::STATUS_CANCELLED => 'Đã hủy',
        self::STATUS_REFUNDED => 'Đã hoàn tiền',
    ];

    // For CMS
    const TAB_WAITING_PAYMENT = 'waiting-payment';
    const TAB_PENDING = 'pending';
    const TAB_CONFIRMED = 'confirmed';
    const TAB_REQUEST_SUPPORT = 'request-support';
    const TAB_PROCESSING = 'processing';
    const TAB_RESTAURANT_READY = 'restaurant-complete';
    const TAB_TRANS_GOING = 'trans-going';
    const TAB_TRANS_DELIVERED = 'trans-delivered';
    const TAB_COMPLETED = 'completed';
    const TAB_CANCELLED = 'cancelled';
    const TAB_REFUNDED = 'refunded';

    public static $arrayTabsOperator = [
        self::TAB_REQUEST_SUPPORT => 'Yêu cầu hỗ trợ',
        self::TAB_WAITING_PAYMENT => 'Chờ thanh toán',
        self::TAB_PENDING => 'Chờ xác nhận',
        self::TAB_CONFIRMED => 'Đã xác nhận',
        self::TAB_PROCESSING => 'Nhà hàng đang chuẩn bị',
        self::TAB_RESTAURANT_READY => 'Nhà hàng đã chuẩn bị xong',
        self::TAB_TRANS_GOING => 'Giao hàng',
        self::TAB_COMPLETED => 'Hoàn thành',
        self::TAB_CANCELLED => 'Đã hủy',
        self::TAB_REFUNDED => 'Đã hoàn tiền',
    ];
    public static $restaurantTabs = [
        self::TAB_PENDING => 'Chờ xác nhận',
        self::TAB_CONFIRMED => 'Đã xác nhận',
        self::TAB_PROCESSING => 'Nhà hàng đang chuẩn bị',
        self::TAB_RESTAURANT_READY => 'Nhà hàng đã chuẩn bị xong',
        self::TAB_REQUEST_SUPPORT => 'Yêu cầu hỗ trợ',
        self::TAB_TRANS_GOING => 'Giao hàng',
        self::TAB_TRANS_DELIVERED => 'Đã giao hàng',
        self::TAB_COMPLETED => 'Hoàn thành',
        self::TAB_CANCELLED => 'Đã hủy',
        self::TAB_REFUNDED => 'Đã hoàn tiền',
    ];


    public static function orderStatusName($status = null)
    {
        $statuses = self::$arrayStatus;

        if ($status == 'all') {
            return $statuses;
        } elseif (isset($statuses[$status])) {
            return $statuses[$status];
        } else if($status === null) {
            return $statuses;
        } else {
            return 'Chưa xác định';
        }
    }

    /**
     * @param \WP_Term $currentCategory
     * @param string $time int format H:i
     *
     * @return array
     * @throws \Exception
     */
    public static function allowBlockTimesToOrder($restaurant, $time = '')
    {
        // calculate order time
        $miniTimeToServe = $restaurant->minimumTimeToServe ?: 3600; //(int) get_field('product_category_minimum_time_to_serve', 'product_cat_'.$currentCategory->term_id);
        $offsetMiniTimeToServe = ceil($miniTimeToServe/900); // each block is  15 minutes; calculate and round up

        $today = new \DateTime(date_i18n('Y-m-d')); // 2017-04-01 00:00:00
        $allTimes = [];
        $allTimes[] = $today->format('Y-m-d H:i:s'); //add the 00:00 time before looping
        $index = 0;
        for ($i = 0; $i <= 95; $i ++){ // 95 loops will give you everything from 00:00 to 23:45
            $allTimes[$index] = $today->format('H:i').' - '.$today->modify('+15 minutes')->format('H:i');
            $index++;
        }
        $openTime1 = $restaurant->merchantOpenTime1 ?: '09:00';
        $blockOpenTime1 = (((int) substr($openTime1, 0, 2)) * 4) + (int)(((int) substr($openTime1, 3, 2))/15) + $offsetMiniTimeToServe;
        $closeTime1 = $restaurant->merchantCloseTime1 ?: '22:00';
        $blockCloseTime1 = (((int) substr($closeTime1, 0, 2)) * 4) + ceil(((int) substr($closeTime1, 3, 2))/15);

        $validRangeTime = [];
        foreach ($allTimes as $index => $block) {
            if ($index >= $blockOpenTime1 && $index < $blockCloseTime1) {
                $validRangeTime[$index] = $block;
            }
        }

        $openTime2 = $restaurant->merchantOpenTime2 ?: '';
        $closeTime2 = $restaurant->merchantCloseTime2 ?: '';
        if ($openTime2 && $closeTime2) {
            $blockOpenTime2 = (((int) substr($openTime2, 0, 2)) * 4) + (int)(((int) substr($openTime2, 3, 2))/15) + $offsetMiniTimeToServe; // sau giờ mở cửa 30' nên cần + 2 block ở cuối
            $blockCloseTime2 = (((int) substr($closeTime2, 0, 2)) * 4) + ceil(((int) substr($closeTime2, 3, 2))/15); // trước giờ đóng cửa 30' nên cần 0 - 2 block ở cuối
            foreach ($allTimes as $index => $block) {
                if ($index >= $blockOpenTime2 && $index < $blockCloseTime2) {
                    if (!isset($validRangeTime[$index])) {
                        $validRangeTime[$index] = $block;
                    }
                }
            }
        }

        // start time
        if (!$time) {
            $time = date_i18n('H:i');
        }

        $hours = (int) substr($time, 0, 2);
        $minutes = (int) substr($time, 3, 2);
        $start = ($hours * 4) + ceil($minutes * 60/900) + $offsetMiniTimeToServe;

        $temp = [];
        foreach ($validRangeTime as $index => $block) {
            if ($index >= $start) {
                $temp[] = $block;
            }
        }

        return $temp;
    }

    /**
     * Calculate total tax of order
     *
     * @param \WC_Order $order
     * @param float $discount
     * @return float Tax total.
     */
    public static function totalTax($order, $discount)
    {
        $totalTax = 0;
        foreach ($order->get_items() as $item) {
            // Get total price and discount of product.
            $totalPrice = $item->get_subtotal() - $discount;

            if ($totalPrice <= 0) {
                $discount = 0 - $totalPrice; // Discount remaining
                continue;
            }

            $discount = 0;
            // Get tax of product.
            $product = $item->get_product();
            $productId = $product->get_id();
            $productInfo = wc_get_product($productId);
            if ($productInfo->is_type('variation')) {
                $productId = $productInfo->get_parent_id();
            }
            $tax = Product::getTaxRateValue($productId);

            $totalTax += $totalPrice * $tax;
        }

        return $totalTax;
    }

    public static function allowActionWithStatus($action, $status)
    {
        $confirm = [
            Order::STATUS_PENDING, Order::STATUS_NEED_TO_TRANSFER, Order::STATUS_NEED_TO_CANCEL
        ];

        $cancel = [
            //'pending', 'need-to-transfer', 'processing', 'trans-requested', 'trans-accepted', 'trans-going', 'trans-return'
            Order::STATUS_NEED_TO_CANCEL
        ];

        $needToCancel = [
            Order::STATUS_PENDING, Order::STATUS_NEED_TO_TRANSFER, Order::STATUS_PROCESSING, Order::STATUS_TRANS_REQUESTED, Order::STATUS_TRANS_ACCEPTED, Order::STATUS_TRANS_GOING, Order::STATUS_TRANS_RETURNED, Order::STATUS_NEED_TO_CANCEL, Order::STATUS_TRANS_REJECTED
        ];

        $cancelVendorTransport = [
            Order::STATUS_TRANS_ALLOCATING, Order::STATUS_TRANS_REQUESTED, Order::STATUS_TRANS_ACCEPTED
        ];

        $needToTransfer = [
            Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_NEED_TO_TRANSFER
        ];

        $restaurantTransport = [
            Order::STATUS_PROCESSING, Order::STATUS_TRANS_REJECTED
        ];

        $vendorTransport = [
            Order::STATUS_PROCESSING, Order::STATUS_TRANS_REQUESTED, Order::STATUS_TRANS_REJECTED
        ];

        $complete = [
            Order::STATUS_TRANS_GOING, Order::STATUS_TRANS_DELIVERED
        ];

        switch ($action) {
            case Order::ACTION_CONFIRM :
                return in_array($status, $confirm);
                break;
            case Order::ACTION_CANCEL :
                return in_array($status, $cancel);
                break;
            case Order::ACTION_NEED_TO_CANCEL :
                return in_array($status, $needToCancel);
                break;
            case Order::ACTION_CANCEL_VENDOR_TRANSPORT :
                return in_array($status, $cancelVendorTransport);
                break;
            case Order::ACTION_NEED_TO_TRANSFER :
                return in_array($status, $needToTransfer);
                break;
            case Order::ACTION_RESTAURANT_TRANSPORT :
                return in_array($status, $restaurantTransport);
                break;
            case Order::ACTION_VENDOR_TRANSPORT :
                return in_array($status, $vendorTransport);
                break;
            case Order::ACTION_COMPLETE :
                return in_array($status, $complete);
            default :
                return false;
        }
    }

    public static function getListQuantityOfProductOrder($listOrders)
    {
        $arrQuantity = [];
        foreach ($listOrders as $order) {
            foreach ($order->get_items() as $item) {
                $itemData = $item->get_data();
                if ($itemData['variation_id'] != 0) {
                    $itemProductId = $itemData['variation_id'];
                } else {
                    $itemProductId = $itemData['product_id'];
                }
                if (!isset($arrQuantity[$itemProductId])) {
                    $arrQuantity[$itemProductId] = 0;
                }
                $arrQuantity[$itemProductId] += (int) $itemData['quantity'];
            }
        }

        return $arrQuantity;
    }

    /**
     * @param $category
     * @param $time
     * @return array
     */
    public static function checkTimeOrder($restaurant, $date, $time)
    {
        $date = gmdate('Y-m-d', strtotime(str_replace('/', '-', $date)));

        if ($date > date_i18n('Y-m-d')) {
            $listTimeValid = self::allowBlockTimesToOrder(
                $restaurant,
                '00:00'
            );
        } else {
            $listTimeValid = self::allowBlockTimesToOrder($restaurant);
        }

        if ($date < date_i18n('Y-m-d')) {
            return [
                'isValid' => false,
                'validTimes' => $listTimeValid,
                'validDate' => date_i18n('Y-m-d')
            ];
        }

        $count = count($listTimeValid);

        $minTime = explode('-', $listTimeValid[0]);
        $startMinTime = strtotime($minTime[0]);
        $endMinTime = strtotime($minTime[1]);

        $maxTime = explode('-', $listTimeValid[$count - 1]);
        $startMaxTime = strtotime($maxTime[0]);
        $endMaxTime = strtotime($maxTime[1]);

        $selectTime = explode('-', $time);
        $startTime = strtotime($selectTime[0]);
        $endTime = strtotime($selectTime[1]);

        return [
            'isValid' => $startTime >= $startMinTime && $endTime >= $endMinTime &&
                $startMaxTime >= $startTime && $endMaxTime >= $endTime,
            'validTimes' => $listTimeValid
        ];
    }

    public static function sendNotify($id, $status)
    {
        $res = new Result();
        $getNotify = self::prepareSendNotifyOrder($id, $status);
        if ($getNotify->messageCode == Message::SUCCESS) {
            $tgsServiceV2 = new TgsServiceV2();
            $doSend = $tgsServiceV2->sendNotify($getNotify->result);
            if ($doSend->messageCode == 200) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'success';
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $doSend->message;
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = $getNotify->message;
        }
        return $res;
    }

    public static function prepareSendNotifyOrder($id, $status)
    {
        $res = new Result();
        $order = wc_get_order($id);
        if ($order) {
            $customer = $order->get_meta('customer_info');
            if ($customer->customerNumber) {
                if (in_array($status, [
                    self::STATUS_COMPLETED,
                    self::STATUS_READY_TO_PICKUP,
                    self::STATUS_CANCELLED
                ])) {
                    switch ($status) {
                        case self::STATUS_COMPLETED;
                            $title = "Đơn hàng #{$id} đã hoàn thành";
                            $message = "Đơn hàng #{$id} đã hoàn thành";
                            break;
                        case self::STATUS_READY_TO_PICKUP;
                            $title = "Đơn hàng #{$id} đã sẵn sàng để nhận";
                            $message = "Đơn hàng #{$id} đã sẵn sàng để nhận";
                            break;
                        case self::STATUS_CANCELLED;
                            $title = "Đơn hàng #{$id} đã bị hủy";
                            $message = "Đơn hàng #{$id} đã bị hủy vì lý do không tìm thấy tài xế giao hàng";
                            break;
                        default:
                            $title = "";
                            $message = "";
                    }
                    $res->result = [
                        "title" => $title,
                        "message" => $message,
                        "customerNumbers" => [
                            $customer->customerNumber
                        ],
                        "deeplink" => "tgsuat://order?orderId={$id}"
                    ];
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'success';
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Trạng thái không hợp lệ!';
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Không tìm thấy thông tin khách hàng!';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không tìm thấy thông tin order!';
        }
        return $res;
    }

    public static function prepareSendNotifyOrderV1($id, $status)
    {
        $res = new Result();
        $order = wc_get_order($id);
        if ($order) {
            $customer = $order->get_meta('customer_info');
            if ($customer->customerNumber) {
                if (in_array($status, [
                    self::STATUS_COMPLETED,
                ])) {
                    switch ($status) {
                        case self::STATUS_COMPLETED;
                            $title = "Đơn hàng #{$id} đã hoàn thành";
                            $message = "Đơn hàng #{$id} đã hoàn thành";
                            break;
                        default:
                            $title = "";
                            $message = "";
                    }
                    $res->result = [
                        "customerNumber" => $customer->customerNumber,
                        "title" => $title,
                        "message" => $message,
                        "icon" => "",
                        "sceneObjectId" => $id,
                        "scene" => 6,
                        "sendToOs" => true
                    ];
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'success';
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Trạng thái không hợp lệ!';
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Không tìm thấy thông tin khách hàng!';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không tìm thấy thông tin order!';
        }
        return $res;
    }

    public static function getInsteadTotal($order)
    {
        $insteadValid = true;
        $insteadValidMessage = '';
        $selectedVouchers = $order->get_meta('selected_vouchers');
        $items = [];
        $newItems = [];
        $lineItemIds = [];

        $newTotalPrice = 0;
        $newTotalTax = 0;
        $newTotalPaySum = 0;

        foreach ($order->get_items() as $oneItem) {
            $newItem = new \stdClass();
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
                $newItem->quantity = (int) $oneItem->get_quantity();
                $newItem->name = $oneItem->get_name();
                $newItem->salePrice = (double) $oneItem->get_meta('salePrice');
                $newItem->regularPrice = (double) $oneItem->get_meta('regularPrice');
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

                        $newItem = new \stdClass();
                        $newItem->productId = $toppingInstead->wooId;
                        $quantity = $oneItem->get_meta("instead_of_quantity_{$lineItemId}");
                        $newItem->quantity = $quantity;
                        $newItem->name = $toppingInstead->name;
                        $newItem->salePrice = $toppingInstead->salePrice;
                        $newItem->regularPrice = $toppingInstead->regularPrice;
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
        }

        $total = new \stdClass();
        $total->newTotalPrice = $newTotalPrice;
        $total->newTotalTax = $newTotalTax;
        $total->newTotalPaySum = $newTotalPaySum;
        return $total;
    }
}