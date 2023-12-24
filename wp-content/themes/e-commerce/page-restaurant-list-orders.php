<?php

/*
Template Name: Restaurant List Orders
*/

use GDelivery\Libs\Helper\Order;

$tabStatus = [
    Order::TAB_PENDING => [
        Order::STATUS_PENDING,
        Order::STATUS_TRANS_ALLOCATING,
        Order::STATUS_TRANS_REJECTED,
    ],
    Order::TAB_CONFIRMED => [
        Order::STATUS_CONFIRMED,
    ],
    Order::TAB_REQUEST_SUPPORT => [
        Order::STATUS_REQUEST_SUPPORT,
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
$listTabTotalOrders = array_fill_keys(array_keys($tabStatus), 0);

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if ($user->role != 'restaurant' && $user->role != 'administrator') {
    wp_die('Bạn không được phép truy cập trang này');
}

$restaurant = $currentUser->get('user_restaurant');

//$getRestaurantInfo = \GDelivery\Libs\Helper\Helper::getMerchant($restaurant);

$page = get_query_var('paged') > 0 ? get_query_var('paged') : 1;
$fromDate = $_GET['fromDate'] ?? date_i18n('Y-m-d');
$toDate = $_GET['toDate'] ?? date_i18n('Y-m-d');
$search = $_GET['search'] ?? '';
$currentTab = $_GET['tab'] ?? Order::TAB_PENDING;

$args = [
    'meta_key' => 'restaurant_code',
    'meta_value' => $restaurant,
    'numberposts' => 30,
    'page' => $page,
];

if (isset($tabStatus[$currentTab])) {
    $args['status'] = $tabStatus[$currentTab];
}

if ($fromDate && $toDate) {
    $args['date_created'] = "{$fromDate}...{$toDate}";
}

if ($search) {
    $args['billing_phone'] = $search;
}

$oldMemory = ini_get('memory_limit');
ini_set("memory_limit", -1);

if ($search && strlen($search) < 10) {
    $orders = [];
    $getOrderOne = wc_get_order($search);
    if ($getOrderOne) :
        $orders[] = $getOrderOne;
    endif;
} else {
    $orders = wc_get_orders($args);
}
wp_reset_query();


// process summary
$args2 = [
    'meta_key' => 'restaurant_code',
    'meta_value' => $restaurant,
    'numberposts' => -1,
];

if ($fromDate && $toDate) {
    $args2['date_created'] = "{$fromDate}...{$toDate}";
}

if ($search) {
    $args2['billing_phone'] = $search;
}

if ($search && strlen($search) < 10) {
    $getOrder = wc_get_order($search);
    $summaryOrders = [];
    if ($getOrder) :
        $summaryOrders[] = $getOrder;
    endif;
} else {
    $summaryOrders = wc_get_orders($args2);
}
wp_reset_query();
$totalOrders = 0;
$totalRevenue = 0;
if (!empty($summaryOrders)) :
foreach ($summaryOrders as $order) {
    $orderStatus = $order->get_status();
    if ($orderStatus == Order::STATUS_WAITING_PAYMENT) {
        continue;
    }
    $orderTabSearch = array_filter($tabStatus, function($listTabStatus, $tabKey) use ($orderStatus) {
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
endif;

$queryUri = \GDelivery\Libs\Helper\Helper::parseQueryUri();
ini_set("memory_limit", $oldMemory);

get_header('restaurant', [
    'user' => $user
]);

?>

<main class="content">
    <div class="container">
        <div class="row feature">
            <!--<div class="col-xl-6 col-lg-12">
                <select><option>Gogi House</option></select>
                <select><option>Nhà hàng</option></select>
            </div>-->
            <div class="col-xl-12 col-lg-12">
                <form action="" method="get">
                    <input type="hidden" value="<?=$currentTab?>" name="tab" />
                    <input class="datetime-picker" type="text"  name="fromDate" placeholder="Từ ngày" value="<?=$fromDate?>" />
                    <input class="datetime-picker" type="text" name="toDate" placeholder="Đến ngày" value="<?=$toDate?>" />
                    <input type="input" name="search" placeholder="Nhập mã đơn hoặc SĐT..." value="<?=(isset($_GET['search']) ? $_GET['search'] : '')?>" />
                    <input class="btn btn-submit" value="Tìm" type="submit" />
                </form>

<!--                <div class="slipt"></div>-->
                <a href="#" class="func open-printer"><i class="icon-print"></i>In đơn hàng</a>
                <!--<a href="#" class="func"><i class="icon-file-down"></i>Tải xuống</a>-->
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-8 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?=site_url()?>" title="<?=bloginfo('name')?>">Trang chủ</a></li>
                        <!--<li class="breadcrumb-item"><a href="#">Quản trị danh mục</a></li>-->
                        <li class="breadcrumb-item active" aria-current="page">Quản lý đơn hàng</li>
                    </ol>
                </nav>
            </div>
            <div class="col-xl-4 col-lg-12">
                <div class="summary">
                    <?=$totalOrders?> đơn hàng<i></i><span>Doanh thu: <?=number_format($totalRevenue)?> vnđ</span>
                </div>
            </div>
        </div>
        <!-- end block info -->
        <div class="row">
            <div class="col-xl-12">
                <ul class="tab-status">
                    <?php
                    if ($queryUri) {
                        $temp = clone $queryUri;
                        if (isset($temp->params['tab'])) {
                            unset($temp->params['tab']);
                        }
                        $statusUri = '?'.http_build_query($temp->params).'&';
                    } else {
                        $statusUri = '?';
                    }
                    ?>
                    <?php foreach ($tabStatus as $tabStatusKey => $tabStatusList) :?>
                        <li><a class="<?=($currentTab == $tabStatusKey ? 'active' : '')?>" href="<?=site_url('restaurant-list-orders')?>/<?=$page?>/<?=$statusUri?>tab=<?=$tabStatusKey?>"><?=Order::$restaurantTabs[$tabStatusKey]?> <span><?=$listTabTotalOrders[$tabStatusKey]?></span></a></li>
                    <?php endforeach; ?>
                    <li><a class="<?=($currentTab == 'all' ? 'active' : '')?>" href="<?=site_url('restaurant-list-orders')?>/<?=$page?>/<?=$statusUri?>tab=all">Tất cả <span><?=$totalOrders?></span></a></li>
                </ul>
            </div>
        </div>
        <!-- end tabs status -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="wrap-tbl">
                    <table class="table table-hover ">
                        <thead>
                        <tr>
                            <th scope="col" width="3%">
                                <input type="checkbox"  id="exampleCheck1">
                            </th>
                            <th scope="col">Mã đơn hàng</th>
                            <th scope="col">Thông tin khách hàng</th>
                            <th scope="col">Tổng tiền</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col" class="text-center">Vận Chuyển</th>
                            <th scope="col" class="text-center">Trạng thái</th>
                            <?php if ($currentTab == Order::TAB_CANCELLED) :?>
                                <th scope="col" class="text-center">Lý do</th>
                            <?php endif;?>
<!--                            <th scope="col" class="text-center">Lý do</th>-->
<!--                            <th scope="col" class="text-center">Ghi chú</th>-->
                            <th scope="col" class="text-center">Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            if ($currentTab == Order::TAB_CANCELLED) {
                                $colSpan = 8;
                            } else {
                                $colSpan = 7;
                            }
                        ?>
                        <?php if ($orders) : ?>
                        <?php foreach ($orders as $order) :?>
                            <?php
                                $orderItemStatus = $order->get_status();
                                $getRestaurantInfo = \GDelivery\Libs\Helper\Helper::getMerchantByCode($order->get_meta('restaurant_code'));
                                $shippingPartnerData = get_field('merchant_shipping_partner', $getRestaurantInfo->result->id);
                                $merchantShippingPartner = [];
                                foreach($shippingPartnerData as $item) {
                                    $merchantShippingPartner[$item['value']] = $item['label'];
                                }
                                $hasSelfShipping = false;
                                if (isset($merchantShippingPartner['self'])) {
                                    $hasSelfShipping = true;
                                    unset($merchantShippingPartner['self']);
                                }
                                if ($orderItemStatus == 'waiting-payment') {
                                    continue;
                                }
                                $jsonRestaurant = $order->get_meta('restaurant_object');
                                switch ($orderItemStatus) {
                                    case 'processing' :
                                    case 'trans-going':
                                    case 'trans-request':
                                        $statusClass = 'alert-success';
                                        break;
                                    case 'need-to-transfer':
                                    case 'cancelled':
                                    case 'trans-rejected':
                                        $statusClass = 'alert-danger';
                                        break;
                                    case 'completed':
                                        $statusClass = 'alert-info';
                                        break;
                                    default :
                                        $statusClass = 'alert-warning';
                                }
                                $statusTextName = \GDelivery\Libs\Helper\Order::orderStatusName($orderItemStatus);
                                $needDelivery = $order->get_meta('is_pickup_at_restaurant') == 0 ? true : false;
                                $isDeliveryNow = $order->get_meta('is_delivery_now') == '1' ? true : false;
                                $vendorTransport = $order->get_meta('vendor_transport');
                                ?>
                                <tr>
                                    <td><input type="checkbox"/></td>
                                    <td><a href="<?=site_url('restaurant-order-detail')?>?id=<?=$order->get_id()?>" title="#<?=$order->get_id()?>">#<?=$order->get_id()?></a></td>
                                    <td>
                                        <?=$order->get_shipping_first_name()?> - <span class="number"><?=$order->get_shipping_phone()?></span>
                                        <a class="tool-tip" tabindex="0" role="button" data-toggle="popover" data-trigger="focus" title="<?=$order->get_shipping_first_name()?> - <?=$order->get_shipping_phone()?>" data-content="<?=$order->get_shipping_address_1()?>, <?=$order->get_shipping_address_2()?>"><i class="icon-info"></i></a>
                                    </td>
                                    <td><?=number_format($order->get_total())?>₫</td>
                                    <td data-title="_created_time">
                                        <?=\GDelivery\Libs\Helper\Helper::textRecentOrderTime(strtotime($order->get_date_created()))?> <br />
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $transportVendor = '';
                                            if (!empty($transportVendor = $order->get_meta('vendor_transport'))) {
                                                $transportVendor = in_array($transportVendor, ['golden_gate', '']) ? 'Nhà hàng' : ucfirst(str_replace('_', ' ', $transportVendor));
                                            }
                                            echo $transportVendor;
                                        ?>
                                    </td>
                                    <td>
                                        <div class="alert <?=$statusClass?>" role="alert">
                                            <?=$statusTextName?>
                                        </div>
                                    </td>
                                    <?php if ($orderItemStatus == 'need-to-cancel') :?>
                                        <td scope="col" class="text-center"><?=$order->get_meta('restaurant_note');?></td>
                                    <?php elseif ($orderItemStatus == 'cancelled') :?>
                                        <td scope="col" class="text-center"><?=$order->get_meta('operator_note');?></td>
                                    <?php endif;?>
                                    <td class="text-center">
                                        <?php
                                            if (
                                                (
                                                    $user->role == 'restaurant'
                                                    && (!in_array($orderItemStatus, ['completed', 'cancelled', 'refunded']))
                                                )
                                                || $user->role == 'administrator'
                                            ) :
                                        ?>

                                        <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                                            <!-- PENDING STATUS -->
                                            <?php if ($orderItemStatus == Order::STATUS_PENDING): ?>
                                                <?php if ($needDelivery): ?> <!-- PARTNER SHIPPING -->
                                                    <?php if ($isDeliveryNow): ?> <!-- DELIVERY NOW -->
                                                        <?php foreach ($merchantShippingPartner as $partner => $partnerName) :?>
                                                            <a class="dropdown-item change-status" href="#"
                                                            data-order-id="<?=$order->get_id()?>"
                                                            data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_VENDOR_TRANSPORT?>"
                                                            data-extra-data='<?=json_encode(['partner' => $partner])?>'
                                                            data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                            data-order-price="<?=$order->get_total('number')?>"
                                                            data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED?>"
                                                            data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED)?>">
                                                                <?=$partnerName?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                        <?php if ($hasSelfShipping): ?>
                                                            <a class="dropdown-item change-status" href="#"
                                                            data-order-id="<?=$order->get_id()?>"
                                                            data-extra-data='<?=json_encode(['partner' => 'golden_gate'])?>'
                                                            data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                            data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_PROCESSING?>"
                                                            data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING)?>">Nhà hàng tự giao</a>
                                                        <?php endif; ?>
                                                    <?php else: ?> <!-- SCHEDULE DELIVERY -->
                                                        <?php foreach ($merchantShippingPartner as $partner => $partnerName) :?>
                                                            <a class="dropdown-item change-status" href="#"
                                                            data-order-id="<?=$order->get_id()?>"
                                                            data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_VENDOR_SCHEDULE_TRANSPORT?>"
                                                            data-extra-data='<?=json_encode(['partner' => $partner])?>'
                                                            data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                            data-order-price="<?=$order->get_total('number')?>"
                                                            data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED?>"
                                                            data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED)?>">
                                                                <?='Xác nhận hẹn giao (' . $partnerName . ')'?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                        <?php if ($hasSelfShipping): ?>
                                                            <a class="dropdown-item change-status" href="#"
                                                            data-order-id="<?=$order->get_id()?>"
                                                            data-extra-data='<?=json_encode(['partner' => 'golden_gate'])?>'
                                                            data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                            data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED?>"
                                                            data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CONFIRMED)?>">Xác nhận hẹn giao (Nhà hàng tự giao)</a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <a class="dropdown-item" href="<?=site_url('restaurant-order-detail')?>?id=<?=$order->get_id()?>">Yêu cầu hỗ trợ</a>
                                                <?php else: ?> <!-- (RESTAURANT SHIPPING) -->
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_CONFIRM?>"
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_PROCESSING?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING)?>">Xác nhận</a>
                                                    <a class="dropdown-item" href="<?=site_url('restaurant-order-detail')?>?id=<?=$order->get_id()?>">Yêu cầu hỗ trợ</a>
                                                <?php endif; ?>
                                            <!-- CONFIRMED STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_CONFIRMED): ?>
                                                <?php if ($vendorTransport == 'golden_gate'): ?>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_CONFIRM?>"
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_PROCESSING?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING)?>">Nhà hàng đang chuẩn bị</a>
                                                <?php endif; ?>
                                            <!-- PROCESSING (RESTAURANT PREPARING) STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_PROCESSING): ?>
                                                <a class="dropdown-item change-status" href="#"
                                                data-order-id="<?=$order->get_id()?>"
                                                data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_READY_TO_PICKUP?>"
                                                data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_READY_TO_PICKUP)?>">Nhà hàng đã chuẩn bị xong</a>
                                            <!-- RESTAURANT READY STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_READY_TO_PICKUP): ?>
                                                <?php if ($order->get_meta('is_pickup_at_restaurant') == 0): ?>
                                                    <?php if ($order->get_meta('vendor_transport') == 'golden_gate'): ?>
                                                        <a class="dropdown-item change-status" href="#"
                                                        data-order-id="<?=$order->get_id()?>"
                                                        data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                        data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING?>"
                                                        data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING)?>">Đang giao hàng</a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_COMPLETE?>"
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_COMPLETED?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_COMPLETED)?>">Hoàn thành</a>
                                                <?php endif; ?>
                                            <!-- TRANS ALLOCATING STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_TRANS_ALLOCATING): ?>
                                                <a class="dropdown-item change-status" href="#"
                                                data-order-id="<?=$order->get_id()?>"
                                                data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_CANCEL_VENDOR_TRANSPORT?>"
                                                data-extra-data='<?=json_encode(['partner' => $order->get_meta('vendor_transport')])?>'
                                                data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_TRANS_CANCEL?>"
                                                data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_CANCEL)?>">Hủy [<?=$order->get_meta('vendor_transport');?>]</a>
                                            <!-- TRANS REJECTED STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_TRANS_REJECTED): ?>
                                                <?php foreach ($merchantShippingPartner as $partner => $partnerName) :?>
                                                    <?php if ($partner == $order->get_meta('vendor_transport')) continue; ?>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_VENDOR_TRANSPORT?>"
                                                    data-extra-data='<?=json_encode(['partner' => $partner])?>'
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-price="<?=$order->get_total('number')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_REQUESTED)?>">
                                                        <?=$partnerName?>
                                                    </a>
                                                <?php endforeach; ?>
                                                <?php if ($hasSelfShipping): ?>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-extra-data='<?=json_encode(['partner' => 'golden_gate'])?>'
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_PROCESSING?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_PROCESSING)?>">Nhà hàng tự giao</a>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_CANCEL?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_CANCELLED?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CANCELLED)?>">Hủy đơn</a>
                                                <?php endif; ?>
                                            <!-- TRANS GOING STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_TRANS_GOING): ?>
                                                <?php if ($order->get_meta('vendor_transport') == 'golden_gate'): ?>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_TRANS_DELIVERED?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_TRANS_DELIVERED)?>">Đã giao hàng</a>
                                                    <a class="dropdown-item change-status" href="#"
                                                    data-order-id="<?=$order->get_id()?>"
                                                    data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                    data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT?>"
                                                    data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_CUSTOMER_REJECT)?>">Khách hàng không nhận đơn</a>
                                                <?php endif; ?>
                                            <!-- TRANS DELIVERED STATUS -->
                                            <?php elseif ($orderItemStatus == Order::STATUS_TRANS_DELIVERED): ?>
                                                <a class="dropdown-item change-status" href="#"
                                                data-order-id="<?=$order->get_id()?>"
                                                data-action="<?=\GDelivery\Libs\Helper\Order::ACTION_COMPLETE?>"
                                                data-payment-method="<?=$order->get_meta('payment_method')?>"
                                                data-order-status="<?=\GDelivery\Libs\Helper\Order::STATUS_COMPLETED?>"
                                                data-order-status-text="<?=\GDelivery\Libs\Helper\Order::orderStatusName(\GDelivery\Libs\Helper\Order::STATUS_COMPLETED)?>">Hoàn thành</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; // end if check status and user role?>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <th colspan="<?=$colSpan;?>>">Chưa có đơn hàng</th>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end table -->
            <div class="col-xl-12">
                <nav aria-label="...">
                    <ul class="pagination">
                        <?php
                        $page = get_query_var('paged') > 0 ? get_query_var('paged') : 1;
                        $currentUrl = add_query_arg( NULL, NULL ) ;
                        $queryString = isset(parse_url($currentUrl)['query']) ? '?'.parse_url($currentUrl)['query'] : '';
                        ?>

                        <li class="page-item <?=($page == 1 ? 'disabled' : '')?>">
                            <a class="page-link" href="<?=site_url('restaurant-list-orders')?>/<?=($page-1)?>?<?=$queryString?>" tabindex="-1" aria-disabled="true"><span aria-hidden="true">&laquo;</span></a>
                        </li>

                        <?php if ($page == 1) :?>
                            <li class="page-item active" aria-current="page">
                                <a class="page-link" href="<?=site_url('restaurant-list-orders')?>/<?=$queryString?>">1 <span class="sr-only">(current)</span></a>
                            </li>
                            <li class="page-item"><a class="page-link" href="<?=site_url('restaurant-list-orders')?>/page/2/<?=$queryString?>">2</a></li>
                            <li class="page-item"><a class="page-link" href="<?=site_url('restaurant-list-orders')?>/page/3/<?=$queryString?>">3</a></li>

                        <?php else: ?>
                            <li class="page-item"><a class="page-link" href="<?=site_url('restaurant-list-orders')?>/page/<?=($page-1)?>?<?=$queryString?>"><?=($page-1)?></a></li>

                            <li class="page-item active" aria-current="page">
                                <a class="page-link" href="<?=site_url('restaurant-list-orders')?>/<?=$queryString?>"><?=$page?> <span class="sr-only">(current)</span></a>
                            </li>

                            <li class="page-item"><a class="page-link" href="<?=site_url('restaurant-list-orders')?>/page/<?=($page+1)?>/<?=$queryString?>"><?=($page+1)?></a></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?=site_url('restaurant-list-orders')?>/page/<?=($page+1)?>/<?=$queryString?>"> <span aria-hidden="true">&raquo;</span></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>

<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<?php get_template_part('content/restaurant', 'search-address'); ?>

<?php
get_footer('restaurant');
?>

