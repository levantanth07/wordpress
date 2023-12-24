<?php
/*
Template Name: Restaurant List Orders V2
*/

use GDelivery\Libs\Helper\Order;

$tabStatus = GDelivery\Libs\Helper\RestaurantOrderList::$tabStatus;

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if ($user->role != 'restaurant' && $user->role != 'administrator') {
    wp_die('Bạn không được phép truy cập trang này');
}

get_header('restaurant', [
    'user' => $user
]);

$startDate = $_GET['startDate'] ?? date_i18n('Y-m-d');
$endDate = $_GET['endDate'] ?? date_i18n('Y-m-d');
$search = $_GET['search'] ?? '';
$currentTab = $_GET['tab'] ?? '';

?>

<main class="content">
    <div class="container">
        <div class="row feature">
            <div class="col-xl-12 col-lg-12">
                <form action="" method="get">
                    <input id="start-date" autocomplete="off" class="" type="text"  name="fromDate" placeholder="Từ ngày" value="<?=$startDate?>" />
                    <input id="end-date" autocomplete="off" class="" type="text" name="toDate" placeholder="Đến ngày" value="<?=$endDate?>" />
                    <input type="input" name="search" placeholder="Nhập mã đơn hoặc SĐT..." value="<?=$search?>" />
                    <input id="btn-search-order" class="btn btn-submit" value="Tìm" type="submit" />
                </form>
                <a href="#" class="func open-printer"><i class="icon-print"></i>In đơn hàng</a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-8 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?=site_url()?>" title="<?=bloginfo('name')?>">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý đơn hàng</li>
                    </ol>
                </nav>
            </div>
            <div class="col-xl-4 col-lg-12">
                <div class="summary">
                    <span id='total-orders'>...</span> đơn hàng
                    <i></i>
                    <span>Doanh thu: </span><span id='total-revenue' class='pl-0'>...</span> vnđ
                </div>
            </div>
        </div>
        <!-- end block info -->
        <div class="row">
            <div class="col-xl-12">
                <ul class="tab-status">
                    <?php foreach ($tabStatus as $tabStatusKey => $tabStatusList) :?>
                        <li><a data-tab="<?=$tabStatusKey?>" class="tab-<?=$tabStatusKey?> tab-item <?=$tabStatusKey == $currentTab ? 'active' : '';?>" href="#"><?=Order::$restaurantTabs[$tabStatusKey]?> <span class='tab-total-order'>0</span></a></li>
                    <?php endforeach; ?>
                    <li><a data-tab="all" class="tab-all tab-item <?='all' == $currentTab ? 'active' : '';?>" href="#">Tất cả <span class='all-order'>0</span></a></li>
                </ul>
            </div>
        </div>
        <!-- end tabs status -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="wrap-tbl">
                    <table id='order-list' class="table table-hover ">
                        <thead>
                        <tr>
                            <th scope="col" width="3%">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th scope="col">Mã đơn hàng</th>
                            <th scope="col">Thông tin khách hàng</th>
                            <th scope="col">Tổng tiền</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col" class="text-center">Vận Chuyển</th>
                            <th scope="col" class="text-center">Trạng thái</th>
                            <th scope="col" class="text-center">Hành động</th>
                        </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<link rel="stylesheet" href='<?=get_template_directory_uri()?>/assets/css/jquery.dataTables.min.css'>
<script type="text/javascript" src="<?=get_template_directory_uri()?>/assets/js/jquery.dataTables.min.js"></script>
 
