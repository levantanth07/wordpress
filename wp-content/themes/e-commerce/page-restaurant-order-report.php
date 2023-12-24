<?php
/*
Template Name: Restaurant Order Report
*/

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$currentUser = wp_get_current_user();

$user = Permission::checkCurrentUserRole($currentUser);

if ($user->role == 'restaurant') {
    wp_die('Bạn không được phép truy cập trang này');
}

$bookingService = new \GDelivery\Libs\BookingService();
$tgsService = new \GDelivery\Libs\TGSService();
$listRestaurants = $bookingService->getRestaurants()->result;
$arrRestaurants = [];
$arrListCode = [];
$regionListRestaurants = [];
$regionBrandListRestaurants = [];

$currentUserRestaurantCodes = $currentUser->get('user_operator_restaurant_code');

foreach ($listRestaurants as $oneRestaurant) {
    if ($user->role == 'am' || $user->role == 'acc') {

        if (!empty($currentUserRestaurantCodes) && in_array($oneRestaurant->code, $currentUserRestaurantCodes)) {
            $arrRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name][] = $oneRestaurant;

            if (isset($regionListRestaurants[$oneRestaurant->regionName])) {
                $regionListRestaurants[$oneRestaurant->regionName] .= $oneRestaurant->code . ',';
            } else {
                $regionListRestaurants[$oneRestaurant->regionName] = $oneRestaurant->code . ',';
            }

            if (isset($regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name])) {
                $regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name] .= $oneRestaurant->code . ',';
            } else {
                $regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name] = $oneRestaurant->code . ',';
            }
        }

    } else {
        $arrRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name][] = $oneRestaurant;

        if (isset($regionListRestaurants[$oneRestaurant->regionName])) {
            $regionListRestaurants[$oneRestaurant->regionName] .= $oneRestaurant->code . ',';
        } else {
            $regionListRestaurants[$oneRestaurant->regionName] = $oneRestaurant->code . ',';
        }

        if (isset($regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name])) {
            $regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name] .= $oneRestaurant->code . ',';
        } else {
            $regionBrandListRestaurants[$oneRestaurant->regionName][$oneRestaurant->brand->name] = $oneRestaurant->code . ',';
        }
    }
}

$params = [];

$restaurants = isset($_REQUEST['restaurants']) && $_REQUEST['restaurants'] ? explode(',', trim($_REQUEST['restaurants'],',')) : '';
$fromDate = $_REQUEST['fromDate'] ?? date_i18n('Y-m-d');
$toDate = $_REQUEST['toDate'] ?? date_i18n('Y-m-d');
$fromDateDelivery = $_REQUEST['fromDateDelivery'] ?? null;
$toDateDelivery = $_REQUEST['toDateDelivery'] ?? null;
$selectedStatus = $_REQUEST['status'] ?? 'all';
$numberPerPage = $_REQUEST['numberPerPage'] ?? 1000;
$restaurantName = $_REQUEST['restaurantName'] ?? '';

$args = [];

// todo add logic to check restaurant_code is exist
$metaQuery = [];
if ($restaurants) {
    $metaQuery[] = [
        'key' => 'restaurant_code',
        'value' => $restaurants,
        'compare' => 'IN'
    ];
} else if (
        $user->role == 'am'
        || $user->role == 'acc'
) {
    $metaQuery[] = [
        'key' => 'restaurant_code',
        'value' => $currentUserRestaurantCodes,
        'compare' => 'IN'
    ];
}

if ($fromDate && $toDate) {
    $args['date_created'] = "{$fromDate}...{$toDate}";
}

if ($fromDateDelivery && $toDateDelivery) {
    $metaQuery[] = [
        'key' => 'delivery_date',
        'value' => [
            date("d/m/Y", strtotime($fromDateDelivery)),
            date("d/m/Y", strtotime($toDateDelivery))
        ],
        'compare' => 'BETWEEN'
    ];
}
$args['meta_query'] = $metaQuery;
if ($selectedStatus && $selectedStatus != 'all') {
    $args['status'] = $selectedStatus;
}

$args['posts_per_page'] = $numberPerPage;
$args['parent'] = 0;

$oldMemory = ini_get('memory_limit');
ini_set("memory_limit", -1);
$orders = wc_get_orders($args);
wp_reset_query();
ini_set("memory_limit", $oldMemory);
get_header('restaurant', [
    'user' => $user
]);
?>

