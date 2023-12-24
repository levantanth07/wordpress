<style>
    .dropdown-check-list {
        display: inline-block;
    }

    .dropdown-check-list .anchor {
        position: relative;
        cursor: pointer;
        display: inline-block;
        /*padding: 5px 50px 5px 10px;*/
        /*border: 1px solid #ccc;*/
    }

    .dropdown-check-list .anchor:after {
        position: absolute;
        content: "";
        border-left: 1px solid black;
        border-top: 1px solid black;
        padding: 3px;
        right: 5px;
        top: 35%;
        -moz-transform: rotate(-135deg);
        -ms-transform: rotate(-135deg);
        -o-transform: rotate(-135deg);
        -webkit-transform: rotate(-135deg);
        transform: rotate(-135deg);
    }

    .dropdown-check-list .anchor:active:after {
        right: 8px;
        top: 21%;
    }

    .dropdown-check-list ul.items {
        /*padding: 2px;*/
        display: none;
        margin: 0;
        border: 1px solid #ccc;
        border-top: none;
    }

    .dropdown-check-list ul.items li {
        list-style: none;
    }

    .dropdown-check-list.visible .items {
        display: block;
    }

    .checkbox {
        width: 20px !important;
        margin-top: 5px;
    }

    .bootstrap-datepicker-widget tr:hover {
        background-color: #808080;
    }

    .ui-datepicker table tbody .ui-datepicker-week-col {
        cursor: pointer;
        color: red;
    }
    .form-control .multiple-seleect-custom{
        max-height: 70vh;
        overflow: auto;
        z-index: 9;
        background: white;
    }
</style>
<?php
/*
Template Name: Operator List Orders
*/

$currentUser = wp_get_current_user();

