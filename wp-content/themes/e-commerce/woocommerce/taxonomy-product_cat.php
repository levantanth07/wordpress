<?php
if (get_queried_object()->parent == 0) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".get_bloginfo('url'));
    exit();
}

$force = isset($_REQUEST['force']) ? $_REQUEST['force'] : false;
$provinceId = isset($_REQUEST['provinceId']) ? $_REQUEST['provinceId'] : false;
$quickAddProductToCart = isset($_REQUEST['quickAddProductToCart']) ? $_REQUEST['quickAddProductToCart'] : null;

// query product group
$arrProductInGroups = [];

$productGroups = get_terms(
    'product_group',
    [
        'hide_empty' => 0,
        'parent' => 0,
        'exclude' => [15]
    ]
);
wp_reset_query();

// query product, with product group
$arr = array();
$i = 0;
/** @var WP_Term $currentCategory */
$currentCategory = get_queried_object();
$parentCategory = get_term($currentCategory->parent);

if ($force == true) {
    // reset
    if (WC()->cart->get_cart_contents_count() > 0) {
        WC()->cart->empty_cart();
    }

    \GDelivery\Libs\Helper\Helper::setCurrentCategory($currentCategory);
} else {
    if (!\GDelivery\Libs\Helper\Helper::getCurrentCategory()) {
        \GDelivery\Libs\Helper\Helper::setCurrentCategory($currentCategory);
    } elseif (\GDelivery\Libs\Helper\Helper::getCurrentCategory()->term_id != $currentCategory->term_id) {
        // reset
        if (WC()->cart->get_cart_contents_count() > 0) {
            WC()->cart->empty_cart();
        }

        \GDelivery\Libs\Helper\Helper::setCurrentCategory($currentCategory);
        \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
    }
}

if ($provinceId) {
    $provinceOfCategory = get_field('product_category_province_id', 'product_cat_'.$currentCategory->term_id);
    if ($provinceOfCategory != $provinceId) {
        \GDelivery\Libs\Helper\Helper::setSelectedProvince(null);
        WC()->cart->empty_cart();
        \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
    }
}

if ($quickAddProductToCart) {
    $doAddToCart = \GDelivery\Libs\Helper\Cart::addProductToCart($quickAddProductToCart, 1, false);
    \GDelivery\Libs\Helper\Address::updateSelectedProvince();
}

wp_reset_query();

$query = new WP_Query(
    [
        'post_type' => 'product',
        'post_status'=>'publish',
        'posts_per_page'=> -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $currentCategory->term_id,
            ),
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => ['topping', 'voucher-coupon'],
                'operator' => 'NOT IN'
            ),
        ),
    ]
);

foreach ($query->posts as $product) {

    $productFactory = new WC_Product_Factory();
    $productDetail = $productFactory->get_product($product->ID);
    $availableVariations = [];
    if ($productDetail->is_type('variable')) {
        $productVariations = \GDelivery\Libs\Helper\Product::getAvailableVariations($productDetail, true);
        $availableVariations = $productVariations['availableVariations'];
    }
    if (!empty($availableVariations)) {
        $product->availableVariations = $availableVariations;
    }

    $i++;
    $term_obj_list = get_the_terms($product->ID, 'product_group');

    if (isset($term_obj_list[0]->term_id)) {
        $arr[$term_obj_list[0]->term_id][] = $product->ID;
        if ($term_obj_list[0]->term_id) {
            $product->id = $product->ID;
            $arrProductInGroups[$term_obj_list[0]->term_id]['products'][] = $product;

            foreach ($productGroups as $group) {
                if ($group->term_id == $term_obj_list[0]->term_id) {
                    $arrProductInGroups[$term_obj_list[0]->term_id]['group'] = $group;
                }
            }
        }
    }
}


wp_reset_query();

