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

// pre-set selected province if has
if (isset($_REQUEST['provinceId'])) {
    $getProvince = $bookingService->getProvince($_REQUEST['provinceId']);
    if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
        \GDelivery\Libs\Helper\Helper::setSelectedProvince($getProvince->result);
    }
}

$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();

?>
    <!DOCTYPE html>
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
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap-grid.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap-reboot.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap-side-modals.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Muli:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&amp;display=swap" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/ico-font.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/styles.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >

        <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/fonts-web.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
        <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
        <link href="<?=bloginfo('template_url')?>/assets/mobile/css/custom.css?v=<?=\uniqid()?>" rel="stylesheet" >

        <!-- js -->
        <script src="<?=bloginfo('template_url')?>/assets/mobile/js/jquery-3.5.1-min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
        <script src="<?=bloginfo('template_url')?>/assets/mobile/js/popper.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
        <script src="<?=bloginfo('template_url')?>/assets/mobile/js/bootstrap.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>

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

$strBg = get_template_directory_uri().'/assets/images/bg-manwah-mobile.jpg?v='.\GDelivery\Libs\Config::VERSION;
?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            var cover = jQuery('#home-cover');
            var i = 2;
            var homeBgs = [
                '<?=bloginfo('template_url')?>/assets/images/bg-manwah-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-sumo-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-hutong-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-isushi-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-kichi-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-jack-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-37th-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-daruma-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-gogi-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>',
                '<?=bloginfo('template_url')?>/assets/images/bg-yutang-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>',
            ];

            cover.css('height', '500px');
            cover.css('background-size', '100% 100%');
            jQuery('.wrap-feature').css('top', '50%');
            jQuery('.wrap-brand').css('top', '41%');


            var css = 'url("'+ homeBgs[0] +'") no-repeat top center;';
            cover.css('background', css);

            setInterval(function () {
                var current = i % 8;
                css = 'url("'+ homeBgs[current] + '") no-repeat top center';
                cover.css(
                    {
                        'background' : css,
                        'transition' : '1s ease-in-out',
                        '-webkit-transition' : '1s ease-in-out',
                        '-moz-transition' : '1s ease-in-out',
                        '-o-transition' : '1s ease-in-out'
                    }
                );

                <?php if (wp_is_mobile()) :?>
                cover.css('background-size', '100% 100%');
                <?php endif; ?>
                i++;
            }, 5000);
        });
    </script>

    <div id="home-cover" class="cover-container" style="background: url(<?=$strBg?>) no-repeat top left">
        <header>
            <div class="container">
                <div class="row">
                    <div class="col-6"><h1 class="brand"><a href="<?=site_url()?>">G-delivery</a></h1></div>
                    <div class="col-6 mod">
                        <a href="<?=$cartLink?>" class="item top-cart" title="Giỏ hàng">
                            <i class="icon-cart"></i>
                            <span class="animate__animated animate__rubberBand animate__fast amount-of-items"><?=WC()->cart->get_cart_contents_count()?></span>
                        </a>
                        <div class="dropdown">
                            <?php if (\GDelivery\Libs\Helper\User::isLogin()) : ?>
                                <a class="wrap-user" href="#"  id="btn-login" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static"><i class="icon-single"></i></a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                                    <a class="dropdown-item" href="<?=site_url('orders')?>" title="Theo đơn hàng">
                                        <i class="icon-recipe"></i>
                                        Đơn hàng<span><?=\GDelivery\Libs\Helper\Helper::countOnGoingOrders()?></span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item last" href="<?=site_url('logout')?>"><i class="icon-prototype"></i>Đăng xuất</a>
                                </div>
                            <?php else: ?>
                                <a class="wrap-user" href="#" id="btn-login" data-toggle="modal" data-target="#modal-login"><i class="icon-single"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="select-province">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <button class="wrap-province" data-toggle="modal" data-target="#modal-select-province">
                            <span>Chọn thương hiệu tại</span>
                            <i class="icon-down"></i>
                            <b id="selected-province-name"><?=($selectedProvince ? $selectedProvince->name : 'Hà Nội')?></b>
                        </button>
                    </div>

                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="wrap-brand">
                            <ul id="choose-brand">
                            <?php
                            if (!$selectedProvince) {
                                $getCurrentProvince = $bookingService->detectCurrentProvinceViaIP($_SERVER['REMOTE_ADDR']);
                                if ($getCurrentProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
                                    $currentProvince = $getCurrentProvince->result;
                                    //if ($currentProvince->id != 5) {
                                    //    $currentProvince = null;
                                    //}
                                } else {
                                    $currentProvince = $bookingService->getProvince(5)->result;
                                    //$currentProvince = null;
                                }
                            } else {
                                $currentProvince = $selectedProvince;
                            }

                            if ($currentProvince) {
                                GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);

                                // get brand in province
                                $args = [
                                    'hide_empty' => true,
                                    'meta_query' => [
                                        [
                                            'key'       => 'product_category_province_id',
                                            'value'     => $currentProvince->id,
                                            'compare'   => '='
                                        ],
                                        [
                                            'key'       => 'product_category_is_show',
                                            'value'     => 1,
                                            'compare'   => '='
                                        ]
                                    ]
                                ];

                                $brands = get_terms('product_cat', $args);
                                if ($brands) {
                                    $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();

                                    foreach ($brands as $brand) :
                                        $temp = new \stdClass();
                                        $temp->logo = get_field('product_category_logo', 'product_cat_'.$brand->term_id);
                                        $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$brand->term_id);
                                        $temp->url = get_term_link($brand);
                                        $temp->name = $brand->name;
                                        $temp->id = $brand->term_id;

                                        echo "<li class='list-single-brand' data-category-id='{$temp->id}' data-category-url='{$temp->url}' data-category-name='{$temp->name}'><img src='{$temp->logoBW}' alt='{$temp->name}'></li>";
                                    endforeach; // end foreach brands
                                }
                            }
                            ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="module-step"> <h2>3 bước đơn giản để đặt hàng </h2>
                </div>
            </div>
            <div class="col-md-4 step step-first">
                <img src="<?=bloginfo('template_url')?>/assets/images/step01.svg"  alt="" />
                <h4>Chọn địa điểm</h4>
                <p>Chọn khu vực của bạn và chọn nhãn hiệu yêu thích</p>
            </div>
            <div class="col-md-4 step">
                <img src="<?=bloginfo('template_url')?>/assets/images/step02.svg" alt="" />
                <h4>Đặt hàng</h4>
                <p>Chọn món ăn bạn yêu thích </p>
            </div>
            <div class="col-md-4 step">
                <img src="<?=bloginfo('template_url')?>/assets/images/step03.svg" alt="" />
                <h4>Thanh toán</h4>
                <p>Thanh toán và chờ giao hàng </p>
            </div>
        </div>
    </div>

