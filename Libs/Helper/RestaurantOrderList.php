<?php
namespace GDelivery\Libs\Helper;

use stdClass;

class RestaurantOrderList {

    const DEFAULT_STATUS_CLASS = 'alert-warning';

    public $statusToClass = [
        Order::STATUS_PROCESSING => 'alert-success',
        Order::STATUS_TRANS_GOING => 'alert-success',
        Order::STATUS_TRANS_REQUESTED => 'alert-success',

        Order::STATUS_NEED_TO_TRANSFER => 'alert-danger',
        Order::STATUS_CANCELLED => 'alert-danger',
        Order::STATUS_TRANS_REJECTED => 'alert-danger',
        Order::STATUS_CUSTOMER_REJECT => 'alert-danger',

        Order::STATUS_COMPLETED => 'alert-info',
    ];

    public static $tabStatus = [
        Order::TAB_PENDING => [
            Order::STATUS_PENDING,
            Order::STATUS_TRANS_ALLOCATING,
            Order::STATUS_TRANS_REJECTED,
        ],
        Order::TAB_CONFIRMED => [
            Order::STATUS_CONFIRMED,
        ],
        Order::TAB_PROCESSING => [
            Order::STATUS_PROCESSING
        ],
        Order::TAB_RESTAURANT_READY => [
            Order::STATUS_READY_TO_PICKUP
        ],
        Order::TAB_TRANS_GOING => [
            Order::STATUS_TRANS_COMING_PICK_UP,
            Order::STATUS_TRANS_GOING,
            Order::STATUS_CUSTOMER_REJECT,
        ],
        Order::TAB_TRANS_DELIVERED => [
            Order::STATUS_TRANS_DELIVERED,
        ],
        Order::TAB_COMPLETED => [
            Order::STATUS_COMPLETED,
        ],
        Order::TAB_CANCELLED => [
            Order::STATUS_CANCELLED,
            Order::STATUS_NEED_TO_CANCEL,
        ],
        Order::TAB_REFUNDED => [
            Order::STATUS_REFUNDED,
        ],
    ];

    public function getListOrderStatuses()
    {
        $orderStatuses = wc_get_order_statuses();
        unset($orderStatuses['wc-waiting-payment']);
        return array_map( fn($orderStatusKey) => str_replace('wc-', '', $orderStatusKey), array_keys($orderStatuses));
    }

    public function getOrderSummaryInfo($requestData)
    {
        ini_set("memory_limit", -1);
        $currentUser = wp_get_current_user();
        $restaurantCode = $currentUser->get('user_restaurant');
        $startDate = $requestData['startDate'] ?? date_i18n('Y-m-d');
        $endDate = $requestData['endDate'] ?? date_i18n('Y-m-d');
        $search = $requestData['search'] ?? '';

        $args = [
            'meta_key' => 'restaurant_code',
            'meta_value' => $restaurantCode,
            'status' => $this->getListOrderStatuses(),
            'numberposts' => -1,
        ];

        if ($startDate && $endDate) {
            $args['date_created'] = "{$startDate}...{$endDate}";
        }

        if ($search) {
            $args['billing_phone'] = $search;
        }

        $summaryOrders = [];
        if ($search && strlen($search) < 10) {
            $order = wc_get_order($search);
            if ($order && in_array($order->get_status(), $args['status'])) :
                $summaryOrders[] = $order;
            endif;
        } else {
            $summaryOrders = wc_get_orders($args);
        }

        $totalOrders = 0;
        $totalRevenue = 0;
        $listTabTotalOrders = array_fill_keys(array_keys(self::$tabStatus), 0);
        /** @var \WC_Order */
        foreach ($summaryOrders as $order) {
            $orderStatus = $order->get_status();
            $orderTabSearch = array_filter(self::$tabStatus, function($listTabStatus, $tabKey) use ($orderStatus) {
                return in_array($orderStatus, $listTabStatus);
            }, ARRAY_FILTER_USE_BOTH);
            if ($orderTabKey = array_key_first($orderTabSearch)) {
                $listTabTotalOrders[$orderTabKey] += 1;
            }
            if ($order->get_status() != Order::STATUS_CANCELLED) {
                $totalRevenue += $order->get_total();
            }
            $totalOrders += 1;
        }

        return [$totalOrders, number_format($totalRevenue), $listTabTotalOrders];
    }

