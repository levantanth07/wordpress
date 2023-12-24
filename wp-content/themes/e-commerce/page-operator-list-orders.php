<?php
/*
Template Name: Operator List Orders
*/

use GDelivery\Libs\Helper\Order;

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if (
        $user->role != 'operator'
        && $user->role != 'administrator'
        && $user->role != 'am'
        && $user->role != 'acc'
) {
    header('Location: '.site_url('wp-login.php'));
    //wp_die('Bạn không được phép truy cập trang này');
}

$bookingService = new \GDelivery\Libs\BookingService();
$tgsService = new \GDelivery\Libs\TGSService();
$listProvinces = $tgsService->getProvinces()->result;
$listRestaurants = $bookingService->getRestaurants()->result;
$listBrands = $bookingService->getBrands()->result;

// current operator province_brand
$currentUserMerchantIds = $currentUser->get('user_operator_merchant_id') ?: [];

// check provinces, brands are right for this operator
$args = [
    'post_type' => 'merchant',
    'posts_per_page'=> -1,
    'post_status' => 'publish'
];
$loop = new \WP_Query($args);
$merchants = [];
foreach ($loop->posts as $onePost) {
    if (!in_array($onePost->ID, $currentUserMerchantIds) && $user->role != 'administrator') {
        continue;
    }
    $merchants[] = [
            'name' => $onePost->post_title,
            'id' => $onePost->ID,
            'code' => get_field('restaurant_code', $onePost->ID)
    ];
}

