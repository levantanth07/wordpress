<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>CMS TGS App</title>
    <!-- Styles CSS -->
    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/bootstrap.css" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/bootstrap-grid.css" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/bootstrap-reboot.css" rel="stylesheet" >

    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/styles.css?ver=<?=uniqid()?>" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/ico-font.css" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/restaurant/css/custom.css" rel="stylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">

    <link href="<?=bloginfo('template_url')?>/assets/css/bootstrap-datepicker.min.css" rel="stylesheet" >

    <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css" rel="stylesheet" >
    <link href="<?=bloginfo('template_url')?>/assets/v2.3/desktop/css/fonts-web.css?v=<?=\uniqid()?>" rel="stylesheet" >
<!--    <link href="--><?//=bloginfo('template_url')?><!--/assets/v2.3/desktop/css/icon-font.css?v=--><?//=\GDelivery\Libs\Config::VERSION?><!--" rel="stylesheet">-->

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?=bloginfo('template_url')?>/assets/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="<?=bloginfo('template_url')?>/assets/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?=bloginfo('template_url')?>/assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?=bloginfo('template_url')?>/assets/images/favicon/manifest.json">

    <script src="<?=bloginfo('template_url')?>/assets/js/jquery-3.5.1-min.js" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/popper.min.js" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/bootstrap.js" type="text/javascript"></script>
    <script src="<?=bloginfo('template_url')?>/assets/js/jquery.table2excel.js?v=<?=\GDelivery\Libs\Config::VERSION?>" type="text/javascript"></script>

    <style type="text/css">
        .navbar-nav.menu li {
            padding: 8px;
        }
        .navbar-nav.menu li a {
            color: #bcd5f3;
        }
        .navbar-nav.menu li.active a {
            color: #fff;
            font-weight: 700;
        }
    </style>
</head>
<body>
<?php
global $wp;
$currentUrl = home_url( $wp->request );
?>
<!-- header -->
<header>
    <nav class="navbar navbar-expand-md fixed-top  ">
        <a class="navbar-brand" href="#"><img src="<?=bloginfo('template_url')?>/assets/restaurant/images/logo.png"/></a>
        <ul class="navbar-nav menu">
            <?php if (isset($args['user']) && ($args['user']->role == 'operator' || $args['user']->role == 'administrator')) :?>
                <li class="<?=strpos($currentUrl, 'operator-list-orders') ? 'active' : ''?>">
                    <a href="<?=site_url('operator-list-orders')?>">Quản lý vận đơn</a>
                </li>
                <li class="<?=strpos($currentUrl, 'restaurant-order-report') ? 'active' : ''?>">
                    <a href="<?=site_url('restaurant-order-report')?>">Báo cáo bán hàng</a>
                </li>
            <?php endif; ?>
            <?php if (current_user_can('show_feedback_list')): ?>
                <li class="<?=strpos($currentUrl, 'feedback-list') ? 'active' : ''?>">
                    <a href="<?=site_url('feedback-list')?>">Danh sách khiếu nại</a>
                </li>
            <?php endif; ?>
            <?php if (isset($args['user']) && $args['user']->role == 'restaurant') : ?>
                <li class="<?=strpos($currentUrl, 'restaurant-list-orders') ? 'active' : ''?>">
                    <a href="<?=site_url('restaurant-list-orders')?>">Quản lý đơn hàng</a>
                </li>
            <?php endif; ?>
            <?php if (current_user_can('setting_on_off_product')): ?>
                <li class="<?=strpos($currentUrl, 'setting-product') ? 'active' : ''?>">
                    <a href="<?=site_url('setting-product')?>">On/Off sản phẩm</a>
                </li>
            <?php endif; ?>
        </ul>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav ml-md-auto">
<!--            <li>-->
<!--                <div class="wrap-search">-->
<!--                    <form action="" method="get">-->
<!--                        <input type="input" name="search" placeholder="Tìm đơn theo số điện thoại..." value="--><?//=(isset($_GET['search']) ? $_GET['search'] : '')?><!--" />-->
<!--                        <button type="submit"><i class="icon-search"></i></button>-->
<!--                    </form>-->
<!--                </div>-->
<!--            </li>-->
            <li>
                <a href="#" class="dropdown-toggle info-user" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span>G</span><?=wp_get_current_user()->first_name?></a>
                <div class="dropdown-menu dropdown-menu-md-right" aria-labelledby="bd-versions">
                    <a class="dropdown-item" href="<?=wp_logout_url('login')?>">Đăng xuất</a>
                </div>
            </li>
        </ul>
    </nav>
</header>
<!-- end header -->