<?php
$bookingService = new \GDelivery\Libs\BookingService();
$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();
$currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();

if ($selectedProvince) {
    // hot deal product
    $hotDealProducts = \GDelivery\Libs\Helper\Product::getProductByGroup('hot-deal', $selectedProvince->id, 1, -1);
    $hotDealProducts = \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, "g-delivery:province:{$selectedProvince->id}:home-hotdeal:sort-product");
} else {
    $hotDealProducts = [];
}


?>

<!-- begin content -->
<div class="container content-page">
    <div class="row block-hotdeal" style="margin-top: 8rem">
        <div class="col-md-12">
            <div class="wrap-list">
                <h3><span style="border-bottom: 3px solid #E96E34;">Hot</span> Deal</h3>
            </div>
            <div class="wrap-list-product">
                <div class="row list-product" id="list-hot-deal-product">
                <?php
                foreach ($hotDealProducts as $product) :
                    if ($product->salePrice) {
                            $discount = '-' . round(($product->regularPrice - $product->salePrice)/$product->regularPrice * 100) . '%';
                        } else {
                            $discount = 'HOT!';
                        }
                        ?>
                    <div class="col-6 each-block-product">
                        <div class="wrap-product">
                            <div class="wrap-img">
                                <img class="lazy" src="<?=get_bloginfo('template_url')?>/assets/images/no-product-image.png" data-src="<?=$product->thumbnail?>" alt="<?=$product->name?>">
                                <div class="wrap-feature">
                                    <span class="label"><?=$discount?></span>
                                    <!--<a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                </div>
                            </div>
                            <div class="wrap-small-brand">
                                <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                <span><?=$product->brand->name?></span>
                            </div>
                            <h4>
                                <a href="#" title="<?=$product->name?>"><?=$product->name?></a>
                                <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                            </h4>
                            <div class="wrap-price">
                                <div class="row">
                                    <div class="col-6">
                                        <?php if ($product->salePrice):?>
                                        <span><?=number_format($product->regularPrice)?>đ</span>
                                        <p><?=number_format($product->salePrice)?>đ</p>
                                        <?php else: ?>
                                        <span style="text-decoration: none;">&nbsp;</span>
                                        <p><?=number_format($product->regularPrice)?>đ</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6 btn-end-center">
                                        <a
                                            href="#"
                                            class="btn-add home-quick-add-to-cart"
                                            data-category-id="<?=$product->brand->id?>"
                                            data-category-url="<?=$product->brand->url?>"
                                            data-category-name="<?=$product->brand->name?>"
                                            data-product-id="<?=$product->id?>">
                                            <i class="icon-add"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- end list product -->
        </div>
    </div>
</div>
<!-- end content -->

<?php get_template_part('content/content', 'js-netcore'); ?>
<?php get_template_part('content/v2.3/mobile/partials/js', 'product'); ?>

<script type="text/javascript">
    var currentCategoryLink = '<?=($currentCategory ? get_term_link($currentCategory->term_id) : '')?>';
    var currentCategoryName = "<?=($currentCategory ? $currentCategory->name : '')?>";

    // load more from by tag
    jQuery(document).on('click', '#load-more-by-group', function(){
        var thisElement = jQuery(this);
        var group = 'hot-deal';
        var page = Number(thisElement.attr('data-page'));
        var oldHtml = thisElement.html();

        //loading this
        thisElement.append(' <span class="fa fa-1x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>');

        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'load_product_by_group',
                group: group,
                page: page,
                numberPerPage: 8
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    // prepare data
                    var html = '';
                    let params = {
                        isHotDeal: true
                    };
                    res.result.forEach(function (one) {
                        html += buildHtmlBlockProductOnHomePage(one, params);
                    });

                    jQuery('#list-hot-deal-product').append(html);

                    page++;

                    if (res.result.length == 8) {
                        thisElement.attr('data-page', page);
                    } else {
                        thisElement.attr('data-page', page).hide();
                    }
                } else {
                    thisElement.attr('disabled', 'disabled').html(res.message);
                }
            },
            error : function (x, y, z) {
                console.log(x, y, z);
                openModalAlert('Thông báo', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: 'Đóng'});
            },
            complete: function (x, y) {
                thisElement.html(oldHtml);
            }
        });

        return false;
    }); // end load more

    // home-quick-add-to-cart
    jQuery(document).on('click', '.home-quick-add-to-cart', function() {
        var thisElement = jQuery(this);
        var categoryId = Number(thisElement.attr('data-category-id'));
        var categoryUrl = thisElement.attr('data-category-url');
        var categoryName = thisElement.attr('data-category-name');
        var productId = Number(thisElement.attr('data-product-id'));
        var oldHtml = thisElement.html();

        //loading this
        thisElement.html('<span class="fa fa-1x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>');

        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'check_selected_brand',
                categoryId : categoryId
            },
            success : function (res) {
                if (res.messageCode == 1) {
                    window.location = res.result.url + '?quickAddProductToCart=' + productId;
                } else if (res.messageCode == <?=\Abstraction\Object\Message::SUCCESS_WITHOUT_DATA?>) {
                    var scene = {
                        scene : 'confirm',
                        btnCancelScene : 'link',
                        btnCancelLinkAttr: ' href="' + categoryUrl + '?force=true&quickAddProductToCart=' + productId + '" title="Đồng ý"',
                        btnCancelText: categoryName,
                        btnOkScene : 'link',
                        btnOkLinkAttr: ' href="' + currentCategoryLink + '" title="Tiếp tục mua"',
                        btnOkText: currentCategoryName,
                    };
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, scene);
                } else {
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            error : function (x, y, z) {
                openModalAlert('Thông báo', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: 'Đóng'});
            },
            complete: function (x, y) {
                //thisElement.html(oldHtml);
            }
        });

        return false;
    }); // home quick add to cart

    // message for disable province
    <?php
    if ($selectedProvince) {
    $checkProvince = \GDelivery\Libs\Helper\Province::checkProvinceAvailable($selectedProvince->id);
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

<!--Change province-->
<script type="text/javascript">
    jQuery('ul#list-provinces li').click(function () {
        showLoadingPage();

        let thisElement = jQuery(this),
            oldHtml = thisElement.html(),
            thisProvinceName = thisElement.children('span').html(),
            provinceId = thisElement.attr('data-province-id');

        let successCallback = function (res) {
            // Update dropdown province
            jQuery('.menu-province ul li').removeClass('active');
            thisElement.addClass('active');
            jQuery('#selected-province-name').html(thisProvinceName);


            // Reload hotdeal block
            let blockHotDealElem = jQuery('.block-hotdeal'),
                listProductHotElem =  blockHotDealElem.find('.list-product');
            if (listProductHotElem.length !== 0) {
                var htmlHotDeals = '<p class="text-center" style="width: 100%;">Hiện chưa có ưu đãi nào</p>';
                if (res.result.hotDealProducts.length === 0) {
                    blockHotDealElem.hide();
                } else {
                    htmlHotDeals = '';
                    let params = {
                        isHotDeal: true
                    };
                    res.result.hotDealProducts.forEach(function (one) {
                        htmlHotDeals += buildHtmlBlockProductOnHomePage(one, params);
                    });
                    blockHotDealElem.show();
                }
                listProductHotElem.html(htmlHotDeals);
            }
        }
        reloadContentPageWhenChangeProvince('refresh_hotdeal_content', provinceId, successCallback);
    });
</script>