if (!$currentUser) {
    header('Location: ' . site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);
//echo "<pre>";
//var_dump($user->restaurant->code);
//echo "</pre>";
//die();

if ($user->role != 'operator' && $user->role != 'restaurant' && $user->role != 'administrator') {
    header('Location: ' . site_url('wp-login.php'));
    //wp_die('Bạn không được phép truy cập trang này');
}

$bookingService = new \GDelivery\Libs\BookingService();
$tgsService = new \GDelivery\Libs\TGSService();
$listProvinces = $tgsService->getProvinces()->result;
$listRestaurants = $bookingService->getRestaurants()->result;
$listBrands = $bookingService->getBrands()->result;

// check provinces, brands are right for this operator
$currentUserRights = $currentUser->get('user_operator_rights');
$userRightObject = [];
$restaurantCode = 0;
if ($user->role != 'restaurant'){
    foreach ($currentUserRights as $provinceId => $brandIds) {
        $temp = [];
        if ($listProvinces) {
            foreach ($listProvinces as $oneProvince) {
                if ($provinceId == $oneProvince->id) {
                    $temp['province'] = $oneProvince;
                    break;
                }
            }
        } else {
            $temp['province'] = "";
        }

        if ($brandIds) {
            foreach ($brandIds as $oneBrandId) {
                foreach ($listBrands as $oneBrand) {
                    if ($oneBrandId == $oneBrand->id) {
                        $temp['brands'][] = $oneBrand;
                        break;
                    }
                }
            }
        } else {
            $temp['brands'][] = "";
        }
        $userRightObject[] = $temp;
    }
} else {
    $restaurantCode = $user->restaurant->code;
}
// current operator province_brand
$currentUserProvinceBrand = $currentUser->get('user_operator_province_brand');

$page = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$provinceBrand = isset($_GET['provinceBrand']) ? $_GET['provinceBrand'] : '';
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : date('Y-m-d');
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : date('Y-m-d');
$dayOfWeek = isset($_GET['dayOfWeek']) ? $_GET['dayOfWeek'] : date('Y-m-d');
$search = isset($_GET['search']) ? $_GET['search'] : '';

$statuses = [];
$statuses = \GDelivery\Libs\Helper\Helper::orderStatusName();

wp_reset_query();

get_header('restaurant', [
    'user' => $user
]);
?>

<main class="content">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
                        <!--<li class="breadcrumb-item"><a href="#">Quản trị danh mục</a></li>-->
                        <li class="breadcrumb-item active" aria-current="page">Xuất File</li>
                    </ol>
                </nav>
            </div>
            <div class="col-xl-12">
                <hr/>
            </div>
        </div>
        <div class="row feature">
            <div class="form-group col-xl-3 col-lg-3">
                <select class="form-control w-100" name="typeReport" id="typeReport">
                    <option value="">Loại báo cáo</option>
                    <option value="1">Báo cáo theo ngày</option>
                    <option value="2">Báo cáo theo tuần</option>
                    <option value="3">Báo cáo chi tiết đơn hàng</option>
                    <option value="4">Báo cáo doanh thu theo hình thức thanh toán</option>
                </select>
            </div>
            <?php if ($user->role != 'restaurant') :?>
            <div class="form-group col-xl-3 col-lg-3">
                <select class="form-control w-100" name="provinceBrand" id="provinceBrand">
                    <option value="">Khu vực</option>
                    <?php foreach ($userRightObject as $one) : ?>
                        <optgroup label="<?= $one['province']->name ?>">
                            <?php foreach ($one['brands'] as $oneBrand) : ?>
                                <option data-brand-id="<?= $oneBrand->id ?>"
                                        data-province-id="<?= $one['province']->id ?>"
                                        value="<?= $one['province']->id ?>_<?= $oneBrand->id ?>" <?= ($provinceBrand == "{$one['province']->id}_{$oneBrand->id}" ? 'selected' : '') ?> ><?= $oneBrand->name ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <div id="list1" class="dropdown-check-list w-100 form-control p-0" tabindex="100">
                    <span class="anchor w-100 pt-2 pl-2">Nhà hàng</span>
                    <ul class="items w-100 mt-1 pt-2">
                        <div class="position-relative multiple-seleect-custom">
                            <li class="liCheckallRestaurant">
                                <input name="" class="checkallRestaurant checkbox" value="" type="checkbox"/>Tất cả
                            </li>
                            <?php foreach ($listRestaurants as $oneRestaurant) : ?>
                                <li data-brand-id="<?= $oneRestaurant->brandId ?>"
                                    data-restaurant-code="<?= $oneRestaurant->code ?>"
                                    data-province-id="<?= $oneRestaurant->province->id ?>">
                                    <input name="selector[]" class="value-restaurant checkbox" value="<?= $oneRestaurant->code ?>" type="checkbox"/><?= $oneRestaurant->name ?>
                                </li>
                            <?php endforeach; ?>
                        </div>
                    </ul>
                </div>
            </div>
            <?php endif;?>
            <div class="form-group col-xl-3 col-lg-3">
                <div id="statusOrder" class="dropdown-check-list w-100 form-control p-0" tabindex="100">
                    <span class="anchor w-100 pt-2 pl-2">Trạng thái</span>
                    <ul class="items w-100 mt-1 pt-2">
                        <div class="position-relative multiple-seleect-custom">
                            <li class="liCheckallStatusRestaurant">
                                <input name="" class="checkallStatusRestaurant checkbox" value="" type="checkbox"/>Tất cả
                            </li>
                            <?php foreach ($statuses as $key => $value) : ?>
                                <li><input name="selector[]" class="value-status checkbox" value="<?= $key ?>"
                                           type="checkbox"/><?= $value ?></li>
                            <?php endforeach; ?>
                        </div>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row feature">
            <div class="form-group col-xl-3 col-lg-3 startDate" style="display: none">
                <input class="datetime-picker w-100" type="text" name="startDate" id="startDate" placeholder="Từ ngày"
                       value="<?= $fromDate ?>"/>
            </div>
            <div class="form-group col-xl-3 col-lg-3 endDate" style="display: none">
                <input class="datetime-picker w-100" type="text" name="endDate" id="endDate" placeholder="Đến ngày"
                       value="<?= $toDate ?>"/>
            </div>
            <div class="form-group col-xl-3 col-lg-3 dayOfWeek" style="display: none">
                <select class="w-100" name="dayOfWeek" id="dayOfWeek">
                </select>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <input class="btn btn-submit w-100" value="Xuất File" type="submit"/>
            </div>
        </div>
    </div>
    <form method="post" action="<?=site_url('report')?>" id="reportGdeli">
        <input type="hidden" name="startDate">
        <input type="hidden" name="endDate">
        <input type="hidden" name="dayOfWeek">
        <input type="hidden" name="status">
        <input type="hidden" name="dataRestaurant">
        <input type="hidden" name="typeReport">
    </form>
</main>


<?php get_template_part('content/restaurant', 'modal-process-order'); ?>

<style>
    .change-status {
        cursor: pointer;
    }
</style>
<?php
get_footer('restaurant');
?>
<script type="text/javascript">
    <?php if ($user->role != 'restaurant') :?>
    jQuery('#provinceBrand').on('change', function () {
        $('.value-restaurant').prop('checked', false);
        jQuery('.value-restaurant:checked').prop('checked', false);
        // $('#restaurant').html('');
        var provinceBrandId = Number(jQuery('#provinceBrand :selected').attr('data-brand-id'));
        var valueProvinceId = Number(jQuery('#provinceBrand :selected').attr('data-province-id'));
        jQuery("#list1 .items li").each(function () {
            var brandId = Number(jQuery(this).attr('data-brand-id'));
            var restaurantProvinceId = Number(jQuery(this).attr('data-province-id'));
            if (brandId == provinceBrandId && valueProvinceId == restaurantProvinceId) {
                jQuery(this).show();
                jQuery(this).find('input').prop( "disabled", false );
            } else {
                jQuery(this).hide();
                jQuery(this).find('input').prop( "disabled", true );
            }
        });
        $(".liCheckallRestaurant").show();
        $(".liCheckallRestaurant").find('input').prop( "disabled", false );
    });
    <?php endif;?>
    jQuery('.value-status').on('change', function () {
        if ($('.value-status').length == $('.value-status:checked').length) {
            $('.checkallStatusRestaurant').prop('checked', true);
        } else {
            $('.checkallStatusRestaurant').prop('checked', false);
        }
    });
    jQuery('.value-restaurant').on('change', function () {
        if ($('.value-restaurant').parents('li:not([style*="display: none"])').length == $('.value-restaurant:checked').length) {
            $('.checkallRestaurant').prop('checked', true);
        } else {
            $('.checkallRestaurant').prop('checked', false);
        }
    });
    jQuery('#typeReport').on('change', function () {
        var typeReport = Number(jQuery('option:selected', this).val());
        if (typeReport == 2) {
            jQuery('.startDate').hide();
            jQuery('.endDate').hide();
            jQuery('.dayOfWeek').show();
        } else {
            jQuery('.startDate').show();
            jQuery('.endDate').show();
            jQuery('.dayOfWeek').hide();
        }
    });

    jQuery('.btn-submit').click(function () {
        var typeReport = jQuery('#typeReport :selected').val();
        if (typeReport == '') {
            alert('Hãy chọn loại báo cáo');
            return;
        }
        var dataRestaurant = [];
        jQuery('.value-restaurant:checked').each(function (i) {
            dataRestaurant[i] = jQuery(this).val();
        });
        <?php if ($user->role == 'restaurant') :?>
        dataRestaurant.push(<?= $restaurantCode ?>);
        <?php endif;?>
        if (dataRestaurant.length < 1) {
            alert('Hãy chọn nhà hàng');
            return;
        }

        var dataStatus = [];
        jQuery('.value-status:checked').each(function (i) {
            dataStatus[i] = jQuery(this).val();
        });
        if (dataStatus.length < 1) {
            alert('Hãy chọn trạng thái');
            return;
        }
        // var dataRestaurant = jQuery('.value-restaurant:checked').val();
        var startDate = jQuery('#startDate').val();
        var endDate = jQuery('#endDate').val();

        var dayOfWeek = jQuery('#dayOfWeek').val();
        if (typeReport == 2) {
            startDate = dayOfWeek;
        }

        jQuery('#reportGdeli input[name="startDate"]').val(startDate);
        jQuery('#reportGdeli input[name="endDate"]').val(endDate);
        jQuery('#reportGdeli input[name="dayOfWeek"]').val(dayOfWeek);
        jQuery('#reportGdeli input[name="status"]').val(dataStatus);
        jQuery('#reportGdeli input[name="dataRestaurant"]').val(dataRestaurant);
        jQuery('#reportGdeli input[name="typeReport"]').val(typeReport);
        jQuery('#reportGdeli').submit();
    });

    $(document).ready(function () {
        var typeReport = jQuery('#typeReport :selected').val();
        if (typeReport == 2) {
            jQuery('.startDate').hide();
            jQuery('.endDate').hide();
            jQuery('.dayOfWeek').show();
        } else {
            jQuery('.startDate').show();
            jQuery('.endDate').show();
            jQuery('.dayOfWeek').hide();
        }

        <?php if ($user->role != 'restaurant') :?>
        var checkList = document.getElementById('list1');
        checkList.getElementsByClassName('anchor')[0].onclick = function (evt) {
            if (checkList.classList.contains('visible'))
                checkList.classList.remove('visible');
            else
                checkList.classList.add('visible');
        }
        <?php endif;?>

        var statusOrder = document.getElementById('statusOrder');
        statusOrder.getElementsByClassName('anchor')[0].onclick = function (evt) {
            if (statusOrder.classList.contains('visible'))
                statusOrder.classList.remove('visible');
            else
                statusOrder.classList.add('visible');
        }
    });

    Date.prototype.getWeek = function () {
        var onejan = new Date(this.getFullYear(), 0, 1);
        return Math.ceil((((this - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    };

    Date.prototype.formatDate = function () {
        return this.getFullYear()+"-"+(this.getMonth() + 1)+"-"+(this.getDate());
    };

    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
        return new Date(d.setDate(diff));
    }

    function getSunday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day;
        return new Date(d.setDate(diff));
    }

    var myDate = new Date();
    var listW = [];
    var currentD = getMonday(myDate);
    var lastDayOfWeek = getSunday(myDate);
    currentD.setDate(currentD.getDate() + 7);
    lastDayOfWeek.setDate(lastDayOfWeek.getDate() + 7);
    while(listW.length <= 54) {
        var endD = lastDayOfWeek.formatDate();
        currentD.setDate(currentD.getDate() - 7);
        lastDayOfWeek.setDate(lastDayOfWeek.getDate() - 7);
        listW.push(currentD.getWeek());
        var content = "<option value="+currentD.formatDate()+">Tuần " + (currentD.getWeek() - 1) + " : " + currentD.formatDate() + " - " + endD + "</option>";
        jQuery('#dayOfWeek').append(content);
    }
    $(".checkallRestaurant").click(function () {
        var provinceBrandId = Number(jQuery('#provinceBrand :selected').attr('data-brand-id'));
        var checked = this.checked;
        if (provinceBrandId) {
            jQuery("#list1 .items li").each(function () {
                var brandId = Number(jQuery(this).attr('data-brand-id'));
                if (brandId == provinceBrandId) {
                    jQuery(this).find('input').prop('checked', checked);
                } else {
                    jQuery(this).find('input').prop( "checked", false );
                }
            });
        } else {
            $('.value-restaurant:checkbox').not(this).prop('checked', checked);
        }
        $('.checkallRestaurant').prop('checked', checked);
    });
    $(".checkallStatusRestaurant").click(function () {
        $('.value-status:checkbox').not(this).prop('checked', this.checked);
    });
</script>

