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
    <div class="row" style="margin-top: 8rem">
        <div class="col-md-12">
            <div class="wrap-list">
                <h3><span style="border-bottom: 3px solid #E96E34;">Hot</span> Deal</h3>
            </div>
            <div class="block-hotdeal">
                <div class="row list-product">
                <?php
                foreach ($hotDealProducts as $product) :
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
<!--                                    <a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                </div>
                            </div>
                            <div class="wrap-small-brand">
                                <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                <span><?=$product->brand->name?></span>
                            </div>
                            <h4>
                                <a href="javascript:void(0);" data-url="<?=$product->brand->url?>" data-product-id="<?=$product->id?>" title="<?=$product->name?>"><?=$product->name?></a>
                                <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                            </h4>
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
                </div>
            </div>
            <!-- end list product -->
        </div>
    </div>
</div>
<!-- end content -->

<?php get_template_part('content/content', 'js-netcore'); ?>

<!-- JS product -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'product');?>

<!-- JS province -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'province');?>

<!-- JS loading page -->
<?=get_template_part('content/v2.3/desktop/partials/js', 'loading-page');?>

<!--JS hot deal page-->
<?=get_template_part('content/v2.3/desktop/partials/js', 'hot-deal', ['selectedProvince' => $selectedProvince]);?>
