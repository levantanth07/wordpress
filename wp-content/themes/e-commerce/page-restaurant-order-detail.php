<?php
/*
Template Name: Restaurant Order Detail
*/

use GDelivery\Libs\Config;
use GDelivery\Libs\Helper\Order;

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if ($user->role != 'restaurant' && $user->role != 'administrator' && $user->role != 'operator' && $user->role != 'am') {
    wp_die('Bạn không được phép truy cập trang này');
}

// get order detail
$order = wc_get_order( $_REQUEST['id'] );

if ($order) :

    // save restaurant note
    if (
            ($user->role == 'restaurant' || $user->role == 'administrator')
            && isset($_REQUEST['restaurant_note'])
            && $order->get_status() != 'completed'
            && $order->get_status() != 'cancelled'
    ) {
        $order->update_meta_data('restaurant_note', $_REQUEST['restaurant_note']);
        $order->save();
    }

    // at this time, trigger check payment when open order
    if ($order->get_meta('payment_method') != 'COD') {
        if (!$order->get_meta('is_paid') == 1) {
            $paymentService = new \GDelivery\Libs\PaymentHubService();
            $checkPayment = $paymentService->checkPayment($order->get_meta('payment_request_id'));
            if ($checkPayment->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $order->set_date_paid(strtotime($checkPayment->result['partnerPaymentTime']));
                $order->update_meta_data('payment_partner_transaction_id', $checkPayment->result['partnerTransactionId']);
                $order->update_meta_data('is_paid', 1);
                $order->set_status('pending');
                $order->save();

                // calling new order
                GDelivery\Libs\Helper\Call::makeAcall($order);

                // send mail new order
                GDelivery\Libs\Helper\Mail::send($order);
            }
        }
    }

    // get restaurant info
    $getRestaurant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($order->get_meta('restaurant_code'));

    $selectedRestaurant = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant();

    if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
        $jsonRestaurant = $getRestaurant->result;
    } else {
        $jsonRestaurant = $order->get_meta('restaurant_in_bachmai');
    }

    $user = Permission::checkCurrentUserRole();

    // status class
    switch ($order->get_status()) {
        case 'processing' :
        case 'transport-going':
        case 'transport-request':
            $statusClass = 'alert-success';
            break;
        case 'need-to-transfer':
        case 'canceled':
            $statusClass = 'alert-danger';
            break;
        case 'completed':
            $statusClass = 'alert-info';
            break;
        default :
            $statusClass = 'alert-warning';
    }

    // step
    $processPercent = 0;
    $step = 1;
    switch ($order->get_status()) {
        case 'pending' :
        case 'need-to-transfer':
            break;
        case 'processing':
            $processPercent = 30;
            $step = 2;
            break;
        case 'trans-allocating':
            $processPercent = 30;
            $step = 2;
            break;
        case 'trans-requested':
        case 'trans-going':
            $processPercent = 70;
            $step = 3;
            break;
        case 'trans-delivered' :
            $processPercent = 70;
            $step = 3;
            break;
        case 'completed' :
            $processPercent = 100;
            $step = 4;
    }

    $totals = \GDelivery\Libs\Helper\Helper::orderTotals($order);

    $totalPrice = $totals->totalPrice;
    $totalDiscount = $totals->totalDiscount;
    $totalTax = $totals->totalTax;
    $total = $totals->total;
    $shippingFee = $totals->shipping->price;

    // get available restaurant for order
    $options = [];
    if (isset($order->get_meta('customer_selected_address')->longitude)) {
        $options['fromLongitude'] = $order->get_meta('customer_selected_address')->longitude;
    }
    if (isset($order->get_meta('customer_selected_address')->latitude)) {
        $options['fromLatitude'] = $order->get_meta('customer_selected_address')->latitude;
    }
    $getAvailableRestaurant = \GDelivery\Libs\Helper\Helper::getRestaurantsInCategory(
        $order->get_meta('current_product_category_id'),
        $options
    );

    foreach ($getAvailableRestaurant->result as $oneRestaurant) {
        if ($oneRestaurant->restaurant && $oneRestaurant->restaurant->code == $jsonRestaurant->restaurant->code) {
            $jsonRestaurant->restaurant->distance = $oneRestaurant->restaurant->distance;
        }
    }

    // selected vouchers
    $selectedVouchers = $order->get_meta('selected_vouchers');
    $listCashVouchers = [];
    $listDiscountVouchers = [];
    if ($selectedVouchers) {
        foreach ($selectedVouchers as $one) {
            if ($one->type == 1) {
                $listCashVouchers[] = $one;
            } else {
                $listDiscountVouchers[] = $one;
            }
        }
    }

    get_header('restaurant', [
        'user' => $user
    ]);