<link rel="stylesheet" href="<?=bloginfo('template_url')?>/assets/css/jquery.dataTables.css?v=<?=\GDelivery\Libs\Config::VERSION?>" />
<link rel="stylesheet" href="<?=bloginfo('template_url')?>/assets/css/responsive.dataTables.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" />
<link rel="stylesheet" href="<?=bloginfo('template_url')?>/assets/css/buttons.dataTables.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" />

<main class="content">
    <div class="container-fluid">
        <div class="row feature feature-group">
            <div class="col-xl-12 col-lg-12">
                <form action="" id="filterReport" method="POST">
                    <div class="form-group">
                        <div><label for="">Nhà hàng</label></div>
                        <select name="restaurants" class="restaurantList">
                            <option value="">Khu vực</option>
                            <?php foreach ($arrRestaurants as $regionName => $oneRegion) :?>
                                <option <?= ($restaurantName == $regionName) ? "selected" : "" ?> data-name="<?=$regionName?>" value="<?=$regionListRestaurants[$regionName]?>"><?=$regionName?></option>
                                <?php foreach ($oneRegion as $brandName => $oneBrand) :?>
                                    <option <?= ($restaurantName == $brandName) ? "selected" : "" ?> data-name="<?=$brandName?>" value="<?=$regionBrandListRestaurants[$regionName][$brandName]?>">--<?=$brandName?></option>
                                    <?php foreach ($oneBrand as $oneRestaurant) :?>
                                    <option <?= ($restaurantName == $oneRestaurant->code) ? "selected" : "" ?> data-name="<?=$oneRestaurant->code?>" value="<?=$oneRestaurant->code?>">-----(<?=$oneRestaurant->code?>) <?=$oneRestaurant->name?></option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="restaurantName" class="restaurantName">
                    </div>
                    <!--<select>
                        <option>Nhà hàng</option>
                        <option>Kichi Láng Hạ</option>
                        <option>Kichi Lạc Long Quân</option>
                    </select>-->
                    <div class="form-group">
                        <div><label for="">Ngày tạo</label></div>
                        <input class="datetime-picker" type="text"  name="fromDate" placeholder="Từ ngày" value="<?=$fromDate?>" />
                        <input class="datetime-picker" type="text" name="toDate" placeholder="Đến ngày" value="<?=$toDate?>" />
                    </div>

                    <div class="form-group">
                        <div><label for="">Ngày nhận</label></div>
                        <input class="datetime-picker" type="text"  name="fromDateDelivery" placeholder="Từ ngày" value="<?=$fromDateDelivery?>" />
                        <input class="datetime-picker" type="text" name="toDateDelivery" placeholder="Đến ngày" value="<?=$toDateDelivery?>" />
                    </div>

                    <div class="form-group">
                        <div><label for="">Trạng thái</label></div>
                        <select name="status">
                            <option value="all" <?=($selectedStatus == 'all' ? 'selected' : '')?> >Trạng thái</option>
                            <?php foreach (\GDelivery\Libs\Helper\Order::orderStatusName('all') as $status => $text) :?>
                                <option value="<?=$status?>" <?=($selectedStatus == $status ? 'selected' : '')?> ><?=$text?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <div><label for="">Hiển thị</label></div>
                        <select name="numberPerPage">
                            <option value="1000" <?=($numberPerPage == 1000 ? 'selected' : '')?> >1000</option>
                            <option value="-1" <?=($numberPerPage == -1 ? 'selected' : '')?> >Không giới hạn</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div><label for="">&nbsp;</label></div>
                        <input class="btn btn-submit" value="Tìm" type="submit" />
