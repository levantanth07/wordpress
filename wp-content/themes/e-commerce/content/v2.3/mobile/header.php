<?php
$currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
if ($currentCategory) {
    $cartLink = get_term_link( $currentCategory );
} else {
    $cartLink = '#';
    if (!WC()->cart->is_empty()) {
        // has product in cart
        foreach (WC()->cart->get_cart_contents() as $key => $cartItem) {
            $getTerms = wp_get_post_terms($cartItem['product_id'], 'product_cat');
            foreach ($getTerms as $oneTerm) {
                if ($oneTerm->parent) {
                    $cartLink = get_term_link( $oneTerm );
                    break;
                }
            }
            break;
        }
    }
}

$bookingService = new \GDelivery\Libs\BookingService();
$getListProvinces = $bookingService->getProvinces();
if ($getListProvinces->messageCode == \Abstraction\Object\Message::SUCCESS) {
    $listProvinces = $getListProvinces->result;
} else {
    $listProvinces = [];
}

// pre-set selected province if has and remove existing cart
if (isset($_REQUEST['provinceId'])) {
    $getProvince = $bookingService->getProvince($_REQUEST['provinceId']);
    if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
        \GDelivery\Libs\Helper\Helper::setSelectedProvince($getProvince->result);
        WC()->cart->empty_cart();
        \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
    }
} else {
    if (!\GDelivery\Libs\Helper\Helper::getSelectedProvince()) {
        $getCurrentProvince = $bookingService->detectCurrentProvinceViaIP($_SERVER['REMOTE_ADDR']);
        if ($getCurrentProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $currentProvince = $getCurrentProvince->result;
        } else {
            $currentProvince = $bookingService->getProvince(5)->result;
        }

        \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);
    }
}

$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();

?>

<html lang="vn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="description" content="<?=bloginfo('description')?>">

    <meta property="og:description" content="<?=bloginfo('description')?>">
    <meta property="og:image" content="<?=bloginfo('template_url')?>/assets/images/gdelivery-for-social.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <meta property="og:title" content="<?=bloginfo('name')?>">
    <meta property="og:url" content="">
    <meta property="og:type" content="ecommerce">

    <title><?=bloginfo('name')?></title>

    <!-- Styles CSS -->
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/bootstrap.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/bootstrap-grid.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/bootstrap-reboot.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/fonts-web.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/icon-font.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/slick.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/slick-theme.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/styles.css?v=<?=\uniqid()?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/custom.css?v=<?=\uniqid()?>" rel="stylesheet">

    <!-- js -->
    <script src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/js/jquery-3.5.1-min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/js/popper.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/js/bootstrap.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/lazyload-img.js?ver=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/js/slick.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-57x57.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="60x60" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-60x60.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-72x72.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-76x76.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="114x114" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-114x114.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="120x120" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-120x120.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-144x144.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-152x152.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-180x180.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="icon" type="image/png" sizes="192x192"  href="<?=bloginfo('template_url')?>/assets/images/favicon/android-icon-192x192.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-32x32.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-96x96.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-16x16.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <link rel="manifest" href="<?=bloginfo('template_url')?>/assets/images/favicon/manifest.json?v=<?=\GDelivery\Libs\Config::VERSION?>">

    <?php get_template_part('content/tracking', 'header-code'); ?>