$page = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
$status = $_GET['status'] ?? 'waiting-payment';
$merchantId = $_GET['merchantId'] ?? '';
$fromDate = $_GET['fromDate'] ?? date('Y-m-d');
$toDate = $_GET['toDate'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$arrStatus =  explode(',', $status);

$args = [
    'numberposts' => 30,
    'page' => $page,
];

$orders = [];
$totalOrders = 0;
$arrTotalOrder = [
    Order::TAB_WAITING_PAYMENT => 0,
    Order::TAB_PENDING => 0,
    Order::TAB_CONFIRMED => 0,
    Order::TAB_REQUEST_SUPPORT => 0,
    Order::TAB_PROCESSING => 0,
    Order::TAB_RESTAURANT_READY => 0,
    Order::TAB_TRANS_GOING => 0,
    Order::TAB_COMPLETED => 0,
    Order::TAB_CANCELLED => 0,
    Order::TAB_REFUNDED => 0,
];

if (!empty($merchants)) {
    $args['meta_key'] = 'merchant_id';
    if ($merchantId) {
        if (in_array($merchantId, $currentUserMerchantIds)) {
            $args['meta_value'] = $merchantId;
        }
    } else {
        $args['meta_value'] = $currentUserMerchantIds;
        $args['meta_compare'] = 'IN';
    }

    if ($fromDate && $toDate) {
        $args['date_created'] = "{$fromDate}...{$toDate}";
    }

    if ($search) {
        $args['billing_phone'] = $search;
    }

    if ($status != 'all') {
        if (isset($arrStatus)) {
            $args['status'] = $arrStatus;
        } else {
            $args['status'] = $status;
        }
    }

    $oldMemory = ini_get('memory_limit');
    ini_set("memory_limit", -1);

    if ($search && strlen($search) < 10) {
        $orders = [];
        $getOrderOne = wc_get_order($search);
        if ($getOrderOne
            && (
                in_array($getOrderOne->get_status(), explode(',', $status)))
                || $status == 'all') :
            $orders[] = $getOrderOne;
        endif;
    } else {
        $orders = wc_get_orders($args);
    }
    wp_reset_query();

// process summary
    $args2 = [
        'meta_key' => 'merchant_id',
        'meta_value' => $currentUserMerchantIds,
        'meta_compare' => 'IN',
        'numberposts' => -1,
    ];

    if ($fromDate && $toDate) {
        $args2['date_created'] = "{$fromDate}...{$toDate}";
    }

    if ($status != 'all') {
        $args['status'] = $status;
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
    if (!empty($summaryOrders)) :
        foreach ($summaryOrders as $order) {
            $orderStatus = $order->get_status();
            if ($orderStatus == Order::STATUS_WAITING_PAYMENT) {
                $tabKey = Order::TAB_WAITING_PAYMENT;
            } elseif (in_array($orderStatus, [
                Order::STATUS_PENDING,
                Order::STATUS_TRANS_ALLOCATING,
            ])) {
                $tabKey = Order::TAB_PENDING;
            } elseif ($orderStatus == Order::STATUS_CONFIRMED) {
                $tabKey = Order::TAB_CONFIRMED;
            } elseif ($orderStatus == Order::STATUS_REQUEST_SUPPORT) {
                $tabKey = Order::TAB_REQUEST_SUPPORT;
            } elseif ($orderStatus == Order::STATUS_PROCESSING) {
                $tabKey = Order::TAB_PROCESSING;
            } elseif ($orderStatus == Order::STATUS_READY_TO_PICKUP) {
                $tabKey = Order::TAB_RESTAURANT_READY;
            } elseif (in_array($orderStatus, [
                Order::STATUS_TRANS_GOING,
                Order::STATUS_CUSTOMER_REJECT,
                Order::STATUS_TRANS_REJECTED,
            ])) {
                $tabKey = Order::TAB_TRANS_GOING;
            } elseif ($orderStatus == Order::STATUS_COMPLETED) {
                $tabKey = Order::TAB_COMPLETED;
            } elseif ($orderStatus == Order::STATUS_CANCELLED) {
                $tabKey = Order::TAB_CANCELLED;
            } elseif ($orderStatus == Order::STATUS_REFUNDED) {
                $tabKey = Order::TAB_REFUNDED;
            } else {
                continue;
            }

            if (!isset($arrTotalOrder[$tabKey])) {
                $arrTotalOrder[$tabKey] = 0;
            }
            $arrTotalOrder[$tabKey]++;

            $totalOrders++;
        }
    endif;
    ini_set("memory_limit", $oldMemory);
}

$queryUri = \GDelivery\Libs\Helper\Helper::parseQueryUri();

get_header('restaurant', [
    'user' => $user
]);
?>

<main class="content">
    <div class="container">
        <div class="row feature">
            <div class="col-xl-12 col-lg-12">
                <form action="" method="get">
                    <input type="hidden" value="<?=$status?>" name="status" />
                    <select name="merchantId">
                        <option value="">Merchant</option>
                        <?php foreach ($merchants as $one) :?>
                            <option value="<?=$one['id']?>" <?=($merchantId == "{$one['id']}" ? 'selected' : '')?> ><?=$one['name']?> - <?=$one['code']?></option>
                        <?php endforeach; ?>
                    </select>
                    <!--<select>
                        <option>Nhà hàng</option>
                        <option>Kichi Láng Hạ</option>
                        <option>Kichi Lạc Long Quân</option>
                    </select>-->
                    <input class="datetime-picker" type="text"  name="fromDate" placeholder="Từ ngày" value="<?=$fromDate?>" />
                    <input class="datetime-picker" type="text" name="toDate" placeholder="Đến ngày" value="<?=$toDate?>" />
                    <input type="input" name="search" placeholder="Nhập mã đơn hoặc SĐT..." value="<?=(isset($_GET['search']) ? $_GET['search'] : '')?>" />
                    <input class="btn btn-submit" value="Tìm" type="submit" />
                </form>
            </div>
<!--            <div class="col-xl-3 col-lg-12">-->
<!--                <div class="text-right">-->
<!--                    <div class="slipt"></div>-->
<!--                    <a href="#" class="func"><i class="icon-print"></i>In đơn hàng</a>-->
<!--                    <a href="#" class="func"><i class="icon-file-down"></i>Tải xuống</a>-->
<!--                </div>-->
<!--            </div>-->
        </div>

        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-8 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
                        <!--<li class="breadcrumb-item"><a href="#">Quản trị danh mục</a></li>-->
                        <li class="breadcrumb-item active" aria-current="page">Quản lý đơn hàng</li>
                    </ol>
                </nav>
            </div>
            <div class="col-xl-4 col-lg-12">

            </div>
        </div>
        <!-- end block info -->
        <div class="row">
            <div class="col-xl-12">
                <ul class="tab-status">
                    <?php
                    if ($queryUri) {
                        $temp = clone $queryUri;
                        if (isset($temp->params['status'])) {
                            unset($temp->params['status']);
                        }

                        $statusUri = '?'.http_build_query($temp->params).'&';
                    } else {
                        $statusUri = '?';
                    }
                    ?>
                    <?php
                        foreach (Order::$arrayTabsOperator as $tabKey => $tabName):
                            if ($tabKey == Order::TAB_WAITING_PAYMENT) {
                                $statusValue = Order::STATUS_WAITING_PAYMENT;
                            } elseif ($tabKey == Order::TAB_PENDING) {
                                $statusValue = implode(',', [
                                    Order::STATUS_PENDING,
                                    Order::STATUS_TRANS_ALLOCATING,
                                ]);
                            } elseif ($tabKey == Order::TAB_REQUEST_SUPPORT) {
                                $statusValue = Order::STATUS_REQUEST_SUPPORT;
                            } elseif ($tabKey == Order::TAB_CONFIRMED) {
                                $statusValue = Order::STATUS_CONFIRMED;
                            } elseif ($tabKey == Order::TAB_PROCESSING) {
                                $statusValue = Order::STATUS_PROCESSING;
                            } elseif ($tabKey == Order::TAB_RESTAURANT_READY) {
                                $statusValue = Order::STATUS_READY_TO_PICKUP;
                            } elseif ($tabKey == Order::TAB_TRANS_GOING) {
                                $statusValue = implode(',', [
                                    Order::STATUS_TRANS_GOING,
                                    Order::STATUS_CUSTOMER_REJECT,
                                    Order::STATUS_TRANS_REJECTED,
                                ]);
                            } elseif ($tabKey == Order::TAB_COMPLETED) {
                                $statusValue = Order::STATUS_COMPLETED;
                            } elseif ($tabKey == Order::TAB_CANCELLED) {
                                $statusValue = Order::STATUS_CANCELLED;
                            } elseif ($tabKey == Order::TAB_REFUNDED) {
                                $statusValue = Order::STATUS_REFUNDED;
                            } else {
                                continue;
                            }
                    ?>
                    <li>
                        <a class="<?=($status == $statusValue ? 'active' : '')?>"
                           href="<?=site_url('operator-list-orders')?>/<?=$page?>/<?=$statusUri?>status=<?=$statusValue?>">
                            <?=$tabName?> <span><?=$arrTotalOrder[$tabKey]?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li>
                        <a class="<?=($status == 'all' ? 'active' : '')?>"
                           href="<?=site_url('operator-list-orders')?>/<?=$page?>/<?=$statusUri?>status=all">
                            Tất cả <span><?=$totalOrders?></span>
                        </a>
                    </li>
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
                            <th scope="col">Mã đơn hàng</th>
                            <th scope="col">Thông tin khách hàng</th>
                            <th scope="col">Nhà hàng</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col" class="text-center">Trạng thái</th>
                            <?php if (in_array($status, ['need-to-cancel', 'cancelled'])) :?>
                                <th scope="col" class="text-center">Lý do</th>
                            <?php endif;?>
                            <th scope="col" class="text-center">Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            if (in_array($status, ['need-to-cancel', 'cancelled'])) {
                                $colSpan = 8;
                            } else {
                                $colSpan = 7;
                            }
                        ?>
                        <?php if ($orders) :?>
                        <?php foreach ($orders as $order) :?>
                            <?php
                                if ($order->get_meta('restaurant_object')) {
                                    $jsonRestaurant = $order->get_meta('restaurant_object');
                                } else {
                                    $jsonRestaurant = $order->get_meta('restaurant_in_bachmai')->restaurant;
                                }

                                ?>
                        <tr>
                            <td data-title="#<?=$order->get_id()?>"><a href="<?=site_url('restaurant-order-detail')?>?id=<?=$order->get_id()?>" title="#<?=$order->get_id()?>">#<?=$order->get_id()?></a></td>
                            <td data-title="_info_customer">
                                <?=$order->get_shipping_first_name()?> - <span class="number"><?=$order->get_shipping_phone()?></span>
                                <a class="tool-tip" tabindex="0" role="button" data-toggle="popover" data-trigger="focus" title="<?=$order->get_shipping_first_name()?> - <?=$order->get_shipping_phone()?>" data-content="<?=$order->get_shipping_address_1()?>, <?=$order->get_shipping_address_2()?>"><i class="icon-info"></i></a>
                            </td>
                            <td data-title="_name_restaurant"><?=(isset($jsonRestaurant->name) ? $jsonRestaurant->name.' - '.$jsonRestaurant->telephone :'' )?></td>
                            <td data-title="_created_time">
                                <?=\GDelivery\Libs\Helper\Helper::textRecentOrderTime(strtotime($order->get_date_created()))?> <br />
                            </td>
                            <td data-title="_status">
                                <?php
                                // $jsonRestaurant = $order->get_meta('restaurant_object');
                                switch ($order->get_status()) {
                                    case 'processing' :
                                    case 'transport-going':
                                    case 'transport-request':
                                        $statusClass = 'alert-success';
                                        break;
                                    case 'need-to-transfer':
                                    case Order::STATUS_CANCELLED:
                                    case Order::STATUS_TRANS_REJECTED:
                                        $statusClass = 'alert-danger';
                                        break;
                                    case 'completed':
                                        $statusClass = 'alert-info';
                                        break;
                                    default :
                                        $statusClass = 'alert-warning';
                                }
                                ?>
                                <div class="alert <?=$statusClass?>" role="alert">
                                    <?=\GDelivery\Libs\Helper\Order::orderStatusName($order->get_status())?>
                                </div>
                            </td>

                            <?php if ($status == 'need-to-cancel') :?>
                                <td scope="col" class="text-center"><?=$order->get_meta('restaurant_note');?></td>
                            <?php elseif ($status == 'cancelled') :?>
                                <td scope="col" class="text-center"><?=$order->get_meta('operator_note');?></td>
                            <?php endif;?>

                            <td class="text-center">
                                <?php
                                    if (
                                        $user->role == 'administrator'
                                        && $order->get_status() != Order::STATUS_COMPLETED
                                    ) :
                                ?>
                                    <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                                        <!-- Update order -->
                                        <a class="dropdown-item update-order" data-order-id="<?=$order->get_id()?>">Cập nhật thông tin</a>
                                    </div>
                                <?php elseif (
                                      $user->role == 'operator'
                                      && (
                                          in_array(
                                              $order->get_status(),
                                              [
                                                  Order::STATUS_WAITING_PAYMENT,
                                                  Order::STATUS_REQUEST_SUPPORT,
                                              ]
                                          ) || (
                                             $order->get_status() == Order::STATUS_CANCELLED
                                             && $order->get_meta('payment_method') != 'COD'
                                             && $order->get_meta('payment_method') != 'BizAccount'
                                             && $order->get_meta('is_paid') == 1
                                             && $order->get_meta('payment_partner_transaction_id')
                                          )
                                      )
                                    ) :
                                ?>
                                    <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                                        <!-- Update order/Cancel -->
                                        <?php
                                        if (
                                            in_array(
                                                $order->get_status(),
                                                [
                                                    Order::STATUS_REQUEST_SUPPORT
                                                ]
                                            )
                                        ) : ?>
                                            <a class="dropdown-item" href="<?=site_url('restaurant-order-detail')?>?id=<?=$order->get_id()?>" title="#<?=$order->get_id()?>">Chi tiết</a>
                                        <?php endif; ?>
                                        <?php
                                        if (
                                            in_array(
                                                $order->get_status(),
                                                [
                                                    Order::STATUS_WAITING_PAYMENT
                                                ]
                                            )
                                        ) : ?>
                                            <a class="dropdown-item update-order" data-order-id="<?=$order->get_id()?>">Cập nhật thông tin</a>
                                            <a class="dropdown-item change-status" data-action="cancel" data-order-id="<?=$order->get_id()?>" >Hủy đơn hàng</a>
                                        <?php endif; ?>

                                        <!-- Refund -->
                                        <?php
                                        if (
                                            $order->get_status() == 'cancelled'
                                            && $order->get_meta('payment_method') != 'COD'
                                            && $order->get_meta('is_paid') == 1
                                            && $order->get_meta('payment_partner_transaction_id')
                                        ) : ?>
                                            <a class="dropdown-item change-status" data-action="refund" data-order-id="<?=$order->get_id()?>" >Hoàn tiền</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <th colspan="<?=$colSpan;?>">Chưa có đơn hàng</th>
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
                        $page = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
                        $currentUrl = add_query_arg( NULL, NULL ) ;
                        $queryString = isset(parse_url($currentUrl)['query']) ? '?'.parse_url($currentUrl)['query'] : '';
                        ?>

                        <li class="page-item <?=($page == 1 ? 'disabled' : '')?>">
                            <a class="page-link" href="<?=site_url('operator-list-orders')?>/<?=($page-1)?>?<?=$queryString?>" tabindex="-1" aria-disabled="true"><span aria-hidden="true">&laquo;</span></a>
                        </li>

                        <?php if ($page == 1) :?>
                            <li class="page-item active"" aria-current="page">
                                <a class="page-link" href="<?=site_url('operator-list-orders')?>/<?=$queryString?>">1 <span class="sr-only">(current)</span></a>
                            </li>
                            <li class="page-item"><a class="page-link" href="<?=site_url('operator-list-orders')?>/page/2/<?=$queryString?>">2</a></li>
                            <li class="page-item"><a class="page-link" href="<?=site_url('operator-list-orders')?>/page/3/<?=$queryString?>">3</a></li>

                        <?php else: ?>
                            <li class="page-item"><a class="page-link" href="<?=site_url('operator-list-orders')?>/page/<?=($page-1)?>/?<?=$queryString?>"><?=($page-1)?></a></li>

                            <li class="page-item active" aria-current="page">
                                <a class="page-link" href="<?=site_url('operator-list-orders')?>/<?=$queryString?>"><?=$page?> <span class="sr-only">(current)</span></a>
                            </li>

                            <li class="page-item"><a class="page-link" href="<?=site_url('operator-list-orders')?>/page/<?=($page+1)?>/<?=$queryString?>"><?=($page+1)?></a></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?=site_url('operator-list-orders')?>/page/<?=($page+1)?>/<?=$queryString?>"> <span aria-hidden="true">&raquo;</span></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>


<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<style>
    .change-status {
        cursor: pointer;
    }
    .update-order {
        cursor: pointer;
    }
</style>
<?php
    get_footer('restaurant');
?>

