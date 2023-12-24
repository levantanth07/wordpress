<?php
$bookingService = new \GDelivery\Libs\BookingService();
$selectedProvince = \GDelivery\Libs\Helper\Helper::getSelectedProvince();
$currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();

if ($selectedProvince) {
    // list brands
    $brands = \GDelivery\Libs\Helper\Category::getListInProvince($selectedProvince->id);

    // hot deal product
    $hotDealProducts = \GDelivery\Libs\Helper\Product::getProductByGroup('hot-deal', $selectedProvince->id, 1, -1);
    $hotDealProducts = array_slice(
        \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, "g-delivery:province:{$selectedProvince->id}:home-hotdeal:sort-product"),
        0,
        8,
        true);

    // list tag for suggestion
    $productTags = \GDelivery\Libs\Helper\Product::getListProductTags($selectedProvince->id);

    // list suggestion product (by tag)
    $allSuggestionProducts = \GDelivery\Libs\Helper\Product::getProductByTagOnHome('', $selectedProvince->id, 1, -1);
    $suggestionProducts = array_slice($allSuggestionProducts->sorted, 0, 8, true);

    $sliders = \GDelivery\Libs\Helper\Banner::getBanners('home-sliders', $selectedProvince->id);
} else {
    $brands = [];
    $sliders = [];
    $hotDealProducts = [];
}


?>

