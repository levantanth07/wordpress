<?php
// home page desktop v2.3

$bookingService = new \GDelivery\Libs\BookingService();
$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();
$currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();

if ($selectedProvince) {
    GDelivery\Libs\Helper\Helper::setSelectedProvince($selectedProvince);
    // Get brand in province.
    $brands = \GDelivery\Libs\Helper\Category::getListInProvince($selectedProvince->id);

    // Hot deal product.
    $hotDealProducts = \GDelivery\Libs\Helper\Product::getProductByGroup('hot-deal', $selectedProvince->id, 1, -1);
    $hotDealProducts = array_slice(
        \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, "g-delivery:province:{$selectedProvince->id}:home-hotdeal:sort-product"),
        0,
        8,
        true);

    // List tag for suggestion.
    $productTags = \GDelivery\Libs\Helper\Product::getListProductTags($selectedProvince->id);

    // List suggestion product (by tag).
    $allSuggestionProducts = \GDelivery\Libs\Helper\Product::getProductByTagOnHome('', $selectedProvince->id, 1, -1);
    $suggestionProducts = array_slice($allSuggestionProducts->sorted, 0, 8, true);

    // List slider.
    $sliders = \GDelivery\Libs\Helper\Banner::getBanners('home-sliders', $selectedProvince->id);

} else {
    $brands = [];
    $hotDealProducts = [];
    $productTags = [];
    $suggestionProducts = [];
    $sliders = [];
}

?>

