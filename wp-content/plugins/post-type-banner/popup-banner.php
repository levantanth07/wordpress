<?php
// current position
if (is_home()) {
    $position = 'home';
    $brandId = 0;
} elseif (is_product_category()) {
    $position = 'brand';
    $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
    $brandId = get_field('product_category_brand_id', 'product_cat_'.$currentCategory->term_id);
} else {
    $position = '';
    $brandId = 0;
}

if (in_array($position, ['home', 'brand'])) :
    if (!isset($_SESSION['closePopupBanner']) || !in_array($brandId, $_SESSION['closePopupBanner'])) :
        $bannerImage = '';
        $bannerName = '';

        // return banners
        $banners = [];

        // if not close popup
            // query params
            $args = [];
            $args['meta_query'] = [];

            if ($position) {
                $args['banner-category'] = $position;
            }
            if ($brandId) {
                $args['meta_query'][] = [
                    'key' => 'banner_brand_id',
                    'value' => $brandId,
                    'compare' => '='
                ];
            }

        // get current province
            $currentProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();
            if ($currentProvince) {
                $provinceId = $currentProvince->id;
            } else {
                $provinceId = 5; //set default to Ha Noi
            }
            $args['meta_query'][] = [
                [
                    'key' => 'banner_display_in_provinces',
                    'value' => serialize((string) $provinceId),
                    'compare' => 'like',
                ]
            ];

            $args['post_type'] = 'banners';
            $args['post_status'] = 'publish';
            $args['posts_per_page'] = -1;

        // The Query
            $the_query = new WP_Query($args);

        // The Loop
            if ($the_query->have_posts()) {
                while ($the_query->have_posts()) {
                    $the_query->the_post();
                    if (get_post_status() == 'publish') {
                        $temp['id'] = get_the_ID();
                        $temp['title'] = html_entity_decode(get_the_title());
                        $temp['date'] = get_the_date('Y-m-d H:i:s');
                        $temp['status'] = get_post_status();
                        $temp['mobileImage'] = get_field( 'banner_mobile_image', get_the_ID());
                        $temp['desktopImage'] = get_field('banner_desktop_image', get_the_ID());
                        $temp['bannerLinkType'] = get_field('banner_link_type', get_the_ID());
                        $temp['bannerLinkTarget'] = get_field('banner_link_target', get_the_ID());
                        $temp['order'] = get_field('banner_order', get_the_ID());
                        $temp['displayInProvince'] = get_field('banner_display_in_provinces', get_the_ID());

                        $banners[] = $temp;
                    }
                }
            }
        // re-order banner
            usort(
                $banners,
                function ($a, $b) {
                    return $a['order'] > $b['order'];
                }
            );

        $isRedirectFromHome = \GDelivery\Libs\Helper\Helper::getFlagRedirectFromHome();
        $isQuickAddToCartMobile = isset($_REQUEST['quickAddProductToCart']) ? (bool) $_REQUEST['quickAddProductToCart'] : false;
        if ($banners && !$isRedirectFromHome && !$isQuickAddToCartMobile) :
            // home page
            if (isset($_SESSION['closePopupBanner'])) {
                if (!in_array($brandId, $_SESSION['closePopupBanner'])) {
                    $_SESSION['closePopupBanner'][] = $brandId;
                }
            } else {
                $_SESSION['closePopupBanner'] = [$brandId];
            }

            $bannerName = $banners[0]['title'];
            $bannerLink = $banners[0]['bannerLinkTarget'];
            if (wp_is_mobile()) {
                $bannerImage = $banners[0]['mobileImage'];
            } else {
                $bannerImage = $banners[0]['desktopImage'];
            }

        ?>

        <!-- modal popup banner -->
        <div class="modal fade popup " id="modal-popup-banner" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <div class="modal-body">
                        <?php if ($bannerLink) :?>
                        <a href="<?=$bannerLink?>" title="<?=$bannerName?>">
                        <?php endif;?>
                            <img src="<?=$bannerImage?>" alt="<?=$bannerName?>" />
                        <?php if ($bannerLink) :?>
                        </a>
                        <?php endif;?>
                    </div>
                </div>
            </div>
        </div>
        <style>
            /************** modal popup *****************/
            #modal-popup-banner .modal-body {
                padding: 0;
                margin-top: 0;
            }
            #modal-popup-banner .close {
                border-radius: 20px;
                border: 2px solid #E96E34;
                padding: 0 8px 5px 8px;
                top: unset;
                right: 46%;
                bottom: -10%;
                color: #E96E34;
                opacity: 1;
            }
            /************** end modal popup *****************/
        </style>
        <!-- end modal alert -->
        <script type="text/javascript">
            function openModalPopup()
            {
                jQuery('#modal-popup-banner').modal({
                    'show' : true,
                    'backdrop' : 'static'
                });
            }
            openModalPopup();
        </script>

        <?php
        endif; // end if bannersD
        unset($_SESSION['redirectFromHome']);
    endif; // check session close popup modal
endif; // if check position
    ?>
