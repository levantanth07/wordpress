<script type="text/javascript">
    function buildHtmlBlockProductOnHomePage(product, params)
    {
        var html = '';
        html += '<div class="col-6 each-block-product" id="product-' + product.id + '">' +
            '   <div class="wrap-product">' +
            '       <div class="wrap-img">' +
            '           <img class="lazy" src="' + product.thumbnail + '" data-src="' + product.thumbnail + '" alt="' + product.name + '">';

        var discount = 0;
        if (product.salePrice > 0) {
            discount = '-' + ((product.regularPrice - product.salePrice)/product.regularPrice * 100).format() + '%';
        } else {
            if (typeof params != "undefined" && params.isHotDeal) {
                discount = 'HOT!';
            }
        }

        html += '<div class="wrap-feature">';
        if (discount > 0 || (typeof params != "undefined" && params.isHotDeal)) {
            html += '<span class="label">'+ discount +'</span>';
        }
        html += '</div>'; // end div wrap-feature

        html += '</div>' +
            '   <div class="wrap-small-brand"><img src="' + product.brand.minimizeLogo +'" alt="' + product.brand.name + '"><span>' + product.brand.name + '</span></div>' +
            '   <h4><a href="" title="' + product.name + '">' + product.name + '</a><span>' + product.quantitative + ' ' + product.textUnit + '</span></h4>' +
            '       <div class="wrap-price">' +
            '           <div class="row">' +
            '               <div class="col-6">';

        if (product.salePrice > 0) {
            html += '<span>' + product.regularPrice.format() + 'đ</span><p>' + product.salePrice.format() + 'đ</p>';
        } else {
            html += '<span style="text-decoration: none;">&nbsp;</span><p>' + product.regularPrice.format() + 'đ</p>';
        }

        html += '</div>' +
            '   <div class="col-6 btn-end-center">' +
            '       <a href="#"' +
            '               class="btn-add home-quick-add-to-cart"' +
            '               data-category-id=" ' + product.brand.id + '" data-category-url="' + product.brand.url + '"' +
            '               data-category-name="' + product.brand.name + '"' +
            '               data-product-id="' + product.id + '">' +
            '           <i class="icon-add"></i>' +
            '       </a>' +
            '   </div>' +
            '</div></div></div></div>';

        return html;
    }

    function refreshListProducts()
    {
        // hot deal product
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'load_product_by_group',
                group: 'hot-deal',
                page: 1,
                numberPerPage: 8
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    let blockHotDealElem = jQuery('.block-hotdeal');
                    // prepare data
                    var html = '';
                    let params = {
                        isHotDeal: true
                    };
                    res.result.forEach(function (one) {
                        html += buildHtmlBlockProductOnHomePage(one, params);
                    });

                    // Hide/show block hotdeal.
                    if (res.result.length === 0) {
                        blockHotDealElem.hide();
                    } else {
                        blockHotDealElem.show();
                    }

                    jQuery('#list-hot-deal-product').html(html);
                } else {

                }
            },
            error : function (x, y, z) {
                console.log(x, y, z);
                openModalAlert('Thông báo', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: 'Đóng'});
            },
            complete: function (x, y) {
            }
        });

        // suggestion product
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action: 'load_product_by_tag',
                tag: '',
                page: 1,
                numberPerPage: 8
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    jQuery('#list-suggestion-product').html(' ');
                    // prepare data
                    var html = '';
                    res.result.forEach(function (one) {
                        html += buildHtmlBlockProductOnHomePage(one);
                    });

                    jQuery('#list-suggestion-product').html(html);

                    if (res.result.length == 8) {
                        jQuery('#load-more-by-tag').attr('data-page', 2).show();
                    } else {
                        jQuery('#load-more-by-tag').attr('data-page', 2).hide();
                    }
                } else {
                    jQuery('#load-more-by-tag').attr('disabled', 'disabled').html(res.message);
                }
            },
            error : function (x, y, z) {
                console.log(x, y, z);
                openModalAlert('Thông báo', 'Lỗi kết nối, vui lòng thử lại sau ít phút', {scene : 'info', btnOkText: 'Đóng'});
            },
            complete: function (x, y) {

            }
        });
    }

    // remove click on product name
    jQuery(document).on('click', '.wrap-product h4 a', function () {
        return false;
    });

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
</script>

<!--Feature search-->
<script type="text/javascript">
    var titleProduct = $(".wrap-product h4 a");
    var selectorBlockProduct = jQuery(".each-block-product");
    $('input[name=search]').on('change keyup', function () {
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