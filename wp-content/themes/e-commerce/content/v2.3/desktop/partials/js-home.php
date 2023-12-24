<!--Change province-->
<script type="text/javascript">
    jQuery('.menu-province ul li').click(function () {
        showLoadingPage();
        let thisElement = jQuery(this),
            provinceId = thisElement.attr('data-province-id');

        let successCallback = function (res) {
            // Update dropdown province
            jQuery('.menu-province ul li').removeClass('active');
            thisElement.addClass('active');
            jQuery('#selected-province-name').html(thisElement.find('span').text());

            // Reload header
            let parentElem = document.getElementsByClassName('wrap-position')[0];
            let childElem = document.getElementById('selected-province-name');
            if (isWrap(parentElem, childElem)) {
                jQuery('.block-position').addClass('wrapped');
            } else {
                jQuery('.block-position').removeClass('wrapped');
            }

            // Reload list banner;
            var htmlBanner = '';
            var listBannerElem = jQuery('.block-banner .inner-wrap-slide');
            if (listBannerElem.length !== 0) {
                res.result.banners.forEach(function (one) {
                    if (one.desktopImage) {
                        htmlBanner += "<div><a href='" + one.linkTarget + "' title='" + one.name + "'><img src='" + one.desktopImage + "' alt='" + one.name + "'></a></div>";
                    }
                });
                listBannerElem.slick('unslick').html(htmlBanner);
                setTimeout(function () {
                    wrapSlideBanner();
                }, 200);
            }

            // Reload list brand
            var htmlBrands = '';
            var listBrandElem = jQuery('#list-brand');
            if (listBrandElem.length !== 0) {
                res.result.brands.forEach(function (one) {
                    htmlBrands += '<div class="list-single-brand item" data-category-id="' + one.id + '" data-category-url="' + one.url + '" data-category-name="' + one.name + '"><img src="' + one.logoBW + '" alt="' + one.name + '" /></div>';
                });
                listBrandElem.slick('unslick').html(htmlBrands);
                wrapSlideBrand();
            }

            // Reload hotdeal block
            let blockHotDealElem = jQuery('.block-hotdeal'),
                listProductHotElem =  blockHotDealElem.find('.list-product');
            if (listProductHotElem.length !== 0) {
                var htmlHotDeals = '';
                if (res.result.hotDealProducts.length === 0) {
                    blockHotDealElem.hide();
                } else {
                    htmlHotDeals = createBlockProduct(res.result.hotDealProducts, true);
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
                let listSuggestionProduct;
                if (typeof res.result.suggestProducts.sorted === 'undefined') {
                    listSuggestionProduct = res.result.suggestProducts;
                } else {
                    listSuggestionProduct = res.result.suggestProducts.sorted;
                }
                if (listSuggestionProduct.length > 0) {
                    htmlSuggestion = createBlockProduct(listSuggestionProduct);
                }
                if (listSuggestionProduct.length === 8) {
                    blockSuggestionElem.find('.block-view-more').show();
                    suggestionCurrentPage = 1;
                } else {
                    blockSuggestionElem.find('.block-view-more').hide();
                }
                listProductSuggestElem.html(htmlSuggestion);
            }
            // Reload list tags
            let htmlListTags = '<li><a class="active" title="tất cả" href="#" data-tag-slug="">#tấtcả</a></li>';
            if (res.result.listProductTags.length > 0) {
                res.result.listProductTags.forEach(function (tag) {
                    htmlListTags += '<li><a href="#" title="' + tag.name + '" data-tag-slug="' + tag.slug + '">#' + tag.name + '</a></li>'
                });
            }
            jQuery('.wrap-tags ul').html(htmlListTags);
        }
        reloadContentPageWhenChangeProvince('refresh_home_content', provinceId, successCallback);
    });
</script>

<!--Create slide for banner-->
<script type="text/javascript">
    function wrapSlideBanner() {
        jQuery('.inner-wrap-slide').slick({
            infinite: true,
            dots: true,
            autoplay: true,
            autoplaySpeed: 2000,
            adaptiveHeight: true,
            centerPadding: 0,
            arrows: false,
            slidesToShow: 1,
            slidesToScroll: 1
        });
    }

    wrapSlideBanner();
</script>

<!--Create slide for brand-->
<script type="text/javascript">
    function wrapSlideBrand() {
        jQuery('.wrap-slide-brand').slick({
            lazyLoad: 'ondemand',
            infinite: true,
            slidesToShow: 9,
            slidesToScroll: 8,
            centerPadding: '22px',
            prevArrow:'<div class="slide-arrow left"><i class="icon-left"></i></div>',
            nextArrow:'<div class="slide-arrow right"><i class="icon-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1025,
                    prevArrow:'<div class="slide-arrow left"><i class="icon-left"></i></div>',
                    nextArrow:'<div class="slide-arrow right"><i class="icon-right"></i></div>',
                    settings: {
                        arrows: true,
                        centerMode: true,
                        centerPadding: '20px',
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        arrows: false,
                        centerMode: true,
                        centerPadding: '20px',
                        slidesToShow: 1
                    }
                }
            ]
        });
        if (jQuery('#list-brand .slide-arrow').length === 0) {
            jQuery('#list-brand').addClass('margin-0');
        } else {
            jQuery('#list-brand').removeClass('margin-0');
        }
    }

    wrapSlideBrand();
