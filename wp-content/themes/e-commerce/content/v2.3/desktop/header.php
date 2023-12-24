<?php
// Header desktop v2.3
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
$listProvinces = $bookingService->getProvinces();
if ($listProvinces->messageCode == \Abstraction\Object\Message::SUCCESS) {
    $restaurants = $listProvinces->result;
} else {
    $restaurants = [];
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


$homeBgs = [
    '/assets/images/bg-manwah.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-sumo.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-hutong.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-isushi.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-kichi.png?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-jack.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-37th.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-daruma.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-gogi.jpg?v=' . \GDelivery\Libs\Config::VERSION,
    '/assets/images/bg-yutang.jpg?v=' . \GDelivery\Libs\Config::VERSION,
];

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
    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap-grid.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap-reboot.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap-side-modals.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Muli:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&amp;display=swap" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/css/custom.css?v=<?=\uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/fonts-web.css?v=<?=\uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/icon-font.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/styles.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/custom.css?v=<?=\uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/slick.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/slick-theme.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/bootstrap-side-modals.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >

    <!-- js -->
    <script src="<?=bloginfo('template_url')?>/assets/js/jquery-3.5.1-min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/popper.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/bootstrap.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/v2.3/desktop/js/slick.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <?=get_template_part('content/v2.3/desktop/partials/js', 'common');?>

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
<div class="block-loading">
    <span class="fa fa-3x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>
</div>
<?php
get_template_part('content/tracking', 'body-code');


?>

<header>
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-lg-5">
                <div class="wrap-brand">
                    <a href="<?=site_url()?>" title="Trang chủ" class="brand">
                        <img src="<?=bloginfo('template_url')?>/assets/v2.3/desktop/images/logo.svg" alt="Gdelivery">
                    </a>
                    <div class="block-position dropdown" id="dropdown-province" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-pin"></i>
                        <div class="wrap-position">
                            <div>Giao hàng tới<span class="is-no-wrap" style="font-weight: normal;">:</span>&nbsp;<i class="icon-down is-wrap"></i></div>
                            <span id="selected-province-name">
                                <?=$selectedProvince?$selectedProvince->name:''?>
                            </span>
                        </div>
                        <i class="icon-down is-no-wrap"></i>
                    </div>
                    <!-- dropdown menu -->
                    <div class="dropdown-menu menu-province" aria-labelledby="dropdown-province">
                        <div class="arrow-up"></div>
                        <div class="block-dropdown-search">
                            <i class="icon-search"></i>
                            <input type="text" name="search-province" id="search-province" placeholder="Tìm tỉnh/thành phố">
                        </div>
                        <ul>
                            <?php
                                foreach ($restaurants as $one) :?>
                                    <li data-province-id="<?= $one->id ?>"
                                        class="<?= (($selectedProvince && ($selectedProvince->id == $one->id)) ? 'active' : '') ?>">
                                        <input type="radio" name="1"><i></i><span><?= $one->name ?></span>
                                    </li>
                                <?php
                                endforeach;
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-4">
                <div class="block-search">
                    <i class="icon-search"></i>
                    <input type="text" name="search" placeholder="Tìm kiếm">
                </div>
            </div>
            <div class="col-md-2 col-lg-3">
                <div class="block-feature-right">
                    <div class="block-cart">
                        <?php if (WC()->cart->get_cart_contents_count() == 0) : ?>
                            <i class="icon-cart"></i>
                            <span>Giỏ hàng</span>
                        <?php else: ?>
                            <a href="<?=$cartLink?>" class="item top-cart" title="Giỏ hàng">
                                <i class="icon-cart"></i>
                                <span class="animate__animated animate__rubberBand animate__fast amount-of-items"><?=WC()->cart->get_cart_contents_count()?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="block-sign">
                        <?php if (\GDelivery\Libs\Helper\User::isLogin()) : ?>
                            <a class="wrap-user" href="#" title="Người dùng"  id="btn-login" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">
                                <i class="icon-user"></i>
                                <i class="icon-down"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                                <div class="arrow-up"></div>
                                <a class="dropdown-item" href="<?=site_url('orders')?>" title="Theo đơn hàng">
                                    <i class="icon-recipe"></i>
                                    Đơn hàng<span class="number-order"><?=\GDelivery\Libs\Helper\Helper::countOnGoingOrders()?></span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item last" title="Đăng xuất" href="<?=site_url('logout')?>"><i class="icon-prototype"></i>Đăng xuất</a>
                            </div>
                        <?php else: ?>
                            <a class="wrap-user" href="#" title="Đăng nhập" id="btn-login" data-toggle="modal" data-target="#modal-login">
                                <i class="icon-user"></i>
                                <span>Đăng nhập</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>

<script type="text/javascript">
    var isWrap = function(parentElem, childElem) {
        let parentTop = parentElem.offsetTop;
        let childTop = childElem.offsetTop;

        return childTop > parentTop;
    }

    jQuery(document).ready(function () {
        let parentElem = document.getElementsByClassName('wrap-position')[0];
        let childElem = document.getElementById('selected-province-name');
        if (isWrap(parentElem, childElem)) {
            jQuery('.block-position').addClass('wrapped');
        } else {
            jQuery('.block-position').removeClass('wrapped');
        }
    });
</script>