    public function getRestaurantOrderList($requestData)
    {
        ini_set("memory_limit", -1);
        $currentUser = wp_get_current_user();
        $restaurantCode = $currentUser->get('user_restaurant');
        $page = $requestData['page'] ?? 1;
        $limit = $requestData['length'] ?? 20;
        $startDate = $requestData['startDate'] ?? date_i18n('Y-m-d');
        $endDate = $requestData['endDate'] ?? date_i18n('Y-m-d');
        $search = $requestData['search'] ?? '';
        $currentTab = $requestData['tab'] ?? Order::TAB_PENDING;

        $args = [
            'meta_key' => 'restaurant_code',
            'meta_value' => $restaurantCode,
            'status' => $this->getListOrderStatuses(),
            'numberposts' => $limit,
            'page' => $page,
        ];
        
        if (isset(self::$tabStatus[$currentTab])) {
            $args['status'] = self::$tabStatus[$currentTab];
        }
        
        if ($startDate && $endDate) {
            $args['date_created'] = "{$startDate}...{$endDate}";
        }
        
        if ($search) {
            $args['billing_phone'] = $search;
        }
        
        $totalItems = 1;
        if ($search && strlen($search) < 10) {
            $orders = [];
            $order = wc_get_order($search);
            if ($order && in_array($order->get_status(), $args['status'])) :
                $orders[] = $order;
            endif;
        } else {
            $orders = wc_get_orders($args);
            $argsTotal = $args;
            $argsTotal['numberposts'] = -1;
            $totalItems = count(wc_get_orders($argsTotal));
        }

        $otherData = [];
        $otherData['currentTab'] = $currentTab;

        return [$this->loopOrders($orders), $totalItems, $otherData];
    }

    public function loopOrders($orders)
    {
        $orderList = [];
        /** @var \WC_Order */
        foreach ($orders as $order) {
            $orderObject = new stdClass();
            $orderId = $order->get_id();
            $orderObject->checkbox = '<input type="checkbox"/>';
            $orderObject->id = '<a target="_blank" href="'.site_url('restaurant-order-detail').'?id='.$orderId.'" title="#'.$orderId.'">#'.$orderId.'</a>';
            $orderObject->customerInfo = $this->makeCustomerContent($order);
            $orderObject->totalAmount = number_format($order->get_total()) . '₫';
            $orderObject->orderTime = \GDelivery\Libs\Helper\Helper::textRecentOrderTime(strtotime($order->get_date_created()));
            $orderObject->transportVendor = $this->makeTransportVendorContent($order);
            $orderObject->status = $this->makeOrderStatusContent($order);
            $orderObject->action = $this->makeOrderActionContent($order);
            $orderList[] = $orderObject;
        }
        return $orderList;
    }

    /** @param \WC_Order $order */
    public function makeTransportVendorContent($order)
    {
        $transportVendor = '';
        if (!empty($transportVendor = $order->get_meta('vendor_transport'))) {
            $transportVendor = in_array($transportVendor, ['golden_gate', '']) ? 'Nhà hàng' : ucfirst(str_replace('_', ' ', $transportVendor));
        }
        return $transportVendor;
    }

    /** @param \WC_Order $order */
    public function makeCustomerContent($order)
    {
        return $order->get_shipping_first_name() . ' - <span class="number">' . $order->get_shipping_phone() . '</span>
            <a class="tool-tip" tabindex="0" role="button" data-toggle="popover" 
            data-trigger="focus" title="'.$order->get_shipping_first_name().' - '.$order->get_shipping_phone().'" 
            data-content="'.$order->get_shipping_address_1().','.$order->get_shipping_address_2().'">
            <i class="icon-info"></i></a>';
    }