</script>

<!--Handle block suggestion product: change tag, view more-->
<script type="text/javascript">
    var suggestionCurrentPage = 1;
    var tagSlugSelected = jQuery('.wrap-tags ul li a.active').attr('data-tag-slug');
    // Change tag
    jQuery(document).on('click', '.wrap-tags ul li a', function(){
        var thisElement = jQuery(this);
        if (thisElement.hasClass('active')) {
            return false;
        }
        suggestionCurrentPage = 1;
        jQuery('.block-suggestion .list-product').addClass('loading');
        jQuery('.wrap-tags ul li a').removeClass('active');
        tagSlugSelected = thisElement.attr('data-tag-slug');

        thisElement.addClass('active');

        getProductByTag('', tagSlugSelected, 1);

        return false;
    });

    // Click view more suggestion product
    jQuery(document).on('click', '.block-suggestion .block-view-more .view-more button', function () {
        let listProductElem = jQuery('.block-suggestion .list-product');
        listProductElem.addClass('loading');

        // Add spinner view more when click
        let thisElement = jQuery(this);
        if (thisElement.find('span.fa-spinner').length > 0) {
            return false;
        }
        thisElement.append(' <span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        let nextPage = suggestionCurrentPage + 1;
        suggestionCurrentPage = nextPage;
        let oldHtml = listProductElem.html();
        getProductByTag(oldHtml, tagSlugSelected, nextPage, 8)
    });
</script>

<!--Add product-->
<script type="text/javascript">
    var currentCategoryLink = '<?=(\GDelivery\Libs\Helper\Helper::getCurrentCategory() ? get_term_link(\GDelivery\Libs\Helper\Helper::getCurrentCategory()->term_id) : '')?>';
    var currentCategoryName = "<?=(\GDelivery\Libs\Helper\Helper::getCurrentCategory() ? \GDelivery\Libs\Helper\Helper::getCurrentCategory()->name : '')?>";
    jQuery(document).on('click', '.wrap-product .btn-add', function () {
        showLoadingPage();
        let thisElement = jQuery(this);

        let brandUrl = thisElement.attr('data-url'),
            productId = thisElement.attr('data-product-id'),
            parentId = thisElement.attr('data-parent-id'),
            brandId = thisElement.attr('data-brand-id'),
            brandName = thisElement.attr('data-brand-name');

        let selectedProductId = productId;
        if (parentId) {
            selectedProductId = parentId;
        }

        // Ajax check brand and add product.
        let params = doAjaxParamsDefault;
        params.requestType = 'POST';
        params.data = {
            action: 'add_and_redirect_detail_product',
            categoryId : brandId,
            productId: productId
        };
        params.successCallbackFunction = function (res) {
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
                        setSelectedProduct(selectedProductId);
                        window.location = brandUrl;
                    },
                    1000
                );
            } else if (res.messageCode == <?=\Abstraction\Object\Message::SUCCESS_WITHOUT_DATA?>) {
                hideLoadingPage();
                var scene = {
                    scene : 'confirm',
                    btnCancelScene : 'link',
                    btnCancelLinkAttr: ' href="javascript:void(0);" onclick="redirectForce(\'' + brandUrl + '\', ' + productId + ', ' + selectedProductId + ')" title="Đồng ý"',
                    btnCancelText: brandName,
                    btnOkScene : 'link',
                    btnOkLinkAttr: ' href="' + currentCategoryLink + '" title="Tiếp tục mua"',
                    btnOkText: currentCategoryName,
                };
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, scene);
            } else {
                hideLoadingPage();
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            }
        };
        params.errorCallBackFunction = function (error) {
            hideLoadingPage();
            openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
        }
        doAjax(params);
    });
</script>

<!--Restaurant closed by covid-19-->
<script type="text/javascript">
    <?php
    if (isset($args['selectedProvince']) && $args['selectedProvince']) {
    $selectedProvince = $args['selectedProvince'];
    $checkProvince = \GDelivery\Libs\Helper\Province::checkProvinceAvailable($selectedProvince->id);
    if ($checkProvince->messageCode == \Abstraction\Object\Message::GENERAL_ERROR) {
    ?>
    jQuery(document).ready(function () {
        openModalAlert('Thông báo', "<?=$checkProvince->message?>", {scene: 'info', btnOkText: 'Đóng'})
    });
    <?php
    }
    }
    ?>
</script>