// todo hardcode sort product group
// <editor-fold defaultstate="collapsed" desc="Sort Products">
foreach ($arrProductInGroups as $oneGroup) {
    usort(
        $oneGroup['products'],
        function ($a, $b) {
            return get_field('_regular_price', $a->ID) < get_field('_regular_price', $b->ID);
        }
    );
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Sort Groups">
$provinceId = get_field('product_category_province_id', 'product_cat_'.$currentCategory->term_id);
$brandId = get_field('product_category_brand_id', 'product_cat_'.$currentCategory->term_id);
$key = "g-delivery:province:{$provinceId}:brand:{$brandId}:group";
$sortGroup = unserialize(get_option($key));
if ($sortGroup) {
    $arrSorted = [];
    foreach ($sortGroup as $k=>$idGroup) {
        if (isset($arrProductInGroups[$idGroup])) {
            $arrSorted[$k] = $arrProductInGroups[$idGroup];
            unset($arrProductInGroups[$idGroup]);
        }
    }
    foreach ($arrProductInGroups as $groupInfo) {
        $arrSorted[] = $groupInfo;
    }
    $arrProductInGroups = $arrSorted;
}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Sort Product On Group">
foreach ($arrProductInGroups as $k=>$v) {
    $products = $v['products'];
    $groupId = $v['group']->term_id;
    $keyOptionSort = "g-delivery:province:{$provinceId}:brand:{$brandId}:group:{$groupId}";
    $arrProductInGroups[$k]['products'] = \GDelivery\Libs\Helper\Product::sortProduct($products, $keyOptionSort);
}
// </editor-fold>

// process restaurant
$helper = new \GDelivery\Libs\Helper\Helper();

$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();

get_header('order');
?>
    <!-- content list -->
    <div class="wrap-list<?=!wp_is_mobile()?' detail-brand':''?>">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-12">
                    <div class="<?=!wp_is_mobile()?'menu-sidebar':''?>">
                        <?php
                        $logo = get_field('product_category_logo', 'product_cat_'.$currentCategory->term_id);
                        $url = get_term_link($currentCategory);
                        $openTime1 = get_field('product_category_open_time_1', 'product_cat_'.$currentCategory->term_id) ?: '09:00';
                        $openTime1Obj = date_i18n('Y-m-d').' '.$openTime1;
                        $closeTime1 = get_field('product_category_close_time_1', 'product_cat_'.$currentCategory->term_id) ?: '22:00';
                        $closeTime1Obj = date_i18n('Y-m-d').' '.$closeTime1;
                        $openTime2 = get_field('product_category_open_time_2', 'product_cat_'.$currentCategory->term_id);
                        $openTime2Obj = date_i18n('Y-m-d').' '.$openTime2;
                        $closeTime2 = get_field('product_category_close_time_2', 'product_cat_'.$currentCategory->term_id);
                        $closeTime2Obj = date_i18n('Y-m-d').' '.$closeTime2;
                        $now = date_i18n('Y-m-d H:i:s');

                        $strOpenCloseTime = '';
                        $classOpenCloseTime = '';
                        $isOpen = true;
                        if ($now < $openTime1Obj) {
                            $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
                            $classOpenCloseTime = 'close-time';
                            $isOpen = false;
                        } elseif ($now >= $openTime1Obj && $now <= $closeTime1Obj) {
                            $strOpenCloseTime = 'Mở - Đóng: '.$closeTime1;
                        } elseif ($now > $closeTime1Obj) {
                            if ($openTime2 && $closeTime2) {
                                if ($now < $openTime2Obj) {
                                    $strOpenCloseTime = 'Đóng - Mở: '.$openTime2;
                                    $classOpenCloseTime = 'close-time';
                                    $isOpen = false;
                                } elseif ($now >= $openTime2Obj && $now <= $closeTime2Obj) {
                                    $strOpenCloseTime = 'Mở - Đóng: '.$closeTime2;
                                } elseif ($now > $closeTime1Obj) {
                                    $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
                                    $classOpenCloseTime = 'close-time';
                                    $isOpen = false;
                                }
                            } else {
                                $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
                                $classOpenCloseTime = 'close-time';
                                $isOpen = false;
                            }
                        }
                        ?>
                        <div class="mod-left">
                            <div class="wrap-current-brand">
                                <a href="<?=site_url()?>"><i class="icon-arrow-left"></i></a>
                                <img src="<?=$logo?>" alt="<?=$currentCategory->name?>" />
                                <span class="time <?=$classOpenCloseTime?>">
                                    <span class="fa fa-calendar"></span>
                                    <?=$strOpenCloseTime?>
                                </span>
                            </div>

                            <ul class="list-cate">
                                <?php
                                $i = 0;
                                foreach ($arrProductInGroups as $group) :
                                    $i++;
                                    $groupClass = '';
                                    $htmlName = '';
                                    switch ($group['group']->slug) {
                                        case 'hot-deal':
                                            $groupClass = 'hot-deal';
                                            $htmlName = "<h1>{$group['group']->name}</h1>";
                                            break;
                                        case 'best-seller':
                                            $groupClass = 'best-seller';
                                            $htmlName = "<h1>{$group['group']->name}</h1>";
                                            break;
                                        default:
                                            $htmlName = "<span>{$group['group']->name}</span>";
                                            break;
                                    }
                                    ?>
                                    <li class="<?=($i == 1 ? 'active' : '')?> group-item-name <?= $groupClass != '' ? 'tag-'.$groupClass : ''?>">
                                        <a href="#group-<?=$group['group']->term_id?>">
                                            <?php if ($groupClass != '') :?><i class="icon-<?=$groupClass?>"></i><?php endif;?>
                                            <?=$htmlName?>
                                        </a>
                                    </li>
                                <?php endforeach;?>
                            </ul>
                        </div>

                        <div class="mod-left-mb">
                            <div class="wrap-current-brand">
                                <?php
                                ?>
                                <a href="<?=site_url('?action=select-brand')?>"><i class="icon-arrow-left"></i></a>
                                <img src="<?=$logo?>" alt="<?=$currentCategory->name?>" />
                                <span class="time <?=$classOpenCloseTime?>">
                                        <span class="fa fa-calendar"></span>
                                        <?=$strOpenCloseTime?>
                                    </span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end mod left -->

                <!-- center -->
                <div class="col-lg-5 col-md-7 col-12 post-details">
                    <div class="mod-search">
                        <i class="icon-search"></i><input type="text" name="search" placeholder="Tìm kiếm món ăn">
                    </div>
                    <div class="mb-cate">
                        <div class="wrap-mb-cate">
                            <div class="mod-search">
                                <i class="icon-search"></i><input type="text" name="search" placeholder="Tìm kiếm món ăn">
                            </div>
                            <ul>
                                <?php
                                $i = 0;
                                foreach ($arrProductInGroups as $group) :
                                    $i++;
                                    $groupClass = '';
                                    $htmlName = '';
                                    switch ($group['group']->slug) {
                                        case 'hot-deal':
                                            $groupClass = 'hot-deal';
                                            $htmlName = "<h1>{$group['group']->name}</h1>";
                                            break;
                                        case 'best-seller':
                                            $groupClass = 'best-seller';
                                            $htmlName = "<h1>{$group['group']->name}</h1>";
                                            break;
                                        default:
                                            $htmlName = "<span>{$group['group']->name}</span>";
                                            break;
                                    }
                                    ?>
                                    <li class="<?=($i == 1 ? 'active' : '')?> group-item-name <?= $groupClass != '' ? 'mb-'.$groupClass : '' ?>">
                                        <i></i>
                                        <a href="#group-<?=$group['group']->term_id?>">
                                            <?php if ($group['group']->slug == 'hot-deal') :?><?php endif;?>
                                            <?=$htmlName?>
                                        </a>
                                    </li>
                                <?php endforeach;?>
                            </ul>
                        </div>
                    </div>


                    <?php
                    $i = 0;
                    foreach ($arrProductInGroups as $group) :
                        $i++;
                        $groupClass = '';
                        switch ($group['group']->slug) {
                            case 'hot-deal':
                                $groupClass = 'hot-deal';
                                break;
                            case 'best-seller':
                                $groupClass = 'best-seller';
                                break;
                            default:
                                break;
                        }
                        ?>
                        <div class="block-cate">
                            <div class="title-group <?=wp_is_mobile() ? 'is-mobile' : ''?>">
                                <span id="group-<?= $group['group']->term_id ?>"></span>
                            </div>
                            <h3><?=$group['group']->name?></h3>
                            <ul>
                                <?php
                                foreach ($group['products'] as $product) :

                                    $thumbnail = get_the_post_thumbnail_url($product, 'shop_catalog');
                                    if (strpos($thumbnail, '.gif') !== false) {
                                        $thumbnail = get_the_post_thumbnail_url($product, 'origin');
                                    }

                                    if ($product->availableVariations) {
                                        $productId = $product->availableVariations[0]['variationId'];
                                    } else {
                                        $productId = $product->ID;
                                    }
                                    $regularPrice = (float) get_field('_regular_price', $productId);
                                    $salePrice = (float) get_field('_sale_price', $productId);

                                    if ($salePrice) {
                                        $discountPercent = '-' . round(($regularPrice - $salePrice) / $regularPrice * 100, 0) . '%';
                                    } else {
                                        $discountPercent = 'hot!';
                                    }
                                ?>
                                <li
                                    id="product-<?=$productId?>"
                                    <?php if ($groupClass !=  '') :?>class="sticker <?=$groupClass?>"<?php endif;?>
                                    data-regular-price="<?=$regularPrice?>"
                                    data-sale-price="<?=$salePrice?>"
                                    data-name="<?=$product->post_title?>"
                                >
                                    <?php if ($groupClass != '') :?>
                                        <div class="tag"><span><?= $discountPercent?></span></div>
                                    <?php endif;?>
                                    <div class="container">
                                        <div class="row no-gutters">
                                            <div class="col-md-12 col-12 media">
                                                <a class="do-open-product-detail" data-product-id="<?=$product->ID?>" href="#product-<?=$product->ID?>" >
                                                    <?php if (get_field('product_is_alcohol_food', $product->ID) == 1) : ?>
                                                        <div class="alcohol">
                                                            <img src="<?= bloginfo('template_url') ?>/assets/images/ruou-bia.svg"/>
                                                        </div>
                                                    <?php endif; ?>
                                                    <img class="lazy" src="<?=bloginfo('template_url')?>/assets/images/no-product-image.png" data-src="<?=($thumbnail ? $thumbnail : bloginfo('template_url').'/assets/images/no-product-image.png')?>" alt="<?=$product->post_title?>"/>
                                                </a>
                                            </div>
                                            <div class="col-md-12 info">
                                                <div class="title-unit">
                                                    <a href="#product-<?=$product->ID?>" class="title do-open-product-detail" data-product-id="<?=$product->ID?>">
                                                        <?=$product->post_title?>
                                                    </a>
                                                    <span>
                                                        <?php if (\GDelivery\Libs\Helper\Helper::productUnitText(get_field('product_unit', $product->ID)) != 'Chưa xác định') :?>
                                                            <?=(get_field('product_quantitative', $product->ID).' '.\GDelivery\Libs\Helper\Helper::productUnitText(get_field('product_unit', $product->ID)))?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div class="tag-flash-sale none-tag"></div>
                                                <div class="price">
                                                    <?php
                                                    if ($salePrice) {
                                                        echo '<span>'.number_format($regularPrice).'₫</span>'.number_format($salePrice).'₫';
                                                    } else {
                                                        echo number_format($regularPrice).'₫';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="add-to-cart">
                                                    <?php
                                                        $cartInfo = \GDelivery\Libs\Helper\Helper::productInCart($productId);
                                                    ?>
                                                    <a href="#" class="minus" data-action="minus" data-product-id="<?=$productId?>" style="<?=($cartInfo ? '' : 'display: none;')?>"><i class="icon-minius"></i></a>
                                                    <span class="quantity" style="<?=($cartInfo ? '' : 'display: none;')?>"><?=($cartInfo ? $cartInfo['quantity'] : 0)?></span>
                                                    <a href="#" class="add" data-action="plus" data-product-id="<?=$productId?>"><i class="icon-add"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; // end foreach group['product']?>
                            </ul>
                        </div>
                    <?php endforeach; // end foreach arrProductInGroup?>

                </div>
                <!-- end center -->


                <!-- right -->
                <div class="col-lg-4 col-md-5 col-12">
                    <div class="product-cart">
                        <?php
                        $totals = \GDelivery\Libs\Helper\Helper::calculateCartTotals();
                        get_template_part(
                                'content/content',
                                'checkout-cart',
                                [
                                    'totals' => $totals
                                ]
                        );
                        ?>
                    </div>
                </div>
                <!-- end right -->

                <?php get_template_part('content/content', 'js-cart'); ?>
            </div>
        </div>
    </div>
    <!-- end list -->

    <?php get_template_part('content/content', 'modal-product-detail'); ?>

    <!-- Modal restaurant not open yet -->
    <div class="modal-msg modal fade" id="modal-restaurant-not-open-yet" tabindex="-1" aria-labelledby="modal-restaurant-not-open-yet" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-success">
                        <h4>Nhà hàng chưa mở cửa</h4>
                        <p>Bạn vẫn có thể đặt hàng trước. Đơn hàng của bạn sẽ được xử lý ngay khi chúng tôi mở cửa.</p>
                        <button data-dismiss="modal">Đặt hàng trước</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    jQuery(document).ready(function () {

        let sidebarElem = jQuery(".menu-sidebar");
        let productCartElem = jQuery(".product-cart");
        let contentElem = jQuery(".post-details");
        let windowElem = jQuery(window);
        let sidebarOffset = sidebarElem.offset();
        let cartOffset = productCartElem.offset();

        // Init scroll on product detail page
        sidebarElem.css('top', '75px');

        windowElem.scroll(function () {
            // Set margin for left sidebar.
            setMarginWhenScroll(sidebarElem, sidebarOffset, contentElem);

            // Set margin for cart.
            setMarginWhenScroll(productCartElem, cartOffset, contentElem);
        });
    });

    function setMarginWhenScroll(sidebarElem, sidebarOffset, contentElem) {
        let headerHeight = 65;
        let windowElem = jQuery(window);
        let windowHeight = windowElem.height();
        let contentHeight = contentElem.height();
        if (contentHeight < windowHeight - 75) {
            contentHeight = windowHeight - 75;
        }
        if (windowElem.scrollTop() > 10) {
            let maxHeight = contentHeight - windowHeight + headerHeight;
            let top = maxHeight - windowElem.scrollTop() + headerHeight;
            if (top > 75) {
                top = 75;
            }
            sidebarElem.css('top', top + 'px');
        } else {
            sidebarElem.css('top', '75px');
        }
    }
</script>

<?php get_template_part('content/content', 'js-netcore'); ?>

<script type="text/javascript">
    <?php if (!wp_is_mobile()) :?>
    // checkout
    jQuery('.btn-do-checkout').click(function () {
        var isLogin = <?=(\GDelivery\Libs\Helper\User::isLogin() ? 1 : 0)?>;
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();
        thisElement.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        if (isLogin == 1) {
            if (cartItemQuantity > 0) {
                let successCallback = function (res) {
                    if (res.messageCode == 0) {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                        $('#modal-alert').on('hide.bs.modal', function (e) {
                            window.location.reload();
                        })
                    } else {
                        setTimeout(
                            function () {
                                location.href = '<?=site_url('checkout-pay-and-delivery')?>';
                            },
                            1000
                        );
                    }
                };
                reloadCartLimit(successCallback);
            } else {
                openModalAlert('Thông báo', 'Vui lòng chọn sản phẩm vào giỏ hàng', {scene : 'info', btnOkText: 'Đóng'});
                thisElement.removeAttr('disabled').html(oldHtml);
            }
        } else {
            openModalLogin();
            thisElement.removeAttr('disabled').html(oldHtml);
        }

        return false;
    });
    <?php endif; ?>
</script>

<script type="text/javascript">
    // active group item name
    jQuery('.group-item-name').click(function () {
        jQuery('.group-item-name').removeClass('active');
        jQuery(this).addClass('active');
    });

</script>

<script type="text/javascript">

        <?php
        if (!$isOpen && (!isset($_SESSION['openModalRestaurantNotOpenYet']) || $_SESSION['openModalRestaurantNotOpenYet'] == 0)) :
            $_SESSION['openModalRestaurantNotOpenYet'] = 1;
        ?>
        jQuery('#modal-restaurant-not-open-yet').modal('show');
        <?php
        endif;
        ?>

        // quick add to card
        jQuery('.add-to-cart .add, .add-to-cart .minus').click(function () {
            var thisElement = jQuery(this);
            var productId = thisElement.attr('data-product-id');
            var action = thisElement.attr('data-action');
            var quantity = Number(jQuery('#product-' + productId + ' .add-to-cart .quantity').html());
            var productName = jQuery('#product-' + productId).attr('data-name');
            var productImage = jQuery('#product-' + productId + ' .media img').attr('src');
            var productRegularPrice = Number(jQuery('#product-' + productId).attr('data-regular-price'));
            var productSalePrice = Number(jQuery('#product-' + productId).attr('data-sale-price'));

            if (action == 'plus') {
                quantity += 1;
            } else if (action == 'minus') {
                quantity -= 1;
            }

            thisElement.html('<span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
            jQuery.ajax({
                url : '<?=admin_url('admin-ajax.php')?>',
                type : 'post',
                dataType : 'json',
                data : {
                    beHonest: '<?=wp_create_nonce('add_product_to_cart')?>',
                    action: 'add_product_to_cart',
                    productId : productId,
                    quantity : quantity,
                    addAsNew : true
                },
                success : function (res) {
                    if (res.messageCode == 1) {
                        reloadCart(0, 'cart');

                        if (quantity <= 0) {
                            quantity = 0;
                            jQuery('#product-' + productId + ' .add-to-cart .quantity').hide();
                            jQuery('#product-' + productId + ' .add-to-cart .minus').hide();
                        } else {
                            jQuery('#product-' + productId + ' .add-to-cart .quantity').show();
                            jQuery('#product-' + productId + ' .add-to-cart .minus').show();
                        }

                        // fire Netcore event
                        if (isEnabledNetCore == 1) {
                            // selected restaurant
                            smartech('dispatch', 'Add to Cart', {
                                'prid': productId,
                                'product_name': productName,
                                'product_img': productImage,
                                'product_regular_price': productRegularPrice,
                                'product_sale_price': productSalePrice,
                                'product_quantity': quantity
                            });
                        }

                        jQuery('#product-' + productId + ' .add-to-cart .quantity').html(quantity);
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                    }

                    if (action == 'plus') {
                        thisElement.html('<i class="icon-add"></i>');
                    } else if (action == 'minus') {
                        thisElement.html('<i class="icon-minius"></i>');
                    }
                },
                error : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                    if (action == 'plus') {
                        thisElement.html('<i class="icon-add"></i>');
                    } else if (action == 'minus') {
                        thisElement.html('<i class="icon-minius"></i>');
                    }
                }
            });
            jQuery('.quick-add-item-to-cart').removeClass('not-active-a');
            return false;
        });

        // feature search
        var titleProduct = $(".block-cate li a.title");
        var selectorGroupProduct = $(".block-cate");
        var selectorBlockProduct = jQuery(".block-cate li");
        $('input[name=search]').on('change keyup', function () {

            var _val = $(this).val();
            if(parseInt(_val.length) >= 2){

                selectorGroupProduct.hide(); // hide  whole group product
                selectorBlockProduct.hide(); // hide block product

                // do search text
                var temp = titleProduct.filter(function () {
                    return removeAccents($(this).text()).toLowerCase().indexOf(removeAccents(_val.toLowerCase())) > -1;
                });

                // display result
                temp.parent().parent().parent().parent().parent().show(); // display block product
                temp.parent().parent().parent().parent().parent().parent().parent().show(); // display group product

            } else {
                selectorGroupProduct.show();
                selectorBlockProduct.show();
            }
        });

        function removeAccents(str) {
            return str.normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'D');
        }

    </script>

<script type="text/javascript">
    // Auto add product when selected from home page
    jQuery(document).ready(function () {
        let selectedProductID = localStorage.getItem('selectedProductID');

        if (selectedProductID) {
            localStorage.removeItem('selectedProductID');
            openProductDetail(selectedProductID);
        }
    });

</script>

<?php if (!$selectedProvince) :?>
    <?php get_template_part('content/modal', 'select-province'); ?>
    <script type="text/javascript">
        jQuery('#modal-select-province').modal(
            {
                'show' : true,
                'backdrop' : 'static'
            }
        );
    </script>

<?php else: ?>
<script type="text/javascript">
    // fire Netcore event
    if (isEnabledNetCore == 1) {
        ncBeginOrder(
            {
                brandName: '<?=str_replace("'", ' ', $currentCategory->name)?>',
                restaurantName: ''
            }
        );
    }
</script>
<?php endif;?>

<?php
if ($quickAddProductToCart):
    $productId = $quickAddProductToCart;
    $productVariations = wc_get_product($quickAddProductToCart);
    if ($productVariations->is_type('variation')) {
        $productId = $productVariations->get_parent_id();
    }
?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            openProductDetail(<?=$productId?>);
        });
    </script>
<?php endif; ?>

<?php
    if (isset($doAddToCart) && $doAddToCart->messageCode == \Abstraction\Object\Message::GENERAL_ERROR) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                openModalAlert('Thông báo', '<?=$doAddToCart->message;?>', {scene : 'info', btnOkText: 'Đóng'});
            });
        </script>
        <?php
    }
?>

<?php get_footer();?>