    /** @param \WC_Order $order */
    public function makeOrderStatusContent($order)
    {
        $orderStatus = $order->get_status();
        $statusTextName = \GDelivery\Libs\Helper\Order::orderStatusName($orderStatus);
        $statusClass = isset($this->statusToClass[$orderStatus]) ? $this->statusToClass[$orderStatus] : self::DEFAULT_STATUS_CLASS;
        $orderFailReasons = [
            \GDelivery\Libs\Helper\Order::STATUS_NEED_TO_CANCEL => $order->get_meta('restaurant_note'),
            \GDelivery\Libs\Helper\Order::STATUS_CANCELLED => $order->get_meta('operator_note'),
        ];
        $reasonTooltip = '';
        if (in_array($orderStatus, array_keys($orderFailReasons))) {
            $reasonText = $orderFailReasons[$orderStatus];
            $reasonTooltip = '<a class="tool-tip p-1" tabindex="0" role="button" data-toggle="popover" 
                data-trigger="focus" data-original-title="Lý do hủy" 
                data-content="'.$reasonText.'">
                <i class="icon-info"></i></a>';
        }
        return '<div style="display: inline-flex;"><div class="alert '.$statusClass.'" role="alert" title="'.$statusTextName.'">'.$statusTextName.'</div>'.$reasonTooltip.'</div>';
        
    }

    /** @param \WC_Order $order
     * 
     * @return string
    */
    public function makeOrderActionContent($order)
    {
        return OrderActionFactory::getOrderAction($order)->render();
    }

    public static function staticMakeOrderActionContent($order)
    {
        return OrderActionFactory::getOrderAction($order)->render();
    }
}

class OrderActionFactory {

    public static $mapping = [
        Order::STATUS_PENDING => 'OrderPendingAction',
        Order::STATUS_CONFIRMED => 'OrderConfirmedAction',
        Order::STATUS_PROCESSING => 'OrderProcessingAction',
        Order::STATUS_READY_TO_PICKUP => 'OrderReadyToPickupAction',
        Order::STATUS_TRANS_ALLOCATING => 'OrderTransAllocatingAction',
        Order::STATUS_TRANS_REJECTED => 'OrderTransRejectedAction',
        Order::STATUS_TRANS_GOING => 'OrderTransGoingAction',
        Order::STATUS_TRANS_DELIVERED => 'OrderTransDeliveredAction',
    ];
    
    /** @param \WC_Order $order
     * @return IOrderAction
    */
    public static function getOrderAction($order) {
        $orderStatus = $order->get_status();
        $namespace = '\\GDelivery\\Libs\\Helper\\';
        $className = isset(self::$mapping[$orderStatus]) ? self::$mapping[$orderStatus] : 'OrderDefaultAction';
        $className = $namespace . $className;
        return new $className($order);
    }
}

abstract class AOrderAction {

    /** @var \WC_Order */
    public $order;

    public $merchantShippingPartner = [];

    public $hasSelfShipping = false;

    /** @param \WC_Order $order */
    public function __construct($order)
    {
        $this->order = $order;
        $this->initShippingInfo();
    }

    public function initShippingInfo()
    {
        $getRestaurantInfo = \GDelivery\Libs\Helper\Helper::getMerchantByCode($this->order->get_meta('restaurant_code'));
        $shippingPartnerData = get_field('merchant_shipping_partner', $getRestaurantInfo->result->id);
        
        foreach($shippingPartnerData as $item) {
            $this->merchantShippingPartner[$item['value']] = $item['label'];
        }
        
        if (isset($this->merchantShippingPartner['self'])) {
            $this->hasSelfShipping = true;
            unset($this->merchantShippingPartner['self']);
        }
    }
}

interface IOrderAction {
    public function render() : string;
}

class OrderPendingAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        $pickUpAtRestaurant = $this->order->get_meta('is_pickup_at_restaurant') != 0 ? true : false;
        $isDeliveryNow = $this->order->get_meta('is_delivery_now') == '1' ? true : false;
        $orderId = $this->order->get_id();
        $paymentMethod = $this->order->get_meta('payment_method');
        $total = $this->order->get_total('number');
        $cancelOrderHtml = '<a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_CANCEL.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CANCELLED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CANCELLED).'">Hủy đơn</a>';
        if ($pickUpAtRestaurant) {
            return '
                <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                    <a class="dropdown-item change-status" href="#"
                        data-order-id="'.$orderId.'"
                        data-action="'.\GDelivery\Libs\Helper\Order::ACTION_CONFIRM.'"
                        data-payment-method="'.$paymentMethod.'"
                        data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_PROCESSING.'"
                        data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING).'">Xác nhận</a>
                    <a class="dropdown-item" href="'.site_url('restaurant-order-detail').'?id='.$orderId.'">Thay đổi đơn hàng</a>
                    '.$cancelOrderHtml.'
                </div>
            ';
        }
        $html = '<button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">';
        if ($isDeliveryNow) { // DELIVERY NOW 
            foreach ($this->merchantShippingPartner as $partner => $partnerName) {
                $html .= '
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$orderId.'"
                    data-action="'.\GDelivery\Libs\Helper\Order::ACTION_VENDOR_TRANSPORT.'"
                    data-extra-data='.json_encode(['partner' => $partner]).'
                    data-payment-method="'.$paymentMethod.'"
                    data-order-price="'.$total.'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED).'">
                        '.$partnerName.'
                    </a>
                ';
            }
            if ($this->hasSelfShipping) {
                $html .= '
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$orderId.'"
                    data-extra-data='.json_encode(['partner' => 'golden_gate']).'
                    data-payment-method="'.$paymentMethod.'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_PROCESSING.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING).'">Nhà hàng tự giao</a>
                ';
            }
            $html .= '<a class="dropdown-item" href="'.site_url('restaurant-order-detail').'?id='.$orderId.'">Thay đổi đơn hàng</a>';
            $html .= $cancelOrderHtml;
            $html .= '</div>';
            return $html;
        }
        // SCHEDULE DELIVERY
        foreach ($this->merchantShippingPartner as $partner => $partnerName) {
            $html .= '
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_VENDOR_SCHEDULE_TRANSPORT.'"
                data-extra-data='.json_encode(['partner' => $partner]).'
                data-payment-method="'.$paymentMethod.'"
                data-order-price="'.$total.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED).'">
                    '.'Xác nhận hẹn giao (' . $partnerName . ')'.'
                </a>
            ';
        }
        if ($this->hasSelfShipping) {
            $html .= '
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-extra-data='.json_encode(['partner' => 'golden_gate']).'
                data-payment-method="'.$paymentMethod.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED).'">Xác nhận hẹn giao (Nhà hàng tự giao)</a>
            ';
        }
        $html .= '<a class="dropdown-item" href="'.site_url('restaurant-order-detail').'?id='.$orderId.'">Thay đổi đơn hàng</a>';
        $html .= $cancelOrderHtml;
        $html .= '</div>';
        return $html;
    }
}
 
class OrderConfirmedAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        $vendorTransport = $this->order->get_meta('vendor_transport');
        if ($vendorTransport == 'golden_gate') {
            return '
                <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$this->order->get_id().'"
                    data-action="'.\GDelivery\Libs\Helper\Order::ACTION_CONFIRM.'"
                    data-payment-method="'.$this->order->get_meta('payment_method').'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_PROCESSING.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING).'">Nhà hàng đang chuẩn bị</a>
                </div>
            ';
        }
        return '';
    }
}

class OrderProcessingAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        return '
            <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$this->order->get_id().'"
                data-payment-method="'.$this->order->get_meta('payment_method').'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_READY_TO_PICKUP.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_READY_TO_PICKUP).'">Nhà hàng đã chuẩn bị xong</a>
            </div>
        ';
    }
}

class OrderReadyToPickupAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        $orderId = $this->order->get_id();
        $paymentMethod = $this->order->get_meta('payment_method');
        if ($this->order->get_meta('is_pickup_at_restaurant') != 0) {
            return '
                <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$orderId.'"
                    data-action="'.\GDelivery\Libs\Helper\Order::ACTION_COMPLETE.'"
                    data-payment-method="'.$paymentMethod.'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_COMPLETED.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_COMPLETED).'">Hoàn thành</a>
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$orderId.'"
                    data-payment-method="'.$paymentMethod.'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT).'">Khách hàng không nhận đơn</a>
                </div>
            ';
        }
        if ($this->order->get_meta('vendor_transport') == 'golden_gate') {
            return '
                <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                    <a class="dropdown-item change-status" href="#"
                    data-order-id="'.$orderId.'"
                    data-payment-method="'.$paymentMethod.'"
                    data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING.'"
                    data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING).'">Đang giao hàng</a>
                </div>
            ';
        }
        return '';
    }
}

class OrderTransAllocatingAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        return '
            <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$this->order->get_id().'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_CANCEL_VENDOR_TRANSPORT.'"
                data-extra-data='.json_encode(['partner' => $this->order->get_meta('vendor_transport')]).'
                data-payment-method="'.$this->order->get_meta('payment_method').'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_TRANS_CANCEL.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_CANCEL).'">Hủy ['.$this->order->get_meta('vendor_transport').']</a>
            </div>
        ';
    }
}

class OrderTransRejectedAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        $orderId = $this->order->get_id();
        $paymentMethod = $this->order->get_meta('payment_method');
        $transportVendor = $this->order->get_meta('vendor_transport');
        $html = '<button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">';
        foreach ($this->merchantShippingPartner as $partner => $partnerName) {
            if ($transportVendor == $partner) { // Remove rejected transport vendor
                continue;
            }
            $html .= '
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_VENDOR_TRANSPORT.'"
                data-extra-data='.json_encode(['partner' => $partner]).'
                data-payment-method="'.$paymentMethod.'"
                data-order-price="'.$this->order->get_total('number').'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED).'">
                    '.$partnerName.'
                </a>
            ';
        }
        if ($this->hasSelfShipping) {
            $html .= '
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-extra-data='.json_encode(['partner' => 'golden_gate']).'
                data-payment-method="'.$paymentMethod.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_PROCESSING.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING).'">Nhà hàng tự giao</a>
            ';
            $html .= '<a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_CANCEL.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CANCELLED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CANCELLED).'">Hủy đơn</a>';
        }
        $html .= '</div>';
        return $html;
    }
}

class OrderTransGoingAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        $html = '';
        if ($this->order->get_meta('vendor_transport') == 'golden_gate') {
            $orderId = $this->order->get_id();
            $paymentMethod = $this->order->get_meta('payment_method');
            $html .= '<button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">';
            $html .= '
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-payment-method="'.$paymentMethod.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_TRANS_DELIVERED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_DELIVERED).'">Đã giao hàng</a>
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$orderId.'"
                data-payment-method="'.$paymentMethod.'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT).'">Khách hàng không nhận đơn</a>
            ';
            $html .= '</div>';
        }
        return $html;
    }
}

class OrderTransDeliveredAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        return '
            <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                <a class="dropdown-item change-status" href="#"
                data-order-id="'.$this->order->get_id().'"
                data-action="'.\GDelivery\Libs\Helper\Order::ACTION_COMPLETE.'"
                data-payment-method="'.$this->order->get_meta('payment_method').'"
                data-order-status="'.\GDelivery\Libs\Helper\Order::STATUS_COMPLETED.'"
                data-order-status-text="'.\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_COMPLETED).'">Hoàn thành</a>
            </div>
        ';
    }
}

class OrderDefaultAction extends AOrderAction implements IOrderAction
{
    public function render() : string
    {
        return '';
    }
}