</head>
<body>
<?php
get_template_part('content/tracking', 'body-code');
get_template_part('content/v2.3/mobile/partials/loading', 'page');
?>
<header>
    <div class="container">
        <div class="row mb-header">
            <div class="col-3 logo">
                <a href="<?=site_url()?>" class="brand">
                    <img src="<?=bloginfo('template_url')?>/assets/v2.3/desktop/images/logo.svg">
                </a>
            </div>
            <div class="col-6 group-position">
                <div class="block-position dropdown" id="dropdown-province" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="wrap-position">
                        <div class="title-position">
                            Giao hàng tới
                        </div>
                        <div class="block-province-selected">
                            <i class="icon-pin"></i>
                            &nbsp;
                            <span id="selected-province-name">
                                <?=($selectedProvince ? $selectedProvince->name : 'Vui lòng chọn')?>
                            </span>
                            &nbsp;
                            <i class="icon-down"></i>
                        </div>
                    </div>
                </div>
                <!-- dropdown menu -->
                <div class="dropdown-menu menu-province" aria-labelledby="dropdown-province">
                    <div class="arrow-up"></div>
                    <div class="block-dropdown-search">
                        <i class="icon-search"></i>
                        <input type="text" name="" placeholder="Tìm tỉnh/thành phố">
                    </div>
                    <ul id="list-provinces">
                        <?php foreach ($listProvinces as $province): ?>
                            <li class="each-province <?=($selectedProvince && $selectedProvince->id == $province->id ? 'active' : '')?>" data-province-id="<?=$province->id?>">
                                <i></i>
                                <span><?=$province->name?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-3" style="padding-left: 0;">
                <div class="block-feature-right">
                    <div class="block-cart <?=\GDelivery\Libs\Helper\User::isLogin() ? 'is-login' : ''?>">
                        <?php if (WC()->cart->get_cart_contents_count() == 0) : ?>
                            <i class="icon-cart" title="Giỏ hàng"></i>
                        <?php else: ?>
                            <a href="<?=$cartLink?>" class="item top-cart" title="Giỏ hàng">
                                <i class="icon-cart"></i>
                                <span class="animate__animated animate__rubberBand animate__fast amount-of-items"><?=WC()->cart->get_cart_contents_count()?></span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!\GDelivery\Libs\Helper\User::isLogin()) : ?>
                        <div class="block-sign" id="dropdown-info-user" data-toggle="modal" data-target="#modal-login">
                            <i class="icon-user"></i>
                        </div>
                    <?php else: ?>
                    <div class="block-sign" id="dropdown-info-user" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-user"></i>
                        <i class="icon-down"></i>
                    </div>
                    <!-- dropdown info user -->
                    <div class="dropdown-menu dropdown-info-user" aria-labelledby="dropdown-info-user">
                        <div class="arrow-up"></div>
                        <ul>
                            <li><a href="<?=site_url('orders')?>" title="Quản lý đơn hàng">Quản lý đơn hàng</a></li>
                            <li><a href="<?=site_url('logout')?>" title="Đăng xuất">Đăng xuất</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <div class="block-search">
                    <i class="icon-search"></i>
                    <input type="text" name="search" placeholder="Tìm kiếm" />
                </div>
            </div>
        </div>
    </div>
</header>
<!-- end header -->

<!--Reload content page when change province-->
<script type="text/javascript">
    function reloadContentPageWhenChangeProvince(action, provinceId, successCallback) {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=admin_url('admin-ajax.php')?>',
            'dataType' : 'json',
            'data' : {
                action: action,
                selectedProvinceId: provinceId
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    successCallback(res);
                } else {
                    openModalAlert('<?=__('alert.title.error', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            'error' : function (x, y, z) {
                openModalAlert('<?=__('alert.title.error', 'g-delivery')?>', '<?=__('alert.message.please_try_again_after_later', 'g-delivery')?>', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            },
            'complete': function () {
                let checkProvince = checkProvinceAvailable(provinceId);
                if (checkProvince.messageCode === "<?=\Abstraction\Object\Message::GENERAL_ERROR?>") {
                    // Show notification when province unavailable
                    jQuery(document).ready(function () {
                        openModalAlert('Thông báo', checkProvince.message, {scene : 'info', btnOkText: 'Đóng'})
                    });
                }
                hideLoadingPage();
            }
        }); // end ajax
    }
</script>

