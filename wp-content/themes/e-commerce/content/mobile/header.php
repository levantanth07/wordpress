<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?=bloginfo('description')?>">

    <meta property="og:description" content="<?=bloginfo('description')?>">
    <meta property="og:image" content="<?=bloginfo('template_url')?>/assets/images/gdelivery-for-social.png?v=<?=\GDelivery\Libs\Config::VERSION?>">
    <meta property="og:title" content="<?=bloginfo('name')?>">
    <meta property="og:url" content="">
    <meta property="og:type" content="ecommerce">

    <title>Giỏ hàng | <?=bloginfo('name')?></title>

    <!-- Styles CSS -->
    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap-grid.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/bootstrap-reboot.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >

    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/styles.css?v=<?=\uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/custom.css?v=<?=\uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/mobile/qrcode-reader/css/qrcode-reader.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >

    <link href="<?=bloginfo('template_url')?>/assets/mobile/css/ico-font.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Muli:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">

    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap-datepicker.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >
    <link rel="stylesheet" href="<?=bloginfo('template_url')?>/assets/css/animate.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>"/>

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

    <link href="<?=bloginfo('template_url')?>/assets/v2.3/mobile/css/fonts-web.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet" >

    <script src="<?=bloginfo('template_url')?>/assets/mobile/js/jquery-3.5.1-min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/mobile/js/popper.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/bootstrap.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/mobile/qrcode-reader/js/jsQR/qrcode-reader.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>

    <script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.vi.min.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>

    <?php get_template_part('content/tracking', 'header-code'); ?>
</head>
<body class="list">

<div class="global-loading">
    <div class="lds-ellipsis">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>

<?php get_template_part('content/tracking', 'body-code'); ?>

<!-- header -->
<div class="header-list">
    <div class="container">
        <div class="row">
            <?php
            $backLink = '';
            if (is_page('cart')) {
                $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
                if ($currentCategory) {
                    $backLink = get_term_link($currentCategory);
                } else {
                    $backLink = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : site_url();
                }
            } elseif (is_page('checkout-delivery')) {
                $backLink = site_url('cart');
            } elseif (is_page('checkout-payment')) {
                $backLink = site_url('checkout-delivery');
            } elseif(is_page('list-customer-address')) {
                if (wp_is_mobile()) {
                    $backLink = site_url('checkout-delivery');
                } else {
                    $backLink = site_url('checkout-pay-and-delivery');
                }
            } elseif(is_page('customer-address')) {
                $backLink = site_url('list-customer-address');
            } else {
                $backLink = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : site_url();
            }
            ?>
            <div class="col-lg-4 col-md-4 col-sm-4 col-4"><a href="<?=$backLink?>" onclick="" class="back-order"><i class="icon-arrow-left"></i></a></div>
            <div class="col-lg-8 col-md-8 col-sm-8 col-8">
                <div class="wrap-user">
                    <?php if (\GDelivery\Libs\Helper\User::isLogin()) : ?>
                        <a class="user" href="#" onclick="" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-single"></i></a>
                        <div class="dropdown-menu menu-user" aria-labelledby="dropdownMenuLink">
                            <a class="dropdown-item" href="<?=site_url('orders')?>" title="Theo đơn hàng">
                                <i class="icon-recipe"></i>
                                Đơn hàng<span><?=\GDelivery\Libs\Helper\Helper::countOnGoingOrders()?></span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item last" href="<?=site_url('logout')?>"><i class="icon-prototype"></i>Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <a class="user" href="#"  id="btn-login" data-toggle="modal" data-target="#modal-login"><i class="icon-single"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- end header -->