<script>
    $(document).ready(function () {

        ajaxGetOrderSummary = function() {
            let startDate  = $('#start-date').val();
            let endDate  = $('#end-date').val();
            let search = $('input[name="search"]').val();
            jQuery.ajax({
                'type' : 'post',
                'url' : '/wp-admin/admin-ajax.php?action=restaurant_order_summary',
                'dataType' : 'json',
                'data' : {
                    'startDate' : startDate,
                    'endDate' : endDate,
                    'search' : search
                },
                'success' : function (res) {
                    $('#total-orders').html(res.totalOrders);
                    $('#total-revenue').html(res.totalRevenue);
                    let listTabTotalOrders = res.listTabTotalOrders;
                    for (let key in listTabTotalOrders) {
                        $('.tab-' + key).find('.tab-total-order').html(listTabTotalOrders[key]);
                    }
                    $('.all-order').html(res.totalOrders);
                },
                'error' : function (x, y, z) {
                    alert('Có lỗi xảy ra vui lòng thử lại sau');
                },
                'complete': function() {
                    
                }
            });

            return false;
        }

        ajaxGetOrderSummary();

        ordersTable = $('#order-list').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 20,
            lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]],
            ajax: {
                "url": "/wp-admin/admin-ajax.php?action=restaurant_order_list",
                "type": "POST",
                "data": function (d) {
                    d.start = d.start || 0;
                    d.length = d.length || 20;
                    let page = Math.floor(d.start / d.length) + 1;
                    let startDate  = $('#start-date').val();
                    let endDate  = $('#end-date').val();
                    let search = $('input[name="search"]').val();
                    let tab = $('.tab-status .active').attr('data-tab');
                    return $.extend({}, d, {
                        "start": d.start,
                        "length": d.length,
                        "page": page,
                        "startDate": startDate,
                        "endDate": endDate,
                        "search": search,
                        "tab": tab
                    });
                }
            },
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('tr-item');
                $(row).children('td').eq(0).addClass('td-first-column');
                let lastTd = $(row).children('td').last();
                lastTd.addClass('td-last-column');
                lastTd.css('text-align', 'center');
            },
            language: {
                "lengthMenu": "Hiển thị _MENU_ đơn hàng trên 1 trang",
                "zeroRecords": "Không có đơn hàng nào",
                "info": "Hiển thị trang _PAGE_ trên _PAGES_",
                "infoEmpty": "",
                "paginate": {
                    "next": "Trang tiếp",
                    "previous": "Trang trước"
                }
            },
            drawCallback: function(settings) {
                let api = this.api();
                let responseData = api.ajax.json();
                callbackFunction(responseData);
                setUrlParams();
            },
            columns: [
                { data: 'checkbox', orderable: false },
                { data: 'id', orderable: false },
                { data: 'customerInfo', orderable: false },
                { data: 'totalAmount', orderable: false },
                { data: 'orderTime', orderable: false },
                { data: 'transportVendor', orderable: false },
                { data: 'status', orderable: false },
                { data: 'action', orderable: false },
            ]
        });

        $('#btn-search-order').on('click', function(e) {
            e.preventDefault();
            ajaxGetOrderSummary();
            ordersTable.ajax.reload(function() {
                setUrlParams();
            });  
        });

        $('.tab-status .tab-item').on('click', function(e) {
            e.preventDefault();
            $('.tab-status .tab-item').removeClass('active');
            $(this).addClass('active');
            ordersTable.ajax.reload(function() {
                setUrlParams();
            });
        });

        setDateTimePicker(
            $('#start-date'),
            $('#end-date'),
            '<?=$startDate?>',
            '<?=$endDate?>',
        );

        function setUrlParams() {
            let params = new URLSearchParams(window.location.search);
            let listParams = {
                startDate: $('#start-date').val(),
                endDate: $('#end-date').val(),
                search: $('input[name="search"]').val(),
                tab: $('.tab-status .active').attr('data-tab')
            };
            for (let paramName in listParams) {
                params.set(paramName, listParams[paramName]);
            }
            let newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({path:newUrl}, '', newUrl);
        }

        function setDateTimePicker(startDateObject, endDateObject, startDateValue, endDateValue) {
            startDateObject.datepicker({
                format: "yyyy-mm-dd",
                weekStart: 1,
                endDate: new Date(endDateValue),
                maxViewMode: 2,
                todayBtn: true,
                language: "vi",
            });

            endDateObject.datepicker({
                format: "yyyy-mm-dd",
                weekStart: 1,
                startDate: new Date(startDateValue),
                maxViewMode: 2,
                todayBtn: true,
                language: "vi",
            });

            startDateObject.change(function() {
                let startDate = $(this).datepicker('getDate');
                endDateObject.datepicker("setStartDate", startDate);
            });

            endDateObject.change(function() {
                let endDate = $(this).datepicker('getDate');
                startDateObject.datepicker("setEndDate", endDate);
            });
        }

        function callbackFunction(responseData) {
            $('.tool-tip').popover();
            setActiveTab(responseData.currentTab);
            addOrderChangeStatusEvent();
        }

        function addOrderChangeStatusEvent() {
            jQuery('.change-status').click(function () {
                var thisElement = jQuery(this);
                var action = thisElement.attr('data-action');
                var orderId = thisElement.attr('data-order-id');
                var thisOldHtml = thisElement.html();
                var status = thisElement.attr('data-order-status');
                var statusText = thisElement.attr('data-order-status-text');
                var currentStatusText = thisElement.text();
                var orderPrice = thisElement.attr('data-order-price');
                var paymentMethod = thisElement.attr('data-payment-method');
                var extraData = thisElement.attr('data-extra-data') ? JSON.parse(thisElement.attr('data-extra-data')) : {};

                // loading
                thisElement.html('<span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

                if (action == 'changeRestaurant') {
                    thisElement.html(thisOldHtml);
                    jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                    jQuery.ajax({
                        'type' : 'get',
                        'url' : '<?=site_url('/ajax-order-detail')?>',
                        'dataType' : 'json',
                        'data' : {
                            'orderId' : orderId
                        },
                        'success' : function (res) {
                            if (res.messageCode == 1) {
                                // current restaurant
                                jQuery('#modal-change-restaurant select[name=current-restaurant]').html('<option>' + res.result.order.restaurant.name + '</option>');

                                // destination restaurant
                                var htmlDestinationRestaurant = '';
                                res.result.availableRestaurant.forEach(function (one) {
                                    if (one.restaurant.code != res.result.order.restaurant.code) {
                                        htmlDestinationRestaurant += '<option value="' + one.restaurant.code + '">' + one.name + ' ( ' + (one.restaurant.distance/1000).toFixed(1) + 'km)</option>';
                                    }
                                });
                                jQuery('#modal-change-restaurant select[name=destination-restaurant]').html(htmlDestinationRestaurant);

                                // add order id
                                jQuery('#modal-change-restaurant .btn-change-restaurant').attr('data-order-id', res.result.order.id);

                                // old html
                                thisElement.html(thisOldHtml);

                                // show modal
                                jQuery('#modal-change-restaurant').modal({
                                    'show' : true,
                                    'backdrop' : 'static'
                                });
                            } else {
                                alert(res.message);
                                // old html
                                thisElement.html(thisOldHtml);
                            }
                        },
                        'error' : function (x, y, z) {
                            // old html
                            thisElement.html(thisOldHtml);
                        }
                    }); // end ajax
                } else if (action == 'cancel') {
                    //order id
                    jQuery('#modal-cancel .btn-cancel-order').attr('data-order-id', orderId);

                    // old html
                    thisElement.html(thisOldHtml);

                    // show modal
                    jQuery('#modal-cancel').modal({
                        'show' : true,
                        'backdrop' : 'static'
                    });
                    jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                } else if (action == 'needToCancel') {

                    // order id
                    jQuery('#modal-need-to-cancel .btn-need-to-cancel').attr('data-order-id', orderId);

                    // old html
                    thisElement.html(thisOldHtml);
                    jQuery('#modal-need-to-cancel').modal(
                        {
                            'show' : true,
                            'backdrop' : 'static'
                        }
                    );
                    jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                } else if (action == 'needToTransfer') {
                    // order id
                    jQuery('#modal-need-to-transfer .btn-need-to-transfer').attr('data-order-id', orderId);

                    // old html
                    thisElement.html(thisOldHtml);
                    jQuery('#modal-need-to-transfer').modal(
                        {
                            'show' : true,
                            'backdrop' : 'static'
                        }
                    );
                    jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                } else if (action == 'complete') {
                    // order id
                    jQuery('#modal-complete .btn-complete').attr('data-order-id', orderId);
                    jQuery('#modal-complete input[name=bill-number]').val('');
                    jQuery('#modal-complete input[name=check-number]').val('');

                    // old html
                    thisElement.html(thisOldHtml);
                    jQuery('#modal-complete').modal(
                        {
                            'show' : true,
                            'backdrop' : 'static'
                        }
                    );
                } else if (action == 'vendorTransport') {
                    if (extraData['partner'] == 'grab_express') {
                        if (paymentMethod == 'COD' && orderPrice && Number(orderPrice) >= 2000000) {
                            thisElement.html(thisOldHtml);
                            return alert('Chỉ được yêu cầu Grab vận chuyển với đơn COD nhỏ hơn 2 triệu');
                        } 
                        // old html
                        thisElement.html(thisOldHtml);
                        var cf = confirm("Nhà hàng đã chắc chắn gói hàng đúng quy định?\nKích thước tối đa: 50x50x50cm\nTrọng lượng tối đa: 15kg");
                        if (cf) {
                            jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                            <?php if (get_option('google_map_service_address') == 'goong_address'): ?>
                            openModalSelectAddress(orderId);
                            <?php else: ?>
                            ajaxUpdateOrderStatus(orderId, status, '', 0, extraData);
                            <?php endif; ?>
                        }
                    } else {
                        alert('Chưa hỗ trợ vẫn chuyển ['+ extraData['partner'] +']');
                    }
                } else if (action == 'vendorScheduleTransport') {
                    var cf = confirm($.trim(currentStatusText) + ' ?');
                    if (cf) {
                        ajaxUpdateOrderStatus(orderId, status, '', 0, extraData);
                    } else {
                        thisElement.html(thisOldHtml);
                    }
                } else if (action == 'cancelVendorTransport') {
                    var cf = confirm('Thực hiện hủy ['+ extraData['partner'] +'] ?');
                    if (cf) {
                        ajaxUpdateOrderStatus(orderId, status, '', 0, extraData);
                    } else {
                        thisElement.html(thisOldHtml);
                    }
                } else if (action == 'refund') {
                    var cf = confirm('Thực hiện hoàn tiền?');

                    if (cf) {
                        refund(orderId);
                    } else {
                        thisElement.html(thisOldHtml);
                    }
                } else {
                    // old html
                    thisElement.html(thisOldHtml);
                    var cf = confirm(statusText + '?');
                    if (cf) {
                        jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                        ajaxUpdateOrderStatus(orderId, status, '', 0, extraData);
                    }
                }

                return false;
            });
        }

        function setActiveTab(tabStatus) {
            $('.tab-' + tabStatus).addClass('active');
        }

        function ajaxUpdateOrderStatus(id, status, note, restaurant, extraData = {}) {
            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : id,
                    'status' : status,
                    'note' : note,
                    'restaurant' : restaurant,
                    'extraData' : extraData
                },
                'success' : function (res) {
                    alert(res.message);
                },
                'error' : function (x, y, z) {
                    alert('Có lỗi xảy ra vui lòng thử lại sau');
                },
                'complete': function() {
                    ajaxGetOrderSummary();
                    ordersTable.ajax.reload(null, false);
                }
            });
        }
    });
</script>

<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<?php get_template_part('content/restaurant', 'search-address'); ?>

<?php
get_footer('restaurant');
?>