<!-- begin content -->
<div class="container content-page content-mb">
    <div class="row block-banner">
        <div class="col-12">
            <div class="wrap-slide">
                <div class="inner-wrap-slide">
                    <?php
                    if ($sliders) {
                        foreach ($sliders as $slider) {
                            if ($slider->mobileImage) {
                                echo "<div><a href='{$slider->linkTarget}' title='{$slider->name}'><img src='{$slider->mobileImage}' alt='{$slider->name}'></a></div>";
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="wrap-list">
                <h1>Thương hiệu nổi bật </h1>
                <div class="wrap-slide-brand" id="choose-list-brands">
                    <?php
                    if ($brands) {
                        $numberBrands = count($brands);
                        if ($numberBrands < 10) :
                            foreach ($brands as $brand) :
                                echo '<div class="item list-single-brand one-row" data-category-id='.$brand->id.' data-category-url='.$brand->url.' data-category-name='.$brand->name.'><img src='.$brand->logoBW.' alt='.$brand->name.'></div>';
                            endforeach; // end foreach brands
                        else :
                            $key = ceil($numberBrands / 2);
                            for ($i = 0; $i < $key; $i++) {
                                $upKey = $i;
                                $downKey = $i + $key;
                                $html = '<div class="item two-row">';
                                if (isset($brands[$upKey])) {
                                    $brand = $brands[$upKey];
                                    $html .= '<div class="list-single-brand" data-category-id="' . $brand->id .
                                        '" data-category-url="' . $brand->url .
                                        '" data-category-name="' . $brand->name .
                                        '"><img src="' . $brand->logoBW . '" alt="' . $brand->name . '"></div>';
                                }
                                if (isset($brands[$downKey])) {
                                    $brand = $brands[$downKey];
                                    $html .= '<div class="list-single-brand" data-category-id="' . $brand->id .
                                        '" data-category-url="' . $brand->url .
                                        '" data-category-name="' . $brand->name .
                                        '"><img src="' . $brand->logoBW . '" alt="' . $brand->name . '"></div>';
                                }
                                $html .= '</div>';
                                echo $html;
                            }
                        endif;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row block-hotdeal" style="<?=$hotDealProducts?'':'display: none;'?>">
        <div class="col-md-12">
            <div class="wrap-list products">
                <h1>Hot deal<a href="<?=site_url('hot-deal')?>" title="Hot Deal">Tất cả</a></h1>
                <div class="wrap-list-product">
                    <div class="row list-product" id="list-hot-deal-product">
                        <?php foreach ($hotDealProducts as $product) :
                            if ($product->salePrice) {
                                $discount = '-' . round(($product->regularPrice - $product->salePrice)/$product->regularPrice * 100) . '%';
                            } else {
                                $discount = 'HOT!';
                            }
                            ?>
                        <div class="col-6 each-block-product" id="product-<?=$product->id?>">
                            <div class="wrap-product">
                                <div class="wrap-img">
                                    <img class="lazy" src="<?=bloginfo('template_url')?>/assets/images/no-product-image.png" data-src="<?=$product->thumbnail?>" alt="<?=$product->name?>">
                                    <div class="wrap-feature">
                                        <span class="label"><?=$discount?></span>
                                        <!--<a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                    </div>
                                </div>
                                <div class="wrap-small-brand">
                                    <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                    <span><?=$product->brand->name?></span>
                                </div>
                                <h3>
                                    <a href="#" title="<?=$product->name?>"><?=$product->name?></a>
                                    <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                                </h3>
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
    <div class="row block-suggestion">
        <div class="col-md-12">
            <div class="wrap-list">
                <h1>Gợi ý cho bạn</h1>
            </div>
            <div class="wrap-tags">
                <ul>
                    <li><a class="active each-tag" href="#" title="tất cả" data-tag="">#tấtcả</a></li>
                    <?php foreach ($productTags as $tag) {
                        echo '<li><a class="each-tag" href="#" title="'.$tag->name.'" data-tag="'.$tag->slug.'">#'.$tag->name.'</a></li>';
                    }
                    ?>
                </ul>
            </div>
            <div class="wrap-list-product">
                <div class="row list-product" id="list-suggestion-product">
                <?php
                foreach ($suggestionProducts as $product) :
                    if ($product->salePrice) {
                            $discount = round(($product->regularPrice - $product->salePrice)/$product->regularPrice * 100);
                        } else {
                            $discount = 0;
                        }
                        ?>
                    <div class="col-6 each-block-product" id="product-<?=$product->id?>">
                        <div class="wrap-product">
                            <div class="wrap-img">
                                <img class="lazy" src="<?=get_bloginfo('template_url')?>/assets/images/no-product-image.png" data-src="<?=$product->thumbnail?>" alt="<?=$product->name?>">
                                <div class="wrap-feature">
                                    <?php if($discount != 0): ?><span class="label">-<?=$discount?>%</span><?php endif; ?>
                                    <!--<a href="#" class="label-save"><i class="icon-heart"></i></a>-->
                                </div>
                            </div>
                            <div class="wrap-small-brand">
                                <img src="<?=$product->brand->minimizeLogo?>" alt="<?=$product->brand->name?>">
                                <span><?=$product->brand->name?></span>
                            </div>
                            <h3>
                                <a href="#" title="<?=$product->name?>"><?=$product->name?></a>
                                <span><?=$product->quantitative?> <?=$product->textUnit?></span>
                            </h3>
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
                <div class="row">
                    <div class="col-md-12 col-lg-12">
                        <div class="view-more"><button id="load-more-by-tag" data-tag="" data-page="2" <?=(\count($suggestionProducts) != 8 ? 'style="display:none"' : '')?>>Xem thêm</button></div>
                    </div>
                </div>
            </div>
            <!-- end list product -->
        </div>
    </div>
</div>
<!-- end content -->

<script type="text/javascript">
    jQuery('.inner-wrap-slide').slick({
        infinite: true,
        dots: true,
        autoplay: true,
        autoplaySpeed: 2000,
        adaptiveHeight:true,
        centerMode:true,
        centerPadding:'20px',
        arrows:false,
        variableWidth: true,
        slidesToShow: 1,
        slidesToScroll: 1

    });
    jQuery('.wrap-slide-brand').slick({
        infinite: true,
        slidesToShow: 5,
        slidesToScroll: 5,
        autoplay: true,
        autoplaySpeed: 2000,
        arrows:false,

    });
</script>

<?php get_template_part('content/content', 'js-netcore'); ?>
<?php get_template_part('content/v2.3/mobile/partials/js', 'product'); ?>

<script type="text/javascript">

    // process select brand
    var currentCategoryLink = '<?=($currentCategory ? get_term_link($currentCategory->term_id) : '')?>';
    var currentCategoryName = "<?=($currentCategory ? $currentCategory->name : '')?>";
    var selectBrandProcessing = false;
    // select brand
    jQuery(document).on('click', '#choose-list-brands .list-single-brand', function(){
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
                openModalAlert('Thông báo', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: 'Đóng'});
            }
        });

        return false;
    });

    // select tag
    jQuery(document).on('click', 'li a.each-tag', function(){
        var thisElement = jQuery(this);
        if (thisElement.hasClass('active') || thisElement.find('span.fa-spinner').length > 0) {
            return false;
        }

        var tag = thisElement.attr('data-tag');
        var oldHtml = thisElement.html();

        // remove active class
        jQuery('li a.each-tag').removeClass('active');

        // active this
        thisElement.addClass('active');

        //loading this
        thisElement.append(' <span class="fa fa-1x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>');

        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'load_product_by_tag',
                tag: tag,
                page: 1,
                numberPerPage: 8
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    // prepare data
                    jQuery('#list-suggestion-product').html(' ');
                    var html = '';
                    jQuery.each(res.result, function (index, one) {
                        html += buildHtmlBlockProductOnHomePage(one);
                    });

                    jQuery('#list-suggestion-product').html(html);
                    jQuery('#load-more-by-tag').attr('data-tag', tag);

                    if (res.result.length != 8) {
                        jQuery('#load-more-by-tag').hide();
                    } else {
                        jQuery('#load-more-by-tag').show();
                    }
                } else {
                    openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
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
    }); // end select tag

    // load more from by tag
    jQuery(document).on('click', '#load-more-by-tag', function(){
        var thisElement = jQuery(this);
        if (thisElement.find('span.fa-spinner').length > 0) {
            return false;
        }
        var tag = thisElement.attr('data-tag');
        var page = Number(thisElement.attr('data-page'));
        var oldHtml = thisElement.html();

        //loading this
        thisElement.append(' <span class="fa fa-1x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>');

        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'load_product_by_tag',
                tag: tag,
                page: page,
                numberPerPage: 8
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    // prepare data
                    var html = '';
                    jQuery.each(res.result, function (index, one) {
                        html += buildHtmlBlockProductOnHomePage(one);
                    });

                    jQuery('#list-suggestion-product').append(html);

                    page++;

                    if (Object.keys(res.result).length === 8) {
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
            thisProvinceName = thisElement.children('span').html(),
            provinceId = thisElement.attr('data-province-id');

        let successCallback = function (res) {
            // Update dropdown province
            jQuery('.menu-province ul li').removeClass('active');
            thisElement.addClass('active');
            jQuery('#selected-province-name').html(thisProvinceName);

            let htmlBanner = '';
            let listBannerElem = jQuery('.block-banner .inner-wrap-slide');
            if (listBannerElem.length !== 0) {
                res.result.banners.forEach(function (one) {
                    if (one.mobileImage) {
                        htmlBanner += "<div><a href='" + one.linkTarget + "' title='" + one.name + "'><img src='" + one.mobileImage + "' alt='" + one.name + "'></a></div>";
                    }
                });
                listBannerElem.slick('unslick').html(htmlBanner);
                setTimeout(function () {
                    listBannerElem.slick({
                        infinite: true,
                        dots: true,
                        autoplay: true,
                        autoplaySpeed: 2000,
                        adaptiveHeight:true,
                        centerMode:true,
                        centerPadding:'20px',
                        arrows:false,
                        variableWidth: true,
                        slidesToShow: 1,
                        slidesToScroll: 1
                    });
                }, 200);
            }

            // process list brand
            var html = '';
            let brands = res.result.brands;
            if (brands.length < 10) {
                brands.forEach(function (one) {
                    html += '<div class="item list-single-brand one-row" data-category-id=' + one.id + ' data-category-url=' + one.url + ' data-category-name=' + one.name + '><img src=' + one.logoBW + ' alt=' + one.name + '></div>';
                });
            } else {
                let key = Math.ceil(brands.length / 2);
                for (var i = 0; i < key; i++) {
                    let upKey = i;
                    let downKey = i + key;
                    html += '<div class="item two-row">';
                    if (typeof brands[upKey] != "undefined") {
                        let brand = brands[upKey];
                        html += '<div class="list-single-brand" data-category-id="' + brand.id +
                        '" data-category-url="' + brand.url +
                        '" data-category-name="' + brand.name +
                        '"><img src="' + brand.logoBW + '" alt="' + brand.name + '"></div>';
                    }
                    if (typeof brands[downKey] != "undefined") {
                        let brand = brands[downKey];
                        html += '<div class="list-single-brand" data-category-id="' + brand.id +
                        '" data-category-url="' + brand.url +
                        '" data-category-name="' + brand.name +
                        '"><img src="' + brand.logoBW + '" alt="' + brand.name + '"></div>';
                    }
                    html += '</div>';
                }
            }
            jQuery('#choose-list-brands').removeClass('slick-initialized slick-slider').html(html);

            jQuery("#choose-list-brands").slick({
                infinite: true,
                slidesToShow: 5,
                slidesToScroll: 5,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows:false,

            });

            // Reload hotdeal block
            let blockHotDealElem = jQuery('.block-hotdeal'),
                listProductHotElem =  blockHotDealElem.find('.list-product');
            if (listProductHotElem.length !== 0) {
                var htmlHotDeals = '';
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

            // Reload suggestion block
            jQuery('.wrap-tags ul li a').removeClass('active');
            let blockSuggestionElem = jQuery('.block-suggestion'),
                listProductSuggestElem = blockSuggestionElem.find('.list-product');
            if (listProductSuggestElem.length !== 0) {
                var htmlSuggestion = '';
                if (res.result.suggestProducts.length > 0) {
                    htmlSuggestion = '';
                    res.result.suggestProducts.forEach(function (one) {
                        htmlSuggestion += buildHtmlBlockProductOnHomePage(one);
                    });
                }
                listProductSuggestElem.html(htmlSuggestion);
                jQuery('.wrap-tags ul li:nth-child(1) a').addClass('active');

                if (res.result.suggestProducts.length === 8) {
                    jQuery('#load-more-by-tag').show();
                } else {
                    jQuery('#load-more-by-tag').hide();
                }
            }
            // Reload list tags
            let htmlListTags = '<li><a class="each-tag active" href="#" data-tag="">#tấtcả</a></li>';
            if (res.result.listProductTags.length > 0) {
                res.result.listProductTags.forEach(function (tag) {
                    htmlListTags += '<li><a class="each-tag" href="#" data-tag="' + tag.slug + '">#' + tag.name + '</a></li>'
                });
            }
            jQuery('.wrap-tags ul').html(htmlListTags);
        }
        reloadContentPageWhenChangeProvince('refresh_home_content', provinceId, successCallback);
    });
</script>