<div class="row block-banner">
    <div class="col-md-12 banner">
        <div class="wrap-slide">
            <div class="inner-wrap-slide">
                <?php
                if ($sliders) {
                    foreach ($sliders as $slider) {
                        if ($slider->desktopImage) {
                            echo "<div><a href='{$slider->linkTarget}' title='{$slider->name}'><img src='{$slider->desktopImage}' alt='{$slider->name}'></a></div>";
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row wrap-block-options">
        <div class="col-md-3">
            <div class="item-options">
                <div class="ico"><img src="<?=bloginfo('template_url') . '/assets/v2.3/desktop/images/option01.svg'?>"></div>
                <h4 class="info-option">
                    <span>Đặt hàng</span>
                    <p>Nhanh và dễ dàng</p>
                </h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="item-options">
                <div class="ico"><img src="<?=bloginfo('template_url') . '/assets/v2.3/desktop/images/option02.svg'?>"></div>
                <h4 class="info-option">
                    <span>Từ hệ thống</span>
                    <p>Hơn 400 nhà hàng</p>
                </h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="item-options">
                <div class="ico"><img src="<?=bloginfo('template_url') . '/assets/v2.3/desktop/images/option03.svg'?>"></div>
                <h4 class="info-option">
                    <span>Giao hàng</span>
                    <p>Mọi nơi bạn muốn</p>
                </h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="item-options">
                <div class="ico"><img src="<?=bloginfo('template_url') . '/assets/v2.3/desktop/images/option04.svg'?>"></div>
                <h4 class="info-option">
                    <span>Hàng ngàn</span>
                    <p>Ưu đãi hấp dẫn nhất</p>
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="container content-page">
    <div class="row">
        <div class="col-md-12">
            <div class="wrap-list">
                <h1>Thương hiệu nổi bật </h1>
                <div class="wrap-slide-brand" id="list-brand">
                    <?php
                    if ($brands) {
                        foreach ($brands as $brand) :
                            echo "<div class='list-single-brand item' data-category-id='{$brand->id}' data-category-url='{$brand->url}' data-category-name=\"{$brand->name}\"><img src='{$brand->logoBW}' alt=\"{$brand->name}\"></div>";
                        endforeach; // end foreach brands
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row block-hotdeal" style="<?=$hotDealProducts?'':'display: none;'?>">
        <div class="col-md-12">
            <div class="wrap-list products">
                <h1>Hot deal<a href="<?=site_url('hot-deal')?>" title="Tất cả">Tất cả</a></h1>
                <div>
                    <div class="row list-product">
                        <?php foreach ($hotDealProducts as $product):
                            if ($product->salePrice) {
                                $discountPercent = '-' . round(($product->regularPrice - $product->salePrice) / $product->regularPrice * 100, 0) . '%';
                            } else {
                                $discountPercent = 'HOT!';
                            }
                            ?>
                            <div class="col-md-4 col-lg-3 block-product">
                                <div class="wrap-product">
                                    <div class="wrap-img">
                                        <img class="lazy" src="<?=$product->thumbnail?$product->thumbnail:bloginfo('template_url') . '/assets/images/no-product-image.png'?>" alt="<?=$product->name?>"/>
                                        <div class="wrap-feature">
                                            <span class="label"><?=$discountPercent?></span>
<!--                                            <a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                        </div>
                                    </div>
                                    <div class="wrap-small-brand">
                                        <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                        <h2><?=$product->brand->name?></h2>
                                    </div>
                                    <h3>
                                        <a href="javascript:void(0);" data-url="<?=$product->brand->url?>" data-product-id="<?=$product->id?>" title="<?=$product->name?>"><?=$product->name?></a>
                                        <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                                    </h3>
                                    <div class="wrap-price">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <?php if ($product->salePrice):?>
                                                    <span><?=number_format($product->regularPrice)?>đ</span>
                                                    <p><?=number_format($product->salePrice)?>đ</p>
                                                <?php else:?>
                                                    <span style="text-decoration: none;">&nbsp;</span>
                                                    <p><?=number_format($product->regularPrice)?>đ</p>
                                                <?php endif;?>
                                            </div>
                                            <div class="col-md-6 btn-end-center">
                                                <a href="javascript:void(0);" title="Thêm vào giỏ hàng" class="btn-add"
                                                   data-url="<?=$product->brand->url?>"
                                                   data-product-id="<?=$product->id?>"
                                                   data-parent-id="<?=$product->parentId?>"
                                                   data-brand-id="<?=$product->brand->id?>"
                                                   data-brand-name="<?=$product->brand->name?>">
                                                    <i class="icon-add"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$hotDealProducts):?>
                            <p class="text-center" style="width: 100%;">Hiện chưa có ưu đãi nào</p>
                        <?php endif;?>
                    </div>
                </div>
                <!-- end list product -->
            </div>
        </div>
    </div>
    <div class="row block-suggestion">
        <div class="col-md-12">
            <div class="wrap-list">
                <h1>Gợi ý cho bạn</h1>
            </div>
            <div class="wrap-tags">
                <ul>
                    <li>
                        <a class="active" href="#" title="Tất cả" data-tag-slug="">
                            <h2>#tấtcả</h2>
                        </a>
                    </li>
                    <?php foreach ($productTags as $key=>$tag):?>
                    <li>
                        <a href="#" title="<?=$tag->name?>" data-tag-slug="<?=$tag->slug?>">
                            <h2>#<?=$tag->name?></h2>
                        </a>
                    </li>
                    <?php endforeach;?>
                </ul>
            </div>
            <div class="suggestion-products">
                <div class="row list-product">
                    <?php foreach ($suggestionProducts as $product):?>
                    <div class="col-md-4 col-lg-3 block-product">
                        <div class="wrap-product">
                            <div class="wrap-img">
                                <img src="<?=$product->thumbnail?$product->thumbnail:bloginfo('template_url') . '/assets/images/no-product-image.png'?>" alt="<?=$product->name?>">
                                <div class="wrap-feature">
<!--                                    <a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                </div>
                            </div>
                            <div class="wrap-small-brand">
                                <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                <h2><?=$product->brand->name?></h2>
                            </div>
                            <h3>
                                <a href="javascript:void(0);" data-url="<?=$product->brand->url?>" data-product-id="<?=$product->id?>" title="<?=$product->name?>"><?=$product->name?></a>
                                <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                            </h3>
                            <div class="wrap-price">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php if ($product->salePrice):?>
                                            <span><?=$product->textRegularPrice?>đ</span>
                                            <p><?=$product->textSalePrice?>đ</p>
                                        <?php else:?>
                                            <span style="text-decoration: none;">&nbsp;</span>
                                            <p><?=$product->textRegularPrice?>đ</p>
                                        <?php endif;?>
                                    </div>
                                    <div class="col-md-6 btn-end-center">
                                        <a href="javascript:void(0);" title="Thêm vào giỏ hàng" class="btn-add"
                                           data-url="<?=$product->brand->url?>"
                                           data-product-id="<?=$product->id?>"
                                           data-parent-id="<?=$product->parentId?>"
                                           data-brand-id="<?=$product->brand->id?>"
                                           data-brand-name="<?=$product->brand->name?>">
                                            <i class="icon-add"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <div class="row block-view-more" style="<?=count($suggestionProducts) == 8?'':'display: none;'?>">
                    <div class="col-md-12 col-lg-12">
                        <div class="view-more"><button>Xem thêm</button></div>
                    </div>
                </div>
            </div>
            <!-- end list product -->
        </div>
    </div>
</div>

<!-- Modal order success -->
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

<?php
if (!$selectedProvince) {
    ?>
    <script type="text/javascript">
        jQuery('#modal-location-alert').modal(
            {
                'show' : true,
            }
        );

        jQuery('#modal-location-alert .choose-province').click(function () {
            jQuery('#modal-location-alert').modal('hide');
            setTimeout(function () {
                jQuery('#dropdown-province').trigger('click');
            }, 1000);
        });
    </script>
    <?php
}
?>

<script type="text/javascript">
    // process select brand
    var currentCategoryLink = '<?=($currentCategory ? get_term_link($currentCategory->term_id) : '')?>';
    var currentCategoryName = "<?=($currentCategory ? $currentCategory->name : '')?>";
    var selectBrandProcessing = false;
    // select brand
    jQuery(document).on('click', '#list-brand .list-single-brand', function(){
        if (selectBrandProcessing) {
            return false;
        }

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
            beforeSend : function () {
                selectBrandProcessing = true;
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
                } else {
                    selectBrandProcessing = false;
                    if (res.messageCode == <?=\Abstraction\Object\Message::SUCCESS_WITHOUT_DATA?>) {
                        var scene = {
                            scene : 'confirm',
                            btnCancelScene : 'link',
                            btnCancelLinkAttr: ' href="' + categoryUrl + '?force=true" title="Đồng ý"',
                            btnCancelText: categoryName,
                            btnOkScene : 'link',
                            btnOkLinkAttr: ' href="' + currentCategoryLink + '" title="Tiếp tục mua"',
                            btnOkText: currentCategoryName,
                        };
                        openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, scene);
                    } else {
                        openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                    }
                }
            },
            error : function (x, y, z) {
                selectBrandProcessing = false;
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            }
        });

        return false;
    });

    // search province
    var titleProvince = $('.menu-province ul li span');
    var selectorLiProvince = jQuery('.menu-province ul li');
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

<!-- JS province -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'province');?>

<!-- JS product -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'product');?>

<!-- JS loading page -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'loading-page');?>

<!-- JS for home page -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'home', ['selectedProvince' => $selectedProvince]);?>