<img src="<?=bloginfo('template_url')?>/assets/images/bg-manwah-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0" />
<img src="<?=bloginfo('template_url')?>/assets/images/bg-sumo-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-hutong-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-kichi-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-jack-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-37th-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-gogi-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-yutang-mobile.png?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-isushi-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<img src="<?=bloginfo('template_url')?>/assets/images/bg-daruma-mobile.jpg?v=<?=\GDelivery\Libs\Config::VERSION?>" alt="home banner" width="0" height="0"/>
<!-- trick to preload image -->
<!-- Modal location alert -->
<div class="modal-msg modal fade" id="modal-location-alert" tabindex="-1" aria-labelledby="modal-location-alert" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-success">
                    <p>Bạn hãy chọn địa chỉ cụ thể để nhận được những ưu đãi tốt nhất</p>
                    <button class="choose-province">Chọn địa điểm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_template_part('content/content', 'js-netcore'); ?>
<?php get_template_part('content/modal', 'select-province'); ?>

<?php
if (!$currentProvince) {
    ?>
    <script type="text/javascript">
        jQuery('#modal-location-alert').modal(
            {
                'show' : true,
                'backdrop' : 'static'
            }
        );

        jQuery('#modal-location-alert .choose-province').click(function () {
            jQuery('#modal-location-alert').modal('hide');
            jQuery('#modal-select-province').modal(
                {
                    'show' : true,
                    'backdrop' : 'static'
                }
            );
        });
    </script>
<?php
}
?>

<script type="text/javascript">
    // process select brand
    var currentCategoryLink = '<?=(\GDelivery\Libs\Helper\Helper::getCurrentCategory() ? get_term_link(\GDelivery\Libs\Helper\Helper::getCurrentCategory()->term_id) : '')?>';
    var currentCategoryName = "<?=(\GDelivery\Libs\Helper\Helper::getCurrentCategory() ? \GDelivery\Libs\Helper\Helper::getCurrentCategory()->name : '')?>";
    // select brand
    jQuery(document).on('click', '#choose-brand .list-single-brand', function(){
        var thisElement = jQuery(this);
        var categoryId = thisElement.attr('data-category-id');
        var categoryUrl = thisElement.attr('data-category-url');
        var categoryName = thisElement.attr('data-category-name');

        //loading this
        thisElement.html('<span class="fa fa-3x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>');

        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'check_selected_brand',
                beHonest: '<?=wp_create_nonce('check_selected_brand')?>',
                categoryId : categoryId
            },
            success : function (res) {
                if (res.messageCode == 1) {
                    // fire netcore
                    if (isEnabledNetCore == 1) {
                        ncSelectedRestaurant(
                            {
                                provinceName: res.result.name
                            }
                        );
                    }

                    // redirect
                    setTimeout(
                        function () {
                            window.location = res.result.url;
                        },
                        2000
                    );
                } else if (res.messageCode == <?=\Abstraction\Object\Message::SUCCESS_WITHOUT_DATA?>) {
                    var scene = {
                        scene : 'confirm',
                        btnCancelScene : 'link',
                        btnCancelLinkAttr: ' href="' + categoryUrl + '?force=true" title="Đồng ý"',
                        btnCancelText: categoryName,
                        btnOkScene : 'link',
                        btnOkLinkAttr: ' href="' + currentCategoryLink + '" title="Tiếp tục mua"';
                        btnOkText: currentCategoryName,
                    };
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, scene);
                } else {
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            error : function (x, y, z) {
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            }
        });

        return false;
    });

    <?php
        if ($selectedProvince) {
            $checkProvince = \GDelivery\Libs\Helper\Province::checkProvinceAvailable($currentProvince->id);
            if ($checkProvince->messageCode == \Abstraction\Object\Message::GENERAL_ERROR) {
    ?>
                jQuery(document).ready(function () {
                    openModalAlert('Thông báo', "<?=$checkProvince->message?>", {scene : 'info', btnOkText: 'Đóng'})
                });
    <?php
            }
        }
    ?>
</script>