<script type="text/javascript">
    // change province
    //jQuery('ul#list-provinces li').click(function () {
    //    var thisElement = jQuery(this);
    //    var oldHtml = thisElement.html();
    //    var thisProvinceName = thisElement.children('span').html();
    //    var provinceId = thisElement.attr('data-province-id');
    //
    //    thisElement.html(oldHtml + ' <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
    //    jQuery.ajax({
    //        'type' : 'post',
    //        'url' : '<?//=admin_url('admin-ajax.php')?>//',
    //        'dataType' : 'json',
    //        'data' : {
    //            action: 'list_brands_in_province',
    //            provinceId: provinceId
    //        },
    //        'success' : function (res) {
    //            if (res.messageCode == 1) {
    //                jQuery('#selected-province-name').html(res.result.province.name);
    //
    //                <?php //if (is_front_page()) : ?>
    //                // process list brand
    //                var html = '';
    //                res.result.brands.forEach(function (one) {
    //                    html += '<div class="item list-single-brand" data-category-id=' + one.id + ' data-category-url=' + one.url + ' data-category-name=' + one.name + '><img src=' + one.logoBW + ' alt=' + one.name + '></div>';
    //                });
    //                jQuery('#choose-list-brands').html(html);
    //
    //                jQuery("#choose-list-brands").slick({
    //                    infinite: true,
    //                    slidesToShow: 5,
    //                    slidesToScroll: 5,
    //                    autoplay: true,
    //                    autoplaySpeed: 2000,
    //                    arrows:false,
    //
    //                });
    //                <?php //endif; ?>
    //
    //                // refresh list brand and product in home page
    //                refreshListProducts();
    //
    //                // fire netcore
    //                if (isEnabledNetCore == 1) {
    //                    ncSubmitAddress(
    //                        {
    //                            provinceName: res.result.province.name
    //                        }
    //                    );
    //                }
    //            } else if (res.messageCode == 407) {
    //                var scene = {
    //                    scene : 'confirm',
    //                    btnCancelScene : 'close',
    //                    btnCancelText: 'Đóng',
    //                    btnOkScene : 'link',
    //                    btnOkLinkAttr: ' href="<?//=site_url()?>//?provinceId='+ provinceId+'" title="Tiếp tục mua"',
    //                    btnOkText: 'Trang chủ',
    //                };
    //                openModalAlert(
    //                    'Thông báo',
    //                    res.message,
    //                    scene
    //                );
    //
    //            } else {
    //                openModalAlert('<?//=__('alert.title.error', 'g-delivery')?>//', res.message, {scene : 'info', btnOkText: '<?//=__('alert.btn.close', 'g-delivery')?>//'});
    //            }
    //
    //        },
    //        'error' : function (x, y, z) {
    //            openModalAlert('<?//=__('alert.title.error', 'g-delivery')?>//', '<?//=__('alert.message.please_try_again_after_later', 'g-delivery')?>//', {scene : 'info', btnOkText: '<?//=__('alert.btn.close', 'g-delivery')?>//'});
    //        },
    //        'complete': function () {
    //            jQuery('ul#list-provinces li').removeClass('active');
    //            thisElement.addClass('active');
    //            thisElement.html(oldHtml);
    //        },
    //        'beforeSend': function () {
    //            // empty list products
    //            jQuery('#list-hot-deal-product').html(' ');
    //            jQuery('#list-suggestion-product').html(' ');
    //            jQuery("#choose-list-brands").slick('unslick').html(' ');
    //            jQuery('#selected-province-name').html(thisProvinceName);
    //        }
    //    }); // end ajax
    //
    //});

    // search province
    var titleProvince = $('#modal-select-province ul li span');
    var selectorLiProvince = jQuery('#modal-select-province ul li');
    $('#search-province').on('change keyup', function () {

        var _val = $(this).val();
        if(parseInt(_val.length) >= 1){
            selectorLiProvince.hide(); // hide li province

            // do search text
            var temp = titleProvince.filter(function () {
                return removeAccents($(this).text()).toLowerCase().indexOf(removeAccents(_val.toLowerCase())) > -1;
            });

            // display result
            temp.parent().show(); // display block product
        } else {
            selectorLiProvince.show();
        }
    });

    function removeAccents(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D');
    }
</script>

<!--Check province available-->
<script type="text/javascript">
    function checkProvinceAvailable(provinceId) {
        let result = {};
        if (jQuery.inArray(parseInt(provinceId), [43, 27, 12]) !== -1) {
            result.messageCode = "<?=\Abstraction\Object\Message::GENERAL_ERROR?>";
            result.message = "<p style='text-align: center'>G-Delivery tạm dừng dịch vụ theo chỉ thị phòng chống dịch của thủ tướng chính phủ. <br />Vui lòng liên hệ số điện thoại 02473003077 để được hỗ trợ. <br />Xin lỗi Quý khách vì sự bất tiện này.</p>";
        } else {
            result.messageCode = "<?=\Abstraction\Object\Message::SUCCESS?>";
            result.message = 'Đang hoạt động';
        }

        return result;
    }
</script>