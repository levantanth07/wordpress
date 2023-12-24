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

            // Reload hotdeal block
            let blockHotDealElem = jQuery('.block-hotdeal'),
                listProductHotElem =  blockHotDealElem.find('.list-product');
            if (listProductHotElem.length !== 0) {
                let htmlHotDeals = '<p class="text-center" style="width: 100%;">Hiện chưa có ưu đãi nào</p>';
                if (res.result.hotDealProducts.length === 0) {
                    blockHotDealElem.hide();
                } else {
                    htmlHotDeals = createBlockProduct(res.result.hotDealProducts, true);
                    blockHotDealElem.show();
                }
                listProductHotElem.html(htmlHotDeals);
            }
        }
        reloadContentPageWhenChangeProvince('refresh_hotdeal_content', provinceId, successCallback);
    });
</script>

<!--Handle click view or add product-->
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
        }
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
        openModalAlert('Thông báo', "<?=$checkProvince->message?>", {scene : 'info', btnOkText: 'Đóng'})
    });
    <?php
    }
    }
    ?>
</script>