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
    .form-control .multiple-select-custom{
        max-height: 70vh;
        overflow: auto;
        z-index: 9;
        background: white;
    }
    .value-restaurant {
        margin-top: 1px !important;
    }

    #list1 .items {
        width: 89% !important;
    }

    .multiple-select-custom .liCheckAllRestaurant .checkAllRestaurant {
        margin-top: 4px !important;
    }

    #statusOrder .items {
        width: 89% !important;
    }

    .multiple-select-custom .liCheckAllStatusRestaurant .checkAllStatusRestaurant {
        margin-top: 4px !important;
    }

    .value-status {
        margin-top: 4px !important;
    }

</style>
<?php

$currentUser = wp_get_current_user();

if (!$currentUser) {
    header('Location: ' . site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if ($user->role != 'administrator' && $user->role != 'marketing') {
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
$search = isset($_GET['search']) ? $_GET['search'] : '';

$statuses = [];
$statuses = \GDelivery\Libs\Helper\Helper::orderStatusName();

wp_reset_query();
?>

<main class="content">
    <div class="container">
        <div class="row">
            <div class="col-xl-12">
                <hr/>
            </div>
        </div>
        <div class="row feature">
            <div class="form-group col-xl-3 col-lg-3">
                <select class="form-control w-100" name="typeReport" id="typeReport">
                    <option value="">Loại báo cáo</option>
                    <option value="5">Báo cáo MKT tối ưu quảng cáo</option>
                    <option value="6">Báo cáo MKT quản lý bán hàng</option>
                </select>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <select class="form-control w-100" name="provinceBrand" id="provinceBrand">
                    <option value="">Khu vực</option>
                    <?php foreach ($userRightObject as $one) : ?>
                        <optgroup label="<?= $one['province']->name ?>">
                            <?php foreach ($one['brands'] as $oneBrand) : ?>
                                <option data-brand-id="<?= $oneBrand->id ?>"
                                        data-province-id="<?= $one['province']->id ?>"
                                        value="<?=$one['province']->id?>_<?=$oneBrand->id?>" <?=($provinceBrand == "{$one['province']->id}_{$oneBrand->id}" ? 'selected' : '')?> ><?=$oneBrand->name?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <div id="list1" class="dropdown-check-list w-100 form-control p-0" tabindex="100">
                    <span class="anchor w-100 pt-2 pl-2">Nhà hàng giao</span>
                    <ul class="items w-100 mt-1 pt-2 position-absolute">
                        <div class="position-relative multiple-select-custom">
                            <li class="liCheckAllRestaurant">
                                <input name="" class="checkAllRestaurant checkbox" value="" type="checkbox"/>Tất cả
                            </li>
                            <?php foreach ($listRestaurants as $oneRestaurant) : ?>
                                <?php if ($oneRestaurant->province) :?>
                                    <li data-brand-id="<?= $oneRestaurant->brandId ?>"
                                        data-restaurant-code="<?= $oneRestaurant->code ?>"
                                        data-province-id="<?= $oneRestaurant->province->id ?>">
                                        <input name="selector[]" class="value-restaurant checkbox" value="<?= $oneRestaurant->code ?>" type="checkbox"/><h6><?= $oneRestaurant->name ?></h6>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </ul>
                </div>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <div id="statusOrder" class="dropdown-check-list w-100 form-control p-0" tabindex="100">
                    <span class="anchor w-100 pt-2 pl-2">Trạng thái</span>
                    <ul class="items w-100 mt-1 pt-2 position-absolute">
                        <div class="position-relative multiple-select-custom">
                            <li class="liCheckAllStatusRestaurant">
                                <input name="" class="checkAllStatusRestaurant checkbox" value="" type="checkbox"/>Tất cả
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
            <div class="form-group col-xl-3 col-lg-3 startDate">
                <input class="datetime-picker w-100" type="text" name="startDate" id="startDate" placeholder="Từ ngày"
                       value="<?= $fromDate ?>"/>
            </div>
            <div class="form-group col-xl-3 col-lg-3 endDate">
                <input class="datetime-picker w-100" type="text" name="endDate" id="endDate" placeholder="Đến ngày"
                       value="<?= $toDate ?>"/>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <input class="w-100" type="text" name="utm" id="utm" placeholder="UTM" value=""/>
            </div>
            <div class="form-group col-xl-3 col-lg-3">
                <input class="btn btn-submit w-100" value="Xuất File" type="submit"/>
            </div>
        </div>
    </div>
    <form method="post" action="<?=site_url('report')?>" id="reportMkt">
        <input type="hidden" name="startDate">
        <input type="hidden" name="endDate">
        <input type="hidden" name="status">
        <input type="hidden" name="dataRestaurant">
        <input type="hidden" name="typeReport">
        <input type="hidden" name="utm">
    </form>
</main>

<style>
    .change-status {
        cursor: pointer;
    }
</style>
<script type="text/javascript">
    jQuery('.datetime-picker').datepicker({
        format: "yyyy-mm-dd",
        weekStart: 1,
        maxViewMode: 2,
        todayBtn: true,
        language: "vi"
    });

    jQuery('#typeReport').on('change', function () {
        var typeReport = Number(jQuery('option:selected', this).val());
        if (typeReport == 5) {
            jQuery('#provinceBrand').parents('.form-group').hide();
            jQuery('#list1').parents('.form-group').hide();
            jQuery('#utm').parents('.form-group').show();
        } else if (typeReport == 6) {
            jQuery('#provinceBrand').parents('.form-group').show();
            jQuery('#list1').parents('.form-group').show();
            jQuery('#utm').parents('.form-group').hide();
        } else {
            jQuery('#provinceBrand').parents('.form-group').show();
            jQuery('#list1').parents('.form-group').show();
            jQuery('#utm').parents('.form-group').show();
        }
    });

    jQuery('#provinceBrand').on('change', function () {
        jQuery('.value-restaurant').prop('checked', false);
        jQuery('.value-restaurant:checked').prop('checked', false);
        // jQuery('#restaurant').html('');
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
        jQuery(".liCheckAllRestaurant").show();
        jQuery(".liCheckAllRestaurant").find('input').prop( "disabled", false );
    });

    jQuery('.value-status').on('change', function () {
        if (jQuery('.value-status').length == jQuery('.value-status:checked').length) {
            jQuery('.checkAllStatusRestaurant').prop('checked', true);
        } else {
            jQuery('.checkAllStatusRestaurant').prop('checked', false);
        }
    });

    jQuery('.value-restaurant').on('change', function () {
        if (jQuery('.value-restaurant').parents('li:not([style*="display: none"])').length == jQuery('.value-restaurant:checked').length) {
            jQuery('.checkAllRestaurant').prop('checked', true);
        } else {
            jQuery('.checkAllRestaurant').prop('checked', false);
        }
    });

    jQuery('.btn-submit').click(function () {
        var typeReport = jQuery('#typeReport :selected').val();
        if (typeReport == '') {
            alert('Hãy chọn loại báo cáo');
            return;
        }

        var dataRestaurant = [];
        if (typeReport == 6) {
            jQuery('.value-restaurant:checked').each(function (i) {
                dataRestaurant[i] = jQuery(this).val();
            });
            if (dataRestaurant.length < 1) {
                alert('Hãy chọn nhà hàng');
                return;
            }
        }

        var dataStatus = [];
        jQuery('.value-status:checked').each(function (i) {
            dataStatus[i] = jQuery(this).val();
        });
        if (dataStatus.length < 1) {
            alert('Hãy chọn trạng thái');
            return;
        }

        var startDate = jQuery('#startDate').val();
        var endDate = jQuery('#endDate').val();
        var dataUtm = jQuery('#utm').val();
        jQuery('#reportMkt input[name="startDate"]').val(startDate);
        jQuery('#reportMkt input[name="endDate"]').val(endDate);
        jQuery('#reportMkt input[name="status"]').val(dataStatus);
        jQuery('#reportMkt input[name="dataRestaurant"]').val(dataRestaurant);
        jQuery('#reportMkt input[name="typeReport"]').val(typeReport);
        jQuery('#reportMkt input[name="utm"]').val(dataUtm);
        jQuery('#reportMkt').submit();
    });

    jQuery(document).ready(function () {
        var typeReport = Number(jQuery('option:selected', this).val());
        if (typeReport == 5) {
            jQuery('#provinceBrand').parents('.form-group').hide();
            jQuery('#list1').parents('.form-group').hide();
            jQuery('#utm').parents('.form-group').show();
        } else if (typeReport == 6) {
            jQuery('#provinceBrand').parents('.form-group').show();
            jQuery('#list1').parents('.form-group').show();
            jQuery('#utm').parents('.form-group').hide();
        } else {
            jQuery('#provinceBrand').parents('.form-group').show();
            jQuery('#list1').parents('.form-group').show();
            jQuery('#utm').parents('.form-group').show();
        }

        var checkList = document.getElementById('list1');
        checkList.getElementsByClassName('anchor')[0].onclick = function (evt) {
            if (checkList.classList.contains('visible'))
                checkList.classList.remove('visible');
            else
                checkList.classList.add('visible');
        }

        var statusOrder = document.getElementById('statusOrder');
        statusOrder.getElementsByClassName('anchor')[0].onclick = function (evt) {
            if (statusOrder.classList.contains('visible'))
                statusOrder.classList.remove('visible');
            else
                statusOrder.classList.add('visible');
        }
    });

    jQuery(".checkAllRestaurant").click(function () {
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
            jQuery('.value-restaurant:checkbox').not(this).prop('checked', checked);
        }
        jQuery('.checkAllRestaurant').prop('checked', checked);
    });

    jQuery(".checkAllStatusRestaurant").click(function () {
        jQuery('.value-status:checkbox').not(this).prop('checked', this.checked);
    });
</script>