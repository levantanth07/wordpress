<!-- Google Tag Manager - dat.nguyen@ggg.com.vn MKT request 04/02/2021 -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PVLV6LB"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<?php
if (wp_is_mobile()) {
    $strBg = get_template_directory_uri().'/assets/images/bg-manwah-mobile.jpg?v='.\GDelivery\Libs\Config::VERSION;
    ?>
    <style>
        .cover-container header .brand a {
            background: url('<?=get_template_directory_uri()?>/assets/images/gdeli-logo.svg?v=<?=\GDelivery\Libs\Config::VERSION?>') no-repeat 0 0;
            width: 180px;
            height: 45px;
        }

        .header-list .brand a{
            background: url('<?=get_template_directory_uri()?>/assets/images/gdeli-logo.svg?v=<?=\GDelivery\Libs\Config::VERSION?>') no-repeat center left;
            width: 174px;
            height: 45px;
        }
    </style>
    <?php
} else {
    $strBg = get_template_directory_uri().'/assets/images/bg-manwah.jpg?v='. \GDelivery\Libs\Config::VERSION;
    ?>
    <style>
        .cover-container header .brand a {
            background: url('<?=get_template_directory_uri()?>/assets/images/logo.svg?v=<?=\GDelivery\Libs\Config::VERSION?>') no-repeat 0 0;
            width: 200px;
            height: 50px;
        }

        .header-list .brand a {
            background: url('<?=get_template_directory_uri()?>/assets/images/gdeli-logo.svg?v=<?=\GDelivery\Libs\Config::VERSION?>') no-repeat center left;
            width: 174px;
            height: 45px;
            text-align: center;
        }
    </style>
    <?php
}
?>