<!--                        <input class="btn btn-submit" value="Export" type="button" id="exportReportSale"/>-->
                        <input class="btn btn-submit" value="Export Detail" type="button" id="exportReportDetail"/>
                    </div>
                    <div class="clearfix"></div>
                </form>
            </div>
            <div class="col-xl-12 col-lg-12 hidden">
                <div class="text-right">
                    <div class="slipt"></div>
                    <!--<a href="#" class="func"><i class="icon-print"></i>In đơn hàng</a>
                    <a href="#" class="func"><i class="icon-file-down"></i>Tải xuống</a>-->
                </div>
            </div>
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

        <!-- end tabs status -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="wrap-tbl">
                    <table class="table table-hover cell-border display nowrap" id="table2excel" style="width:100%">
                        <thead>
                        <tr>
                            <th>
                                #
                            </th>
                            <th>Mã đơn hàng</th>
                            <th>Kênh bán</th>
                            <th>SAP CODE</th>
                            <th>Brand</th>
                            <th>Nhà hàng</th>
                            <th>Tỉnh thành</th>
                            <th>Tên khách hàng</th>
                            <th>Số điện thoại nhận hàng</th>
                            <th>Số điện thoại đặt hàng</th>
                            <th>Địa chỉ nhận hàng</th>
                            <th>Thời gian</th>
                            <th>Bill</th>
                            <th>Check</th>
                            <th>Tiền hàng trước thuế</th>
                            <th>Phí ship(Trước thuế)</th>
                            <th>Giảm giá</th>
                            <th>Tổng VAT</th>
                            <th>Thanh toán</th>
                            <th>Voucher tiền mặt</th>
                            <th>Tiền phải trả</th>
                            <th>Chương trình KM</th>
                            <th>Tên voucher tiền mặt</th>
                            <th>Phương thức thanh toán</th>
                            <th>Mã yêu cầu thanh toán</th>
                            <th>Mã giao dịch đối tác</th>
                            <th>Đối tác vận chuyển</th>
                            <th>Khoảng cách</th>
                            <th>Phí Ship</th>
                            <th>TG KH gửi đơn</th>
                            <th>TG KH muốn nhận</th>
                            <th>TG NH xác nhận lần đầu tiên</th>
                            <th>TG CS xác nhận final</th>
                            <th>TG NH xác nhận final</th>
                            <th>TG NH xác nhận đơn đã chuẩn bị xong</th>
                            <th>TG shipper đồng ý nhận đơn</th>
                            <th>TG shipper đi giao từ nhà hàng</th>
                            <th>TG shipper giao tới nơi</th>
                            <th>TG hoàn thành</th>
                            <th>TG hủy</th>
                            <th>Mã referral</th>
                            <th>Lấy hóa đơn</th>
                            <th>Lý do hủy</th>
                            <th>Trạng thái đơn hàng</th>
                            <?php if (in_array($user->role, ['administrator', 'marketing'])): ?>
                            <th>Utm Source</th>
                            <th>Utm Medium</th>
                            <th>Utm Campaign</th>
                            <th>Utm Content</th>
                            <th>Utm Location</th>
                            <th>Utm Term</th>
                            <th>Thời gian click lần gần nhất</th>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($orders) :
                            $i = 1;
                            ?>
                        <?php foreach ($orders as $order) :
                            $utm = $order->get_meta('utm_data');
                            if (!$order->get_meta('restaurant_code')) {
                                continue;
                            };
                                $totals = \GDelivery\Libs\Helper\Helper::orderTotals($order);
                                $referrals = $order->get_meta('mo_utm_data');
                                $jsonRestaurant = $order->get_meta('restaurant_object');
                                $rkOrder = $order->get_meta('rkOrder');
                                if ($rkOrder) {
                                    if (is_array($rkOrder) && isset($rkOrder['billNumber'], $rkOrder['checkNumber'])) {
                                        $billNumber = $rkOrder['billNumber'];
                                        $checkNumber = $rkOrder['checkNumber'];
                                    } elseif(is_object($rkOrder) && isset($rkOrder->billNumber, $rkOrder->checkNumber)) {
                                        $billNumber = $rkOrder->billNumber;
                                        $checkNumber = $rkOrder->checkNumber;
                                    } else {
                                        $billNumber = '';
                                        $checkNumber = '';
                                    }
                                } else {
                                    $billNumber = '';
                                    $checkNumber = '';
                                }

                                // count cash voucher
                                $selectedVouchers = $order->get_meta('selected_vouchers');
                                $listCashVouchers = [];
                                $listDiscountVouchers = [];
                                $campaignNameBeforeVAT = [];
                                $campaignNameAfterVAT = [];
                                if ($selectedVouchers) {
                                    foreach ($selectedVouchers as $one) {
                                        $tempName = '';
                                        if (is_array($one->campaign) && isset($one->campaign['name'])) {
                                            $tempName = $one->campaign['name'];
                                        } elseif (is_object($one->campaign) && $one->campaign->name) {
                                            $tempName = $one->campaign->name;
                                        }

                                        if ($one->type == 1) {
                                            $listCashVouchers[] = $one;
                                            if (!empty($tempName)) {
                                                $campaignNameBeforeVAT[] = $tempName;
                                            }
                                        } else {
                                            $listDiscountVouchers[] = $one;
                                            if (!empty($tempName)) {
                                                $campaignNameAfterVAT[] = $tempName;
                                            }
                                        }
                                    }
                                }

                                //$term_id = $order->get_meta('current_product_category_id');

                            ?>
                        <tr>
                            <td><?=$i++?></td>
                            <td><a>#<?=$order->get_id()?></a></td>
                            <td><?=$order->get_meta('sale_channel')?></td>
                            <td><?=$jsonRestaurant->sapCode?></td>
                            <td><?=$order->get_meta('brands')?></td>
                            <td><?=$jsonRestaurant->name?></td>
                            <td><?=$jsonRestaurant->province->name ?: ''?></td>
                            <td><?=$order->get_shipping_first_name()?></td>
                            <td><?=$order->get_shipping_phone()?></td>
                            <td>
                                <?=isset($order->get_meta('customer_info')->cellphone) ? $order->get_meta('customer_info')->cellphone : $order->get_shipping_phone()?>
                            </td>
                            <td>
                                <?=$order->get_shipping_address_1()?>, <?=$order->get_shipping_address_2()?>
                            </td>
                            <td>
                                <?=date_i18n('d/m/y H:i:s', strtotime($order->get_date_created()) + 7 * 3600)?>
                            </td>
                            <td><?=$billNumber?></td>
                            <td><?=$checkNumber?></td>
                            <td><?=number_format($order->get_subtotal())?></td>
                            <td><?=number_format((float)$order->get_meta('shipping_price'))?></td>
                            <td>
                                <?php
                                $cashVouchers = 0;
                                foreach ($listCashVouchers as $voucher) {
                                    $cashVouchers += $voucher->denominationValue;
                                }
                                echo number_format($cashVouchers);
                                ?>
                            </td>
                            <td>
                                <?=$order->get_cart_tax() ? number_format($order->get_cart_tax()) : 0?>
                            </td>
                            <td>
                                <?=number_format($order->get_total())?>
                            </td>
                            <td>
                                <?php
                                $discountVouchers = 0;
                                foreach ($listDiscountVouchers as $voucher) {
                                    $discountVouchers += $voucher->denominationValue;
                                }
                                echo number_format($discountVouchers);
                                ?>
                            </td>
                            <td>
                                <?=number_format($totals->totalPaySum)?> đ
                            </td>
                            <td>
                                <?=!empty($campaignNameBeforeVAT) ? implode(", ", $campaignNameBeforeVAT) : ''?>
                            </td>
                            <td>
                                <?=!empty($campaignNameAfterVAT) ? implode(", ", $campaignNameAfterVAT) : ''?>
                            </td>
                            <td><?=$order->get_meta('payment_method')?></td>
                            <td><?=$order->get_meta('payment_request_id')?></td>
                            <td><?=$order->get_meta('payment_partner_transaction_id')?></td>
                            <td>
                                <?=($order->get_meta('vendor_transport') == 'grab_express' ? 'Grab Express' : 'Nhà hàng')?>
                            </td>
                            <td>
                                <?=number_format(round($jsonRestaurant->distance/1000, 2), 2, '.', ',')?>km
                            </td>
                            <td>
                                <?php if ($order->get_meta('vendor_transport') == 'grab_express') { ?>
                                    <?=$order->get_meta('actual_shipping_fee') ? number_format($order->get_meta('actual_shipping_fee')) : '0₫'?>
                                <?php } ?>
                            </td>
                            <td>
                                <?=$order->get_meta('order_success_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('order_success_time'))) : ''?>
                            </td>
                            <td><?=$order->get_meta('delivery_date')?> <?=$order->get_meta('delivery_time')?></td>
                            <td>
                                <?=$order->get_meta('restaurant_processing_first_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('restaurant_processing_first_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('cs_processing_last_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('cs_processing_last_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('restaurant_processing_last_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('restaurant_processing_last_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('restaurant_complete_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('restaurant_complete_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('shipper_accept_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('shipper_accept_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('trans_going_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('trans_going_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('trans_complete_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('trans_complete_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('completed_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('completed_time'))) : ''?>
                            </td>
                            <td>
                                <?=$order->get_meta('cancelled_time') ? date_i18n('d/m/y H:i:s', strtotime($order->get_meta('cancelled_time'))) : ''?>
                            </td>
                            <td>
                                <?php if (isset($referrals->isSuccess) && $referrals->isSuccess != '') { ?>
                                    <?php
                                    switch ($referrals->isSuccess) {
                                        case 1:
                                            $masOfferClass = 'alert-info';
                                            $masOfferText = 'Pending';
                                            break;

                                        case 2:
                                            $masOfferClass = 'alert-success';
                                            $masOfferText = 'Success';
                                            break;

                                        case 3:
                                            $masOfferClass = 'alert-danger';
                                            $masOfferText = 'Failed';
                                            break;

                                        default :
                                            $masOfferClass = 'alert-info';
                                            $masOfferText = 'Pending';
                                    }
                                    ?>
                                    <div class="alert <?=$masOfferClass?>" role="alert">
                                        MasOffer_<?=$masOfferText?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td>
                                <?php
                                $customerInvoice = $order->get_meta('customer_invoice');
                                echo isset($customerInvoice['name']) && $customerInvoice['name'] ? 'Có' : 'Không';
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($order->get_status() == 'cancelled') {
                                    if ($order->get_meta('customer_note')) {
                                        echo $order->get_meta('customer_note');
                                    } else {
                                        echo $order->get_meta('operator_note') ? $order->get_meta('operator_note') : $order->get_meta('restaurant_note');
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $jsonRestaurant = $order->get_meta('restaurant_object');
                                switch ($order->get_status()) {
                                    case 'processing' :
                                    case 'transport-going':
                                    case 'transport-request':
                                        $statusClass = 'alert-success';
                                        break;
                                    case 'need-to-transfer':
                                    case 'cancelled':
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
                            <?php if (in_array($user->role, ['administrator', 'marketing'])) { ?>
                                <td><?=$utm->utmSource?></td>
                                <td><?=$utm->utmMedium?></td>
                                <td><?=$utm->utmCampaign?></td>
                                <td><?=$utm->utmContent?></td>
                                <td><?=$utm->utmLocation?></td>
                                <td><?=$utm->utmTerm?></td>
                                <td></td>
                            <?php } ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end table -->

        </div>
    </div>
</main>


<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<style>
    .change-status {
        cursor: pointer;
    }
    th {
        border-top: 1px solid #dddddd;
        border-bottom: 1px solid #dddddd;
        border-right: 1px solid #dddddd;
    }
    th:first-child {
        border-left: 1px solid #dddddd;
    }
</style>
<script src="<?=bloginfo('template_url')?>/assets/js/jquery.dataTables.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/dataTables.responsive.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/dataTables.buttons.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/jszip.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/buttons.html5.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
<script>
    $('#table2excel').DataTable( {
	    scrollX: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                title: 'Báo cáo đơn hàng nhà hàng ' + $('input[name="fromDate"]').val() + '_' + $('input[name="toDate"]').val()
            }
        ],
        language: {
            lengthMenu: "Display _MENU_ records per page",
            zeroRecords: "Không có kết quả",
            info: "Trang _PAGE_ / _PAGES_",
            infoEmpty: "Không có kết quả",
            infoFiltered: "(đã tìm từ _MAX_ kết quả)",
            search: "Tìm kiếm:",
        },
        title: '',
    } );
    $("#exportReportSale").click(function(){
        var d = new Date();
        // $("#table2excel").table2excel({
        //     // exclude CSS class
        //     exclude: ".noExl",
        //     name: "Báo cáo",
        //     filename: "Báo cáo bán hàng " + d.getDate() + "/"  + d.getMonth() + "/"  + d.getFullYear() + " "  + d.getHours() + ":"  + d.getMinutes() + "-" , //do not include extension
        //     fileext: ".xls" // file extension
        // });
    });
    $("#exportReportDetail").click(function(){
        let restaurant = $('select[name="restaurants"]').val();
        let fromDate = $('input[name="fromDate"]').val();
        let toDate = $('input[name="toDate"]').val();
        let fromDateDelivery = $('input[name="fromDateDelivery"]').val();
        let toDateDelivery = $('input[name="toDateDelivery"]').val();
        let status = $('select[name="status"]').val();
        //let url = '/restaurant-export-order-detail?restaurant=' + restaurant + '&fromDate=' + fromDate + '&toDate=' + toDate + '&fromDateDelivery=' + fromDateDelivery + '&toDateDelivery=' + toDateDelivery + '&status=' + status;
        //window.open(url, '_blank');
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=admin_url('admin-ajax.php')?>',
            'dataType' : 'json',
            'data' : {
                action: 'export_report_order_detail',
                beHonest: '<?=wp_create_nonce('export_report_order_detail')?>',
                'restaurant': restaurant,
                'fromDate': fromDate,
                'toDate': toDate,
                'fromDateDelivery': fromDateDelivery,
                'toDateDelivery': toDateDelivery,
                'status': status
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    alert('Request download đang được thực thi, link download sẽ được gủi về mail khi hoàn tất.');
                } else {
                    alert(res.message);
                }
            },
            'error' : function (x, y, z) {
                alert('Lỗi khi gọi ajax');
            }
        }); // end ajax
    });
    $('.restaurantList').on('change', function (e) {
        $(".restaurantName").val($(".restaurantList option:selected").attr("data-name"));
    });
</script>
<?php
get_footer('restaurant');
?>

