<!--Create block product-->
<script type="text/javascript">
    function createBlockProduct(listProducts, hasTagDiscount) {
        let html = '';
        jQuery.each(listProducts, function (index, product) {
            html += '<div class="col-md-4 col-lg-3 block-product">';
            html += '<div class="wrap-product">';
            html += '<div class="wrap-img">';
            html += '<img src="';
            if (product.thumbnail) {
                html += product.thumbnail;
            } else {
                html += "<?=bloginfo('template_url')?>/assets/images/no-product-image.png";
            }
            html += '" alt="' + product.name + '">';
            html += '<div class="wrap-feature">';
            if (hasTagDiscount) {
                let discountPercent = 'HOT!';
                if (product.salePrice) {
                    discountPercent = '-' + Math.round((product.regularPrice - product.salePrice) / product.regularPrice * 100, 0) + '%';
                }
                html += '<span class="label">' + discountPercent + '</span>';
            }
            // html += '<a href="#" class="label-save"><i class="icon-heart"></i></a>';
            html += '</div>';
            html += '</div>';
            html += '<div class="wrap-small-brand">';
            html += '<img src="';
            html += product.brand.minimizeLogo;
            html += '" alt="' + product.brand.name + '">';
            html += '<span>' + product.brand.name + '</span>';
            html += '</div>';
            html += '<h4><a href="javascript:void(0);" data-url="';
            html += product.brand.url + '" data-product-id="' + product.id + '"';
            html += ' title="' + product.name + '">';
            html += product.name + '</a><span>';
            html += product.quantitative + ' ' + product.textUnit;
            html += '</span></h4>';
            html += '<div class="wrap-price">';
            html += '<div class="row">';
            html += '<div class="col-md-6">';
            if (product.salePrice !== 0) {
                html += '<span>' + product.textRegularPrice + 'đ</span>';
                html += '<p>' + product.textSalePrice + 'đ</p>';
            } else {
                html += '<span style="text-decoration: none;">&nbsp;</span>';
                html += '<p>' + product.textRegularPrice + 'đ</p>';
            }
            html += '</div>';
            html += '<div class="col-md-6 btn-end-center">';
            html += '<a href="javascript:void(0);" title="Thêm vào giỏ hàng" data-url="';
            html += product.brand.url;
            html += '" data-product-id="';
            html += product.id;
            html += '" data-parent-id="';
            html += product.parentId;
            html += '" data-brand-id="';
            html += product.brand.id;
            html += '" data-brand-name="';
            html += product.brand.name;
            html += '" class="btn-add"><i class="icon-add"></i></a>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });

        return html;
    }
</script>

<!--Set and clear selected product-->
<script type="text/javascript">
    // Set selected product to local storage
    function setSelectedProduct(productID) {
        localStorage.setItem('selectedProductID', productID);
    }

    // Clear selected product to local storage
    function clearSelectedProduct() {
        localStorage.removeItem('selectedProductID');
    }
</script>

<!--Get product by tag-->
<script type="text/javascript">
    function getProductByTag(oldHtml, tagSlug, page, perPage) {
        let blockSuggestionElem = jQuery('.block-suggestion');
        let data = {
            action: 'load_product_by_tag',
            tag: tagSlug,
            page: page
        };
        if (perPage) {
            data.numberPerPage = perPage;
        }
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : data,
            success : function (res) {
                if (res.messageCode == 1) {
                    let listProducts = res.result,
                        numberProduct = Object.keys(listProducts).length;
                    let htmlSuggestion = oldHtml;

                    if (numberProduct === 0) {
                        if (!oldHtml) {
                            htmlSuggestion = '<p class="text-center" style="width: 100%;">Chưa có sản phẩm gợi ý</p>';
                        }
                    } else {
                        htmlSuggestion += createBlockProduct(listProducts);
                    }
                    if (numberProduct === 8) {
                        blockSuggestionElem.find('.block-view-more').show();
                    } else {
                        blockSuggestionElem.find('.block-view-more').hide();
                    }

                    blockSuggestionElem.find('.list-product').removeClass('loading').html(htmlSuggestion);
                } else {
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            error : function (x, y, z) {
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            },
            complete: function (x, y) {
                blockSuggestionElem.find('.block-view-more span.fa-spinner').remove();
            }
        });
    }
</script>

<!--Get product by group-->
<script type="text/javascript">
    function getProductByGroup(blockElem, oldHtml, groupSlug, page, perPage) {
        let data = {
            action: 'load_product_by_group',
            group: groupSlug,
            page: page
        };
        if (perPage) {
            data.numberPerPage = perPage;
        }
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : data,
            success : function (res) {
                if (res.messageCode == 1) {
                    let listProducts = res.result;
                    let htmlHotDeals = oldHtml;

                    if (listProducts.length !== 0) {
                        htmlHotDeals += createBlockProduct(listProducts, true);
                    }
                    if (listProducts.length === 8) {
                        blockElem.find('.block-view-more').show();
                    } else {
                        blockElem.find('.block-view-more').hide();
                    }
                    blockElem.find('.list-product').removeClass('loading').html(htmlHotDeals);
                } else {
                    openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            error : function (x, y, z) {
                openModalAlert('<?=__('alert.title.select-invalid-brand', 'g-delivery')?>', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            },
            complete : function () {
                blockElem.find('.block-view-more span.fa-spinner').remove();
            }
        });
    }
</script>

<!--Feature search product-->
<script type="text/javascript">
    jQuery(document).on('change keyup', 'input[name=search]', function () {
        var titleProduct = jQuery(document).find(".wrap-product h4 a");
        var selectorBlockProduct = jQuery(document).find(".list-product .block-product");
        var _val = $(this).val();
        if(parseInt(_val.length) >= 2){

            selectorBlockProduct.hide(); // hide block product

            // do search text
            var temp = titleProduct.filter(function () {
                return removeAccents($(this).text()).toLowerCase().indexOf(removeAccents(_val.toLowerCase())) > -1;
            });

            // display result
            temp.parent().parent().parent().show(); // display block product

        } else {
            selectorBlockProduct.show();
        }
    });

    function removeAccents(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D');
    }
</script>

<!--Handle redirect to force product-->
<script type="text/javascript">
    // Redirect to brand detail and clear cart.
    function redirectForce(urlDetail, productId, selectedProductId) {
        let btnCancel = jQuery('#modal-alert .btn-cancel'),
            oldHtml = btnCancel.html();

        btnCancel.append(' <span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
        setSelectedProduct(selectedProductId);
        let params = doAjaxParamsDefault;
        params.requestType = 'POST';
        params.data = {
            action: 'redirect_force',
            productId: productId
        };
        params.successCallbackFunction = function (res) {
            btnCancel.html(oldHtml);
            window.location = urlDetail;
        }
        doAjax(params);
    }
</script>