?>
<style>
    .select2-container{
        width: 100% !important;
    }
    .select2-container .select2-selection,
    .select2-container--default .select2-selection .select2-selection__rendered,
    .select2-container--default .select2-selection .select2-selection__arrow,
    .select2-container--default .select2-selection .select2-selection__clear{
        height: 38px;
    }
    .select2-container--default .select2-selection{
        border: 1px solid #EDEDED;
    }
    .select2-container--default .select2-selection .select2-selection__rendered{
        line-height: 38px;
    }
    .select2-search--dropdown .select2-search__field {
        padding: 6px;
        width: 100%;
        box-sizing: border-box;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid;
        border-radius: 0.25rem;
        border-color: #e4e7ea;
    }
    .select2-search--dropdown {
        display: block;
        padding: 4px;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field{
        border: solid 1px #e4e7ea;
    }
    .select2-container *:focus {
        outline: 0;
    }
    .select2-results__option {
        padding: 10px;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .select2-container--default .select2-selection .select2-selection__clear span {
        width: 1.2em;
        height: 1.2em;
        line-height: 1.15em;
        border-radius: 100%;
        background-color: #3c4b64;
        color: #ebedef;
        float: right;
        margin-right: 0.3em;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice{
        height: 32px;
        line-height: 32px;
        padding-left: 30px;
        vertical-align: top;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
        line-height: 30px;
        border-top-right-radius: 0px;
        border-bottom-right-radius: 0px;
    }
    .select2-container .select2-search--inline .select2-search__field{
        height: 35px;
    }
    .select2-container .select2-selection.select2-selection--multiple{
        height: auto;
    }
    .select2-container--default .select2-selection--multiple{
        padding-bottom: 0px;
    }
    .main-product{
        display: flex;
        align-items: center;
    }
    .main-product img{
        max-width: 60px;
        max-height: 60px;
    }
    .main-product .txt-prd-name{
        margin-left: 10px;
    }
    .main-product .txt-prd-name h4{
        font-size: 16px;
        font-weight: 600;
    }
    .title-sw{
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 0px;
    }
    .tags-list li{
        display: inline-block;
    }
    .tags-list li a{
        display: inline-block;
        color: #000;
        cursor: pointer;
        padding: 8px 16px;
        margin-top: 12px;
        text-align: center;
        user-select: none;
        margin-right: 16px;
        border-radius: 100px;
        background-color: #EDEDED;
    }
    .tags-list li a.active{
        color: #fff;
        background-color: #E96E34;
    }
    .toppings ul li{
        margin-bottom: 5px;
    }
    .toppings ul li a{
        font-size: 15px;
        display: flex;
        align-items: center;
    }
    .toppings ul li a label{
        margin-bottom: 0px;
        margin-left: 5px;
        color: #0c0c0c;
    }
    .toppings ul li a input[type=checkbox]{
        -ms-transform: scale(1.2);
        -webkit-transform: scale(1.2);
        transform: scale(1.2);
    }
    .sz-12{
        -ms-transform: scale(1.2);
        -webkit-transform: scale(1.2);
        transform: scale(1.2);
    }
    .act-btn button{
        border: 1px solid #1073BE;
        border-radius: 2px;
        height: 34px;
        color: #1073BE;
        line-height: 34px;
        padding: 0 10px;
        font-size: 13px;
    }
    .act-btn button:hover{
        background: #1073BE;
        color: #fff;
    }
    .group-btn-detail{
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    .group-btn-detail .slipt{
        margin: 0px 10px;
        height: 25px !important;
    }
</style>
<main class="content">
    <div class="container">
        <div class="row feature">
            <div class="col-xl-6 col-lg-12">
                <h4><?=$jsonRestaurant->restaurant->name?> - <?=$jsonRestaurant->restaurant->telephone?></h4>
            </div>
            <div class="col-xl-6 col-lg-12">
                <div class="group-btn-detail">
                    <a href="#" class="func open-printer"><i class="icon-print"></i>In đơn hàng</a>
                    <div class="slipt"></div>
                    <?php
                    $act = \GDelivery\Libs\Helper\RestaurantOrderList::staticMakeOrderActionContent($order);
                    ?>
                    <div class="pull-right act-btn">
                        <?=$act?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-12 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item "><a href="<?=(($user->role == 'restaurant') ? site_url('restaurant-list-order') : site_url('operator-list-order'))?>">Quản lý đơn hàng</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chi tiết đơn hàng</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row wrap-status-order">
            <div class="col-xl-6 col-lg-6">
                Mã đơn hàng: <span>#<?=$order->get_id()?></span>
                <div class="alert <?=$statusClass?> pl-1 pr-1" role="alert"><?=Order::orderStatusName($order->get_status())?></div>
                <input type="hidden" class="orderId" value="<?=$order->get_id()?>">
            </div>
            <div class="col-xl-6 col-lg-6 wrap-action">
                <?php
                // $order->get_status() == 'processing'
                if (
                        get_field('merchant_allow_create_rk_order', $jsonRestaurant->id) == 1
                        && !$order->get_meta('is_created_rk_order')
                        && $user->role != 'operator'
                        && in_array($order->get_status(), [Order::STATUS_PROCESSING, Order::STATUS_READY_TO_PICKUP, Order::STATUS_TRANS_GOING])
                ) :?>
                    <button id="btn-create-rk-order" class="btn-sbm" data-order-id="<?=$order->get_id()?>">Tạo order trên POS</button>
                <?php endif; ?>
            </div>
        </div>
        <!-- end block info -->
        <!-- proccess -->
        <div class="row">
            <div class="col-xl-12">
                <div class="status-procces">
                    <div class="container">
                        <div class="row no-gutters">
                            <div class="col-xl-2 col-md-2 step-process <?=($step >= 1) ? 'active' : ''?>">
                                <span><i class="icon-clock"></i></span>
                            </div>
                            <div class="col-xl-3 col-md-3 step-process <?=($step >= 2) ? 'active' : ''?>">
                                <span><i class="icon-check"></i></span>
                            </div>
<!--                            <div class="col-xl-2 col-md-2 step-process">-->
<!--                                <span><i class="icon-folder-check"></i></span>-->
<!--                            </div>-->
                            <div class="col-xl-3 col-md-3 step-process <?=($step >= 3) ? 'active' : ''?>">
                                <span><i class="icon-car"></i></span>
                            </div>
                            <div class="col-xl-2 col-md-2 step-process <?=($step >= 4) ? 'active' : ''?>">
                                <span><i class="icon-list-check"></i></span>
                            </div>
                        </div>
                        <div class="row no-gutters wrap-status-process">
                            <div class="col-xl-12">
                                <div class="bar-color">
                                    <div class="fill" style="width: <?=$processPercent?>%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row no-gutters">
                            <div class="col-xl-2 col-md-2 step-process active">
                                <p>Chờ xử lý</p>
                                <!--<div class="time">30s</div>-->
                            </div>
                            <div class="col-xl-3 col-md-3 step-process">
                                <p>Đã xác nhận</p>
                            </div>
                            <!--<div class="col-xl-2 col-md-2 step-process">
                                <p>Chuẩn bị đơn hàng</p>
                            </div>-->
                            <div class="col-xl-3 col-md-3 step-process">
                                <p>Đang giao hàng</p>
                            </div>
                            <div class="col-xl-2 col-md-2 step-process">
                                <p>Đã hoàn thành</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row wrap-info">
            <div class="col-xl-6">
                <div class="block block-left add-height-block scroll">
                    <h4>Thông tin người nhận</h4>
                    <ul>
                        <li><?=$order->get_shipping_first_name()?> - <span class=""><?=$order->get_shipping_phone()?></span></li>
                        <li>
                            Địa chỉ giao hàng:
                            <strong>
                                <?=$order->get_shipping_address_1()?>
                            </strong>
                        </li>
                        <li>Ngày giao: <?=$order->get_meta('delivery_date')?> <?=$order->get_meta('delivery_time')?></li>
                        <li>Ghi chú đơn hàng: <?=$order->get_customer_note()?></li>
                    </ul>
                </div>
            </div>
            <div class="col-xl-6 ">
                <div class="block block-right add-height-block">
                    <h4>Thông tin đơn hàng</h4>
                    <ul>
                        <li>Thời gian đặt: <?=\GDelivery\Libs\Helper\Helper::textRecentOrderTime($order->get_date_created()->getTimestamp())?></li>
                        <li>
                            Hình thức thanh toán:
                            <?php
                                if(
                                        $order->get_meta('payment_method') != 'COD'
                                        && $order->get_meta('payment_method') != Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME
                                ):
                            ?>
                            <span class="text text-info"><?=\GDelivery\Libs\Helper\Helper::textPaymentMethod($order->get_meta('payment_method'))?> - Mã giao dịch: <?=$order->get_meta('payment_partner_transaction_id')?></span>
                            <?php else: ?>
                            <span class="text text-info"><?=\GDelivery\Libs\Helper\Helper::textPaymentMethod($order->get_meta('payment_method'))?></span>
                            <?php endif; ?>
                        </li>
                        <?php
                        $rkOrder = $order->get_meta('rkOrder');
                        if ($rkOrder) :
                            if (is_array($rkOrder)) {
                                $billNumber = $rkOrder['billNumber'];
                                $checkNumber = $rkOrder['checkNumber'];
                            } else {
                                $billNumber = isset($rkOrder->billNumber) ? $rkOrder->billNumber : '';
                                $checkNumber = isset($rkOrder->checkNumber) ? $rkOrder->checkNumber : '';
                            }
                            ?>
                        <li>Số bill: <?=$billNumber?></li>
                        <li>Số check: <?=$checkNumber?></li>
                        <?php endif; ?>
                        <!--<li>Khoảng cách: 5km - Phí ship:30k</li>-->
                        <!--<li>Đơn vị vận chuyển: Grab Express</li>-->
                    </ul>
                </div>
            </div>
        </div>
        <?php  $cusInvoice = $order->get_meta('customer_invoice');
        if ($cusInvoice['info'] == 1) : ?>
        <div class="row wrap-info wrap-order">
            <div class="col-xl-12">
                <div class="block-info-invoice block block-left">
                    <h4>Thông tin hóa đơn</h4>
                    <div class="row">
                        <div class="col-xl-6">
                            <ul>
                                <li>Tên công ty: <?=$cusInvoice['name']?></li>
                                <li>Mã số thuế: <?=$cusInvoice['number']?></li>
                            </ul>
                        </div>
                        <div class="col-xl-6">
                            <ul>
                                <li>Địa chỉ: <?=$cusInvoice['address']?></li>
                                <li>Email: <?=$cusInvoice['email']?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($order->get_meta('vendor_transport') == 'grab_express' && $order->get_status() != Order::STATUS_TRANS_ALLOCATING) :?>
            <?php $deliveryObj = $order->get_meta('grab_delivery_object') ?>
            <?php
            $timeFrom = null;
            $timeTo = null;
            if ($deliveryObj && $deliveryObj->getSchedule()) {
                if (!empty($pickupTimeFrom = $deliveryObj->getSchedule()->getPickupTimeFrom())) {
                    $timeFrom = new DateTime($pickupTimeFrom);
                    $timeFrom->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                }
                if (!empty($pickupTimeTo = $deliveryObj->getSchedule()->getPickupTimeTo())) {
                    $timeTo = new DateTime($pickupTimeTo);
                    $timeTo->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                }
            }
            ?>
            <?php if ($deliveryObj) : ?>
                <div class="row wrap-info wrap-order">
                    <div class="col-xl-12">
                        <div class="block-info-invoice block block-left">
                            <h4>Thông tin grab</h4>
                            <div class="row">
                                <div class="col-xl-6">
                                    <ul>
                                        <li>Mã giao hàng: <?=$deliveryObj->getId()?></li>
                                        <?php if($deliveryObj->getDriver()) : ?>
                                        <li>Tên tài xế: <?=$deliveryObj->getDriver()->getName()?></li>
                                        <li>Biển số xe: <?=$deliveryObj->getDriver()->getLicense()?></li>
                                        <li>Số điện thoại: <?=$deliveryObj->getDriver()->getPhone()?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-xl-6">
                                    <ul>
                                        <li>Phí ship: <?=$order->get_meta('actual_shipping_fee') ? number_format($order->get_meta('actual_shipping_fee')) : 0?>₫</li>
                                        <li>Thời gian lấy hàng: <?=($timeFrom ? $timeFrom->format('d/m/Y H:i') : '')?></li>
                                        <li>Thời gian dự kiến giao hàng: <?=($timeTo ? $timeTo->format('d/m/Y H:i') : '')?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- restaurant note -->
        <div class="row wrap-order">
            <div class="col-xl-12">
                <h4>Ghi chú của nhà hàng</h4>
                <form action="" method="post">
                    <div class="form-row">
                        <?php
                            if (
                                $user->role == 'restaurant' || $user->role == 'administrator'
                            ) {
                                ?>
                                <div class="col-xl-12 col-lg-6 col-12">
                                    <textarea name="restaurant_note" placeholder="Ghi chú của nhà hàng" class="form-control"><?=$order->get_meta('restaurant_note')?></textarea>
                                </div>
                                <?php if ($order->get_status() != 'completed' && $order->get_status() != 'cancelled'): ?>
                                <div class="col-xl-6 col-lg-6 col-12 mt-2">
                                    <button class="btn btn-primary" type="submit">Lưu</button>
                                </div>
                                    <?php
                                    endif;
                            } else {
                                ?>
                                <div class="col-xl-12 col-lg-6 col-12">
                                    <p><?=$order->get_meta('restaurant_note')?></p>
                                </div>
                                <?php
                            }
                        ?>

                    </div>
                </form>
            </div>
        </div>

        <!-- info customer -->
        <div class="row wrap-order">
            <div class="col-xl-12">
                <h4>Chi tiết đơn hàng</h4>
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Số lượng</th>
                        <th scope="col">Tên món</th>
                        <th scope="col">Định lượng</th>
                        <th scope="col">RK Code</th>
                        <th scope="col">SAP Code</th>
                        <th scope="col">Thành tiền</th>
                        <th>Ghi chú</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $newTotal = Order::getInsteadTotal($order);
                    $totalInstead = 0;
                    if ($order->get_items()) :
                        foreach ( $order->get_items() as $oneItem ) :
                                $modifiers = $oneItem->get_meta('modifier');
                                $parentLineItemId = $oneItem->get_meta('parentLineItemId');
                                $lineItemId = $oneItem->get_meta('lineItemId');
                                $productId = $oneItem->get_meta("instead_of_product_id_{$lineItemId}");
                                $insteadToppings = $oneItem->get_meta("instead_of_topping_id_{$lineItemId}");
                                $quantity = $oneItem->get_meta("instead_of_quantity_{$lineItemId}");
                            ?>
                            <tr <?=$parentLineItemId ? "class='isTopping'" : ""?>>
                                <td>
                                    <span><?=$oneItem->get_quantity()?></span>
                                </td>
                                <td>
                                    <?=$oneItem->get_name()?>
                                    <?php

                                        if ($modifiers) {
                                            echo '<br />';
                                            foreach ($modifiers as $modifierCat) {
                                                // get term info
                                                $term = get_term($modifierCat->categoryId);
                                                echo "<strong>{$term->name}</strong>: ";

                                                if ($modifierCat->data) {
                                                    foreach ($modifierCat->data as $oneModifier) {
                                                        // get post
                                                        if ($oneModifier->name) {
                                                            echo $oneModifier->name . "; ";
                                                        } else {
                                                            $childTerm = get_term($oneModifier->id);
                                                            echo $childTerm->name . "; ";
                                                        }
                                                    }
                                                }

                                                echo '<br />';
                                            }
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        $currentProductId = (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) ? $oneItem->get_data()['variation_id'] : $oneItem->get_data()['product_id'];
                                        $parentId = $oneItem->get_data()['product_id'];
                                    ?>
                                    <?=(get_field('product_quantitative', $currentProductId).' '.\GDelivery\Libs\Helper\Helper::productUnitText(get_field('product_unit', $currentProductId)))?>
                                </td>
                                <td>
                                    <?php if (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) { ?>
                                        <?=get_field('product_variation_rk_code', $oneItem->get_data()['variation_id'])?>
                                    <?php } else { ?>
                                        <?=get_field('product_rk_code', $oneItem->get_data()['product_id'])?>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (isset($oneItem->get_data()['variation_id']) && $oneItem->get_data()['variation_id']) { ?>
                                        <?=get_field('product_variation_sap_code', $oneItem->get_data()['variation_id'])?>
                                    <?php } else { ?>
                                        <?=get_field('product_sap_code', $oneItem->get_data()['product_id'])?>
                                    <?php } ?>
                                </td>
                                <td><?=number_format($oneItem->get_total())?>₫</td>
                                <td><?=$oneItem->get_meta("instead_of_product_note_{$lineItemId}")?></td>
                                <td class="text-right">
                                    <?php if ($order->get_status() == Order::STATUS_PENDING) { ?>
                                        <a
                                            href="javascript:void(0);"
                                            line-id="<?=$oneItem->get_meta('lineItemId')?>"
                                            merchant-id="<?=$order->get_meta('merchant_id')?>"
                                            class="note-item"
                                        >
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 2V5" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M16 2V5" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path opacity="0.4" d="M8 11H16" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path opacity="0.4" d="M8 16H12" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    <?php } ?>
                                    <?php if (
                                        $order->get_status() == Order::STATUS_PENDING
                                        && $order->get_meta('request_change_product') != 'request_submitted'
                                    ) { ?>
                                    <a
                                        product-id="<?=$parentId?>"
                                        href="javascript:void(0);"
                                        line-id="<?=$oneItem->get_meta('lineItemId')?>"
                                        merchant-id="<?=$order->get_meta('merchant_id')?>"
                                        class="switch-item"
                                    >
                                        <svg fill="#000000" width="20" height="20" viewBox="-1 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg" transform="rotate(90)">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"/>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M16.417 9.579A7.917 7.917 0 1 1 8.5 1.662a7.917 7.917 0 0 1 7.917 7.917zM4.63 8.182l2.45-2.449-.966-.966a.794.794 0 0 0-1.12 0l-1.329 1.33a.794.794 0 0 0 0 1.12zm8.23 5.961c.13 0 .206-.1.173-.247l-.573-2.542a1.289 1.289 0 0 0-.292-.532l-4.53-4.53-.634.636 4.529 4.529.252-.252a.793.793 0 0 1 .147.268l.253 1.124-.69.69-1.125-.252a.799.799 0 0 1-.268-.148l.871-.87-4.53-4.53L5.19 8.742l4.53 4.528a1.294 1.294 0 0 0 .533.293l2.542.573a.3.3 0 0 0 .066.008z"/>
                                            </g>
                                        </svg>
                                    </a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php if ($order->get_meta('is_request_change_product')) { ?>
                                <?php
                                    if ($productId) {
                                        $productInstead = "";
                                        $getProduct = \GDelivery\Libs\Helper\Product::getDetailProduct($productId);
                                        $productInstead = $getProduct->result;
                                        $totalInstead += $productInstead->salePrice ?: $productInstead->regularPrice;
                                        if ($productInstead) {
                                            $insteadModifiers = $oneItem->get_meta("instead_of_modifier_{$lineItemId}");
                                ?>
                                    <tr class="bg-danger text-white" style="opacity: .7">
                                        <td><?=$quantity?></td>
                                        <td>
                                            <?=$productInstead->name?>
                                            <?php
                                            if ($insteadModifiers) {
                                                echo '<br />';
                                                foreach ($insteadModifiers as $modifierCat) {
                                                    // get term info
                                                    $term = get_term($modifierCat->categoryId);
                                                    echo "<strong>{$term->name}</strong>: ";

                                                    if ($modifierCat->data) {
                                                        foreach ($modifierCat->data as $oneModifier) {
                                                            // get post
                                                            if ($oneModifier->name) {
                                                                echo $oneModifier->name . "; ";
                                                            } else {
                                                                $childTerm = get_term($oneModifier->id);
                                                                echo $childTerm->name . "; ";
                                                            }
                                                        }
                                                    }

                                                    echo '<br />';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td><?=(get_field('product_quantitative', $productId).' '.\GDelivery\Libs\Helper\Helper::productUnitText(get_field('product_unit', $productId)))?></td>
                                        <td><?=$productInstead->rkCode?></td>
                                        <td><?=$productInstead->sapCode?></td>
                                        <td><?=number_format($productInstead->salePrice ?: $productInstead->regularPrice * $quantity)?>₫</td>
                                        <td></td>
                                        <td class="text-right">
                                            <?php if (
                                                $order->get_status() == Order::STATUS_PENDING
                                                && $order->get_meta('request_change_product') != 'request_submitted'
                                            ) { ?>
                                            <a class="remove-product-apply" href="javascript:void(0)" rel="<?=$lineItemId?>">
                                                <svg fill="#ffffff" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" id="delete-alt" class="icon glyph"><path d="M17,4V5H15V4H9V5H7V4A2,2,0,0,1,9,2h6A2,2,0,0,1,17,4Z"></path><path d="M20,6H4A1,1,0,0,0,4,8H5V20a2,2,0,0,0,2,2H17a2,2,0,0,0,2-2V8h1a1,1,0,0,0,0-2ZM11,17a1,1,0,0,1-2,0V11a1,1,0,0,1,2,0Zm4,0a1,1,0,0,1-2,0V11a1,1,0,0,1,2,0Z"></path></svg>
                                            </a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                <?php } ?>

                                <?php
                                    if ($insteadToppings) {
                                        echo '<div><strong>Toppings:</strong></div>';
                                        foreach ($insteadToppings as $topping) {
                                            $getTopping = \GDelivery\Libs\Helper\Product::getDetailProduct($topping);
                                            $toppingInstead = $getTopping->result;
                                            $totalInstead += $toppingInstead->salePrice ?: $toppingInstead->regularPrice;
                                        }
                                ?>
                                    <?php
                                        foreach ($insteadToppings as $topping) {
                                            $getTopping = \GDelivery\Libs\Helper\Product::getDetailProduct($topping);
                                            $toppingInstead = $getTopping->result;
                                            if ($toppingInstead) {
                                    ?>
                                        <tr class="bg-danger text-white" style="opacity: .7">
                                            <td><?=$quantity?></td>
                                            <td>
                                                <?=$toppingInstead->name?>
                                            </td>
                                            <td><?=(get_field('product_quantitative', $toppingInstead->id).' '.\GDelivery\Libs\Helper\Helper::productUnitText(get_field('product_unit', $toppingInstead->id)))?></td>
                                            <td><?=$toppingInstead->rkCode?></td>
                                            <td><?=$toppingInstead->sapCode?></td>
                                            <td><?=number_format($toppingInstead->salePrice ?: $toppingInstead->regularPrice * $quantity)?>₫</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                        <?php endforeach; ?>
                        <?php if ($totals->shipping->price) :?>
                            <tr>
                                <td><span><?=$totals->shipping->price?></span></td>
                                <td>Phí vận chuyển</td>
                                <td></td>
                                <td>
                                    <?php
                                    $categoryId = $order->get_meta('current_product_category_id');
                                    $brandId = get_field('product_category_brand_id', 'product_cat_' . $categoryId);
                                    $isProductIcook = false;
                                    if ($brandId == Config::BRAND_IDS['icook']) {
                                        $isProductIcook = true;
                                    }
                                    if ($jsonRestaurant->restaurant->regionName == 'Ha Noi') {
                                        if ($isProductIcook) {
                                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_ICOOK;
                                        } else {
                                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE;
                                        }
                                    } else {
                                        if ($isProductIcook) {
                                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_ICOOK_HCM;
                                        } else {
                                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_HCM;
                                        }
                                    }
                                    echo $code;
                                    ?>
                                </td>
                                <td></td>
                                <td><?=number_format($totals->shipping->price)?>₫</td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                            <tr>
                                <td colspan="8">
                                    <?php
                                    if ($order->get_meta('use_cutlery_tool') == 1) {
                                        $strCutleryTool = 'CÓ LẤY Dao/Dĩa/Đũa/Thìa....';
                                    } else {
                                        $strCutleryTool = 'KHÔNG LẤY Dao/Dĩa/Đũa/Thìa....';
                                    }
                                    ?>
                                    <strong><?=$strCutleryTool?></strong>
                                </td>
                            </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">
                                Chưa có sản phẩm
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="text-right" colspan="7">
                            <strong class="title-order">Tổng tiền hàng</strong>
                        </td>
                        <td class="text-right"><?=number_format($totals->totalPrice)?> đ</td>
                    </tr>
                    <?php if ($order->get_meta('is_request_change_product')) { ?>
                    <tr>
                        <td class="text-right" colspan="7">
                            <strong class="title-order">Tổng tiền hàng mới</strong>
                        </td>
                        <td class="text-right"><?=number_format($newTotal->newTotalPrice)?> đ</td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td class="text-right bd-t-none" colspan="7">
                            <strong class="title-order">Giảm giá</strong>
                        </td>
                        <td class="bd-t-none text-right">
                            <?php
                                if ($listDiscountVouchers) {
                                    foreach ($listDiscountVouchers as $voucher) {
                                        if (isset($voucher->processOnPos) && $voucher->processOnPos === false) {
                                            if ($voucher->partnerId == 14) {
                                                $strVoucher = '-'.number_format($voucher->denominationValue)."đ ($voucher->code - Coupon GPP)<br /><i>Lỗi khi đẩy xuống pos, vui lòng thao tác thủ công trên POS hoặc liên hệ IT hỗ trợ</i> <br/ >";
                                            } else {
                                                $campaignName = $voucher->campaign['name'] ?? null;
                                                $strVoucher = '-'.number_format($voucher->denominationValue)."đ ($voucher->code - {$campaignName})<br /><i>Lỗi khi đẩy xuống pos, vui lòng thao tác thủ công trên POS hoặc liên hệ IT hỗ trợ</i> <br/ >";
                                            }
                                        } else {
                                            if ($voucher->partnerId == 14) {
                                                $strVoucher = '-'.number_format($voucher->denominationValue)."đ (Coupon GPP)<br />";
                                            } else {
                                                $campaignName = $voucher->campaign['name'] ?? null;
                                                $strVoucher = '-'.number_format($voucher->denominationValue)."đ ({$campaignName})<br />";
                                            }
                                        }

                                        echo $strVoucher;
                                    }
                                } else {
                                    echo '&nbsp;';
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-right bd-t-none" colspan="7">
                            <strong class="title-order">Thuế VAT</strong>
                        </td>
                        <td class="bd-t-none text-right"><?=number_format((float) $totals->totalTax)?> đ</td>
                    </tr>
                    <?php if ($order->get_meta('is_request_change_product')) { ?>
                    <tr>
                        <td class="text-right bd-t-none" colspan="7">
                            <strong class="title-order">Thuế VAT Mới</strong>
                        </td>
                        <td class="bd-t-none text-right"><?=number_format((float) $newTotal->newTotalTax)?> đ</td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="7">
                            <ul class="title-order">
                                <li>Tổng thanh toán</li>
                            </ul>
                        </td>
                        <td class="text-right">
                            <ul>
                                <li><span><?=number_format($totals->total)?> đ</span></li>
                                <li>
                                    <?php
                                        foreach ($listCashVouchers as $voucher) {
                                            if ($voucher->partnerId == 8) {
                                                $campaignName = $voucher->campaign['name'] ?? null;
                                                if (isset($voucher->processOnPos) && $voucher->processOnPos === false) {
                                                    $strVoucher = '-'.number_format($voucher->denominationValue)."đ ($voucher->code - {$campaignName})<br /> <i>Lỗi khi đẩy xuống pos, vui lòng thao tác thủ công trên POS hoặc liên hệ IT hỗ trợ</i> <br/ >";
                                                } else {
                                                    $strVoucher = '-'.number_format($voucher->denominationValue)."đ ({$campaignName})<br />";
                                                }
                                            } else {
                                                if (isset($voucher->processOnPos) && $voucher->processOnPos === false) {
                                                    $strVoucher = '-'.number_format($voucher->denominationValue)."đ ($voucher->code - {$voucher->partnerName})<br /> <i>Lỗi khi đẩy xuống pos, vui lòng thao tác thủ công trên POS hoặc liên hệ IT hỗ trợ</i> <br/ >";
                                                } else {
                                                    $strVoucher = '-'.number_format($voucher->denominationValue)."đ ({$voucher->partnerName})<br />";
                                                }
                                            }

                                            echo $strVoucher;
                                        }
                                    ?>
                                </li>
                            </ul>

                        </td>
                    </tr>
                    <tr>
                        <td colspan="7">
                            <ul class="title-order">
                                <li>Tổng tiền phải trả</li>
                            </ul>
                        </td>
                        <td class="text-right">
                            <ul>
                                <li><span><?=number_format($totals->totalPaySum)?> đ</span></li>
                            </ul>
                        </td>
                    </tr>
                    <?php if ($order->get_meta('is_request_change_product')) { ?>
                    <tr>
                        <td colspan="7">
                            <ul class="title-order">
                                <li>Tổng tiền phải trả mới</li>
                            </ul>
                        </td>
                        <td class="text-right">
                            <ul>
                                <li><span><?=number_format($newTotal->newTotalPaySum)?> đ</span></li>
                            </ul>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div class="text-right">
                    <?php
                        if (
                            $order->get_status() == Order::STATUS_REQUEST_SUPPORT
                            && $user->role == 'operator'
                        ) {
                    ?>
                        <button class="btn btn-danger btn-cancel-order">Hủy đơn</button>
                    <?php } ?>
                    <?php
                        if (
                            (
                                $user->role == 'restaurant'
                                ||  $user->role == 'administrator'
                            )
                            && $order->get_meta('request_change_product')
                            && $order->get_meta('request_change_product') == 'processing'
                            && $order->get_status() != Order::STATUS_REQUEST_SUPPORT
                        ) {
                    ?>
                        <button class="btn btn-warning btn-apply-change-item">Áp dụng thay đổi</button>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click','.remove-product-apply',function(){
            let id = $('.orderId').val();
            let lineItemId = $(this).attr('rel');
            let el = $(this);
            $.ajax({
                'type' : 'post',
                'url' : '/wp-json/api/v1/remove-apply-product',
                'dataType' : 'json',
                'data' : {
                    'id' : id,
                    'lineItemId' : lineItemId
                },
                beforeSend: function() {
                    el.html('<span class="fa fa-spinner fa-spin"></span>');
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        window.location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {

                }
            }); // end ajax
        });

        let orderId = $('.orderId').val();
        $(document).on('click','.btn-send-request-support',function(){
            let id = $('.orderId').val();
            let status = '<?=Order::STATUS_PENDING?>';
            let el = $(this);
            $.ajax({
                'type' : 'post',
                'url' : '<?=site_url('ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : id,
                    'status' : status,
                    'action': 'approve'
                },
                beforeSend: function() {
                    el.html('<span class="fa fa-spinner fa-spin"></span> Processing');
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        window.location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {

                }
            }); // end ajax
        });
        $(document).on('click','.btn-apply-change-item',function(){
            let id = $('.orderId').val();
            let status = '<?=Order::STATUS_PENDING?>';
            let el = $(this);
            $.ajax({
                'type' : 'post',
                'url' : '/wp-json/api/v1/order/make-change-item',
                'dataType' : 'json',
                'data' : {
                    'id' : id,
                    'status' : status
                },
                beforeSend: function() {
                    el.html('<span class="fa fa-spinner fa-spin"></span> Processing');
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đã cập nhật thông tin đơn hàng');
                        window.location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {

                }
            }); // end ajax
        });
        $(document).on('click','.btn-cancel-order',function(){
            $('#modal-cancel .btn-cancel-order').attr('data-order-id', orderId);
	        $('#modal-cancel').modal({
		        'show' : true,
		        'backdrop' : 'static'
	        });
        });
    });
</script>

<style>
    .wrap-status-order .wrap-action .btn-cancel, .wrap-status-order .wrap-action .btn-sbm {
        width: auto !important;
    }
</style>
<?php
get_footer('restaurant');

endif; // end if order

?>
