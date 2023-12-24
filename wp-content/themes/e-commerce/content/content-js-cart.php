<script type="text/javascript">
    var scene = '<?=(isset($scene) ? $scene : 'cart')?>';
    var cartItemQuantity = <?=WC()->cart->get_cart_contents_count()?>;
    var isMobile = <?=(wp_is_mobile() ? 1 : 0)?>;
    var isLogin = <?=(\GDelivery\Libs\Helper\User::isLogin() ? 1 : 0)?>;

    function reloadCartAndCalculateShippingFee(scene = 'checkout')
    {
        var pickupAtRestaurant = Number(jQuery('input[name=pickup-at-restaurant]:checked').val());
        var paymentMethod = jQuery('input[name=payment-method]:checked').val();
        if (paymentMethod == '' || paymentMethod == undefined) {
            paymentMethod = 'COD';
        }

        if (pickupAtRestaurant == 1) {
            reloadCart(1, scene);
        } else {
            if (paymentMethod == 'COD') {
                reloadCart(3, scene);
            } else {
                reloadCart(2, scene);
            }
        }
    }

    ///////////// RELOAD CART /////////////////
    function reloadCart(calculateShippingFee = 0, scene = 'checkout') {
        jQuery('.amount-of-items').removeClass('animate__animated animate__rubberBand animate__fast');
        jQuery.ajax({
            type : 'get',
            url: '<?=admin_url('admin-ajax.php')?>',
            dataType: 'json',
            data : {
                'calculateShippingFee' : calculateShippingFee,
                'scene' : scene,
                'action': 'list_products_in_cart'
            },
            success: function (res) {
                if (res.messageCode == 1) {
                    // list products
                    var html = '';
                    res.result.products.forEach(function (one) {
                        var varition = one.product.variationNames ? ' - ' + one.product.variationNames : '';
                        html +=
                            '<li>' +
                            '   <div class="row no-gutters">' +
                            '      <div class="col-10">' +
                            '          <p>' + one.quantity +' x ' + one.product.name + varition + '</p>' +
                            '          <span>' + one.product.textTotalPrice + '₫</span>' +
                            '      </div>' +
                            '      <div class="col-2">' +
                            '          <a class="do-open-product-detail" data-product-id="' + one.product.id + '" href="#product-' + one.product.id + '"><i class="icon-pen"></i></a>' +
                            '          <a href="#" class="cart-remove-item" data-product-id="' + one.product.id + '" data-item-key="'+ one.key +'" data-item-name="'+ one.product.name +'"><i class="icon-remove-medium"></i></a>' +
                            '      </div>' +
                            '  </div>' +
                            '</li>';
                    });
                    jQuery('.cart .product-in-cart').html(html); // cart element

                    // totals
                    var totals = res.result.totals;

                    // list vouchers
                    if (res.result.selectedVouchers != null) {
                        var listDiscountVouchers = [];
                        var listCashVouchers = [];
                        res.result.selectedVouchers.forEach(function (oneVoucher) {
                            if (oneVoucher.type == 1) {
                                // ignore affiliate without evoucher campaign
                                if (oneVoucher.partnerId != 999 && oneVoucher.denominationValue > 0) {
                                    listCashVouchers.push(oneVoucher);
                                }
                            } else {
                                listDiscountVouchers.push(oneVoucher);
                            }
                        });

                        // discount voucher
                        var listDiscountVoucherHtml = ' ';
                        var listCodePromotionHLTML = '';
                        listCodePromotionHLTML += '<div class="left-btn">' +
                            '<i class="icon-arrow-left" onclick="scrollBtnLeft(\'#list-code-promotion\', 500)"></i>' +
                            '</div>';
                        if (listDiscountVouchers.length > 0) {
                            listDiscountVoucherHtml += '' +
                                '<div class="gift-code">' +
                                '   <p class="text-left">Giảm giá' +
                                '   <b class=" float-right total-discount">- ' + totals.totalDiscount.format() + '₫</b>' +
                                '   </p>' +
                                '</div>';

                            listDiscountVouchers.forEach(function (one) {
                                listDiscountVoucherHtml += '' +
                                    '<div class="gift-code">' +
                                    '   <li class="text-left">' +
                                        one.code +
                                    '   <b class="float-right">- ' + one.denominationValue.format() + '₫</b>' +
                                    '</li>' +
                                    '</div>';
                                listCodePromotionHLTML += '<div class="code">' +
                                    one.code +
                                    ' <img class="remove-voucher" data-voucher-code="' + one.code + '" ' +
                                    'src="<?= bloginfo('template_url') ?>/assets/images/remove-voucher.png"/>' +
                                    '</div>';
                            });
                        }
                        jQuery('#list-discount-vouchers').html(listDiscountVoucherHtml);
                        // Remove class active.
                        jQuery('#list-cash-vouchers').removeClass('active');

                        // cash voucher
                        var listCashVoucherHtml = ' ';
                        if (listCashVouchers.length > 0) {
                            listCashVoucherHtml += '' +
                                '<div class="voucher text-right">' +
                                '   <span class="float-left">Voucher</span>' +
                                '   <b class="">- ' + totals.totalCashVoucher.format() + '₫</b>' +
                                '</div>';

                            listCashVouchers.forEach(function (one) {
                                listCashVoucherHtml += '' +
                                    '<div class="voucher">' +
                                    '   <li class="text-left"> '+
                                    one.code +
                                    '       <b class="float-right">- ' + one.denominationValue.format() + '₫</b>' +
                                    '   </li>' +
                                    '</div>';
                                listCodePromotionHLTML += '<div class="code">' +
                                    one.code +
                                    ' <img class="remove-voucher" data-voucher-code="' + one.code + '" ' +
                                    'src="<?= bloginfo('template_url') ?>/assets/images/remove-voucher.png"/>' +
                                    '</div>';
                            });
                            // Add class active when have voucher
                            jQuery('#list-cash-vouchers').addClass('active');
                        }
                        // Add scroll right button
                        listCodePromotionHLTML += '<div class="right-btn">' +
                            '<i class="icon-arrow-right" onclick="scrollBtnRight(\'#list-code-promotion\', 500)"></i>' +
                            '</div>';

                        jQuery('#list-cash-vouchers').html(listCashVoucherHtml);
                        jQuery('#list-code-promotion').html(listCodePromotionHLTML);
                        // Set scroll if have multi voucher
                        showBtnScroll('#list-code-promotion', '.code');
                        if (listDiscountVouchers.length === 0 && listCashVouchers.length === 0) {
                            jQuery('#list-code-promotion').html('<span class="no-code">Bạn chưa thêm mã giảm giá nào</span>');
                        }
                    }


                    // total
                    var priceWithoutShipping = res.result.totalPrice - res.result.shipping.price;
                    var taxWithoutShipping = res.result.totalTax - res.result.shipping.tax;
                    jQuery('.summary .total-price-without-shipping').html(totals.totalPriceWithoutShipping.format() + '₫');
                    jQuery('.summary .total-pay-sum-before-tax').html((totals.totalPrice - totals.totalDiscount).format() + '₫');
                    jQuery('.summary .total-tax_temp').html(taxWithoutShipping.format() + '₫');
                    jQuery('.summary .cart_total_temp').html((priceWithoutShipping + taxWithoutShipping).format() + '₫');

                    jQuery('.summary .cart_total').html(res.result.total.format() + '₫');
                    jQuery('.summary .shipping-fee').html('+ ' + res.result.shipping.price.format() + '₫');
                    jQuery('.summary .total-tax').html(res.result.totalTax.format() + '₫');

                    <?php if (is_page('cart') || is_product_category()) :?>
                    // sum in cart page
                    jQuery('.sum.total-price-without-shipping').html('<span>Tổng tiền hàng</span>' + totals.totalPriceWithoutShipping.format() + '₫');
                    <?php else: ?>
                    // sum in other page
                    jQuery('.sum').html('<span>Tổng tiền phải trả</span><b class="cart-pay-sum">' + res.result.totalPaySum.format() + '₫</b>');
                    <?php endif; ?>

                    if (res.result.totalQuantity > 0) {
                        jQuery('.amount-of-items').html(res.result.totalQuantity).addClass('animate__animated animate__rubberBand animate__fast');
                        cartItemQuantity = res.result.totalQuantity;
                    }
                } else {
                    jQuery('.cart .product-in-cart').html('<li><div class="row no-gutters"><div class="col-12 alert alert-warning"> ' + res.message + '</div></div></li>');
                    jQuery('.cart .cart_total').html('0₫');
                    jQuery('.cart .total-tax').html('0₫');
                    jQuery('.cart .shipping-fee').html('0₫');
                    jQuery('.cart .total-price').html('0₫');

                    jQuery('.cart .total-price_temp').html('0₫');
                    jQuery('.cart .total-tax_temp').html('0₫');
                    jQuery('.cart .cart_total_temp').html('0₫');
                    jQuery('.cart .cart-pay-sum').html('0₫');

                    jQuery('.cart .total-discount').html('').parent().hide();

                    jQuery('#list-cash-vouchers').hide();
                    jQuery('#list-discount-vouchers').hide();

                    jQuery('.amount-of-items').html('0').addClass('animate__animated animate__rubberBand animate__fast');

                    jQuery('.sum.total-price-without-shipping').html('<span>Tổng tiền hàng</span>0₫');
                }

                jQuery('.global-loading').removeClass('show');
            },
            error : function (x, y, z) {

            }
        }); // end ajax
    } // end function reload cart

    ///////////// REMOVE CART ITEM ////////////
    function removeCartItem(item) {
        var cf = confirm('Xóa "' + item.name + '" khỏi giỏ hàng?');

        if (cf) {
            jQuery.ajax({
                type: 'post',
                url: '<?=admin_url('admin-ajax.php')?>',
                dataType: 'json',
                data: {
                    itemKey: item.key,
                    action: 'remove_cart_item'
                },
                success: function (res) {
                    if (res.messageCode == 1) {
                        /// alert('Đã xóa sản phẩm khỏi giỏ hàng');
                        reloadCartAndCalculateShippingFee('cart');
                        jQuery('#product-'+ item.productId + ' .quantity').html('0');
                        jQuery('#product-' + item.productId + ' .add-to-cart .quantity').hide();
                        jQuery('#product-' + item.productId + ' .add-to-cart .minus').hide();
                        // hidden product detail modal
                        jQuery('#product-detail-modal').modal('hide');
                    } else {
                        alert(res.message);
                    }
                },
                error: function (x, y, z) {

                }
            }); // end ajax
        } else {
            changeQuantity('plus');
            return false;
        }
    }

    //////////// APPLY VOUCHER /////////////////
    function applyVoucher(voucherCode, partnerId, actionElement, productId = 0, options = [])
    {
        if (voucherCode != '') {
            var sendData = {
                action: 'apply_voucher',
                beHonest : '<?=wp_create_nonce('apply_voucher')?>',
                voucherCode : voucherCode,
                partnerId: partnerId
            };
            if (typeof options['quantity'] !== 'undefined') {
                sendData.quantityDiscount = options['quantity'];
            }
            if (typeof options['index-of-line-product'] !== 'undefined') {
                sendData.indexOfLineProduct = options['index-of-line-product'];
            }
            if (typeof options['productIdsInfo'] !== 'undefined') {
                sendData.productIdsInfo = options['productIdsInfo'];
            }

            if (productId > 0) {
                sendData = {...sendData, productId: productId};
            }

            jQuery('#promotionModal').modal('hide');
            jQuery('.global-loading').addClass('show')
            actionElement.attr('disabled', 'disabled');
            jQuery.ajax({
                url : '<?=admin_url('admin-ajax.php')?>',
                type : 'post',
                dataType : 'json',
                data : sendData,
                success : function (res) {
                    if (res.messageCode == 1) {
                        if (res.result.partnerId == 999 && res.result.type ==1 && res.result.denominationValue == 0) {
                            // affiliate without evoucher campaign
                            openModalAlert('Thông báo', 'Áp dụng mã giới thiệu thành công.', {scene : 'info', btnOkText: 'Đóng'});
                        }

                        reloadCart();
                        jQuery('#promotionModal').modal('hide');
                    } else if (res.messageCode == 406) {
                        // select product to apply
                        var html = '';
                        var voucher = res.result;
                        res.result.applyForItems.forEach(function (one) {
                            var price = 0;
                            if (one.salePrice > 0) {
                                price = one.salePrice;
                            } else {
                                price = one.regularPrice;
                            }
                            var discountPrice = 0;
                            if (voucher.type == 5) {
                                discountPrice = price - (voucher.denominationValue * price/100);
                            } else {
                                discountPrice = price - voucher.denominationValue;
                            }
                            if (discountPrice < 0) {
                                discountPrice = 0;
                            }
                            html += '<li>' +
                                '   <div class="media">' +
                                '       <img src="' + one.thumbnail + '" class="mr-3" alt="">' +
                                '       <div class="media-body">' +
                                '           <div class="info">' +
                                '               <h5>' + one.name + '</h5>' +
                                '               <span class="regular-price">' + price.format() + 'đ</span>' +
                                '               <span class="sale-price">' + discountPrice.format() + 'đ</span>' +
                                '           </div>' +
                                '           <a class="btn-select-product choose-product-for-voucher" href="#" data-product-id=' + one.id + ' data-partner-id=' + voucher.partnerId + ' data-voucher-code=' + voucher.code + '>Chọn món</a>' +
                                '       </div>' +
                                '   </div>' +
                                '</li>';
                        });
                        jQuery('#select-product-for-voucher .modal-body ul').html(html);
                        jQuery('#select-product-for-voucher').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });
                    } else if (res.messageCode == 407) {
                        // select product to apply
                        var html = '<ul>';
                        var voucher = res.result;
                        res.result.applyForItems.forEach(function (one) {
                            let price;
                            if (one.salePrice > 0) {
                                price = one.salePrice;
                            } else {
                                price = one.regularPrice;
                            }
                            html += '<li>' +
                                '   <div class="media">' +
                                '       <img src="' + one.thumbnail + '" class="mr-3" alt="">' +
                                '       <div class="media-body">' +
                                '           <div class="info">' +
                                '               <h5>' + one.name + '</h5>' +
                                '               <span class="regular-price">' + price.format() + 'đ</span>' +
                                '               <span class="sale-price">' + one.priceAfterDiscount.format() + 'đ</span>' +
                                '           </div>' +
                                '           <div class="number">' +
                                '               <p class="product-change-quantity" data-min="' + one.minQuantityDiscount + '" data-max="' + one.maxQuantityDiscount + '">' +
                                '                   <span class="minus" onclick="minusNumber(this, {min: ' + one.minQuantityDiscount + ', max: ' + one.maxQuantityDiscount + '})"><i class="icon-minius minus-quantity"></i></span>' +
                                '                       <span class="num product-quantity">' + one.minQuantityDiscount + '</span>' +
                                '                   <span class="plus" onclick="plusNumber(this, {min: ' + one.minQuantityDiscount + ', max: ' + one.maxQuantityDiscount + '})"><i class="icon-add plus-quantity"></i></span>' +
                                '               </p>' +
                                '               <a class="btn-select-product choose-product-for-voucher" href="#" data-product-id=' +
                                                one.id + ' data-partner-id=' + voucher.partnerId + ' data-voucher-code=' +
                                                voucher.code + ' data-index-of-line-product=' + one.indexOfLineProduct + '>Chọn món</a>' +
                                '           </div>' +
                                '       </div>' +
                                '   </div>' +
                                '</li>';
                        });
                        html += '</ul>';
                        jQuery('#select-product-for-voucher .modal-body').html(html);
                        jQuery('#select-product-for-voucher').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });
                    } else if (res.messageCode == 408) {
                        // select product to apply
                        let html = '';
                        let voucher = res.result;
                        let lineProduct = '',
                            indexBlock = 0,
                            listBlock = [],
                            listLineProduct = [];
                        res.result.applyForItems.forEach(function (one) {
                            let price;
                            if (one.salePrice > 0) {
                                price = one.salePrice;
                            } else {
                                price = one.regularPrice;
                            }
                            let html = '<li data-product-id=' + one.id + ' data-index-of-line-product=' + one.indexOfLineProduct + ' data-rk-code=' + one.rkCode + '>' +
                                '   <div class="media">' +
                                '       <img src="' + one.thumbnail + '" class="mr-3" alt="">' +
                                '       <div class="media-body">' +
                                '           <div class="info">' +
                                '               <h5>' + one.name + '</h5>' +
                                '               <span class="regular-price">' + price.format() + 'đ</span>' +
                                '               <span class="sale-price">' + one.priceAfterDiscount.format() + 'đ</span>' +
                                '           </div>' +
                                '           <div class="number">' +
                                '               <p class="product-change-quantity" data-min="' + one.minQuantityDiscount + '" data-max="' + one.maxQuantityDiscount + '">' +
                                '                   <span class="minus" onclick="minusNumber(this, {min: ' + one.minQuantityDiscount + ', max: ' + one.maxQuantityDiscount + '})"><i class="icon-minius minus-quantity"></i></span>' +
                                '                       <span class="num product-quantity">' + one.minQuantityDiscount + '</span>' +
                                '                   <span class="plus" onclick="plusNumber(this, {min: ' + one.minQuantityDiscount + ', max: ' + one.maxQuantityDiscount + '})"><i class="icon-add plus-quantity"></i></span>' +
                                '               </p>' +
                                '           </div>' +
                                '       </div>' +
                                '   </div>' +
                                '</li>';
                            if (lineProduct != one.indexOfLineProduct) {
                                lineProduct = one.indexOfLineProduct;
                                indexBlock++;
                                listLineProduct[indexBlock - 1] = lineProduct;
                            }
                            if (typeof listBlock[indexBlock - 1] === 'undefined') {
                                listBlock[indexBlock - 1] = html;
                            } else  {
                                listBlock[indexBlock - 1] += html;
                            }

                        });
                        jQuery.each(listBlock, function (index, one) {
                            if (voucher.isParallelCondition) {
                                html += '<div class="block-item-discount" id="' + listLineProduct[index] + '">' +
                                    '<input type="checkbox" name="list_block[]" value="' + listLineProduct[index] + '">';
                            } else {
                                html += '<div class="block-item-discount" id="' + listLineProduct[index] + '">' +
                                    '<input type="radio" name="list_block" value="' + listLineProduct[index] + '">';
                            }
                            html += '<ul class="block-discount">' + one + '</ul></div>';
                        });
                        if (html) {
                            html += '<div class="add-all">' +
                                '<a class="btn-select-product select-all-product-for-voucher" href="#" data-partner-id=' + voucher.partnerId + ' data-voucher-code=' +
                                voucher.code + '>Chọn món</a></div>';
                        }
                        jQuery('#select-product-for-voucher .modal-body').html(html);
                        jQuery('#select-product-for-voucher').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                        jQuery('.global-loading').removeClass('show');
                    }
                },
                error : function (x, y, z) {
                    openModalAlert('Lỗi', 'Có lỗi xảy ra khi áp dụng mã giảm giá, vui lòng thử lại', {scene : 'info', btnOkText: 'Đóng'});
                },
                complete: function (x, y) {
                    jQuery('.code-discount input[name=voucher-code]').val('');
                    jQuery('.code-discount input[name=partner]').val(0);
                    actionElement.removeAttr('disabled');
                    jQuery('.global-loading').removeClass('show');
                }
            });
        } else {
            openModalAlert('Thông báo', 'Bạn cần nhập mã giảm giá', {scene : 'info', btnOkText: 'Đóng'});
        }
    }

    // remove cart item
    jQuery(document).on('click', '.cart-remove-item', function (e) {
        var thisElement = jQuery(this);

        // get data item name and item key
        var item = {name: thisElement.attr('data-item-name'), key: thisElement.attr('data-item-key'), productId: thisElement.attr('data-product-id')}

        // run function removeCartItem
        removeCartItem(item);

        return false;
    });

    // click apply voucher
    jQuery('#btn-apply-voucher').click(function () {
        var thisElement = jQuery(this);
        var voucherCode = jQuery('.code-discount input[name=voucher-code]').val();
        var partnerId = jQuery('.code-discount input[name=partner]').val();

        applyVoucher(voucherCode, partnerId, thisElement);

        return false;
    });

    // select product for voucher voucher
    jQuery(document).on('click', '.choose-product-for-voucher', function (e) {
        var thisElement = jQuery(this);
        var voucherCode = thisElement.attr('data-voucher-code');
        var partnerId = thisElement.attr('data-partner-id');
        var productId = thisElement.attr('data-product-id');
        let options = [];
        options['quantity'] = Number(thisElement.parent().find('.product-change-quantity span.product-quantity').text());
        options['index-of-line-product'] = thisElement.attr('data-index-of-line-product');

        jQuery('#select-product-for-voucher').modal('hide');
        applyVoucher(voucherCode, partnerId, thisElement, productId, options);

        return false;
    });

    // select all product for voucher voucher
    jQuery(document).on('click', '.select-all-product-for-voucher', function (e) {
        var thisElement = jQuery(this);
        var voucherCode = thisElement.attr('data-voucher-code');
        var partnerId = thisElement.attr('data-partner-id');
        let productIdsInfo = [];
        jQuery('#select-product-for-voucher .block-item-discount input:checked').each(function (e) {
            let thisElem = jQuery(this),
                idBlock = thisElem.val(),
                blockItemDiscountElem = jQuery('#' + idBlock);

            blockItemDiscountElem.find('ul li').each(function (e) {
                let thisElem = jQuery(this);
                productIdsInfo.push({
                    productId: thisElem.attr('data-product-id'),
                    quantity: Number(thisElem.find('.product-change-quantity').find('.product-quantity').text()),
                    lineProduct: thisElem.attr('data-index-of-line-product'),
                    rkCode: thisElem.attr('data-rk-code')
                });
            });
        });
        if (productIdsInfo.length === 0) {
            return false;
        }

        let options = [];
        options['productIdsInfo'] = productIdsInfo;
        options['index-of-line-product'] = thisElement.attr('data-index-of-line-product');

        jQuery('#select-product-for-voucher').modal('hide');
        applyVoucher(voucherCode, partnerId, thisElement, 0, options);

        return false;
    });

    // remove voucher
    jQuery(document).on('click', '.remove-voucher', function (e) {
        var thisElement = jQuery(this);
        var voucherCode = thisElement.attr('data-voucher-code');

        if (voucherCode != '') {
            var cf = confirm('Bỏ voucher "' + voucherCode + '" khỏi giỏ hàng?');

            if (cf) {
                jQuery('.global-loading').addClass('show');
                thisElement.html('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>').attr('disabled', 'disabled');
                jQuery.ajax({
                    url : '<?=site_url('ajax-remove-voucher')?>',
                    type : 'post',
                    dataType : 'json',
                    data : {
                        voucherCode : voucherCode,
                    },
                    success : function (res) {
                        if (res.messageCode == 1) {
                            reloadCart();
                        } else {
                            openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                            jQuery('.global-loading').removeClass('show');
                        }
                        jQuery('.code-discount input[name=voucher-code]').val('')
                        thisElement.html('x').removeAttr('disabled');
                    },
                    error : function (x, y, z) {
                        openModalAlert('Lỗi', 'Có lỗi xảy ra khi áp dụng mã giảm giá, vui lòng thử lại', {scene : 'info', btnOkText: 'Đóng'});
                        thisElement.html('x').removeAttr('disabled');
                    }
                });
            }
        } else {
            openModalAlert('Thông báo', 'Có lỗi xảy ra, bạn vui lòng tải lại trang và thử lại.', {scene : 'info', btnOkText: 'Đóng'});
        }

        return false;
    });

    jQuery(document).on('click', '.btn-apply-promotion', function() {
        var voucherCode = jQuery(this).attr('data-serial');
        var partnerId = jQuery(this).attr('data-partner-id');
        if (voucherCode != '') {
            $('.modal-promotion').modal('hide');
            jQuery('.code-discount input[name=voucher-code]').val(voucherCode);
            jQuery('.code-discount input[name=partner]').val(partnerId);
            document.getElementById("btn-apply-voucher").click();
        }

        return false;
    });

    jQuery('.modal-promotion').on('hide.bs.modal', function () {
        $('.list-promotion ul').html('');
        $('.list-promotion ul').addClass('hidden');
        $('.list-promotion .lds-ellipsis').removeClass('hidden');
    });

    jQuery('.modal-promotion').on('show.bs.modal', function () {
        $('.list-promotion ul').html('');
        $('.list-promotion ul').addClass('hidden');
        $('.list-promotion .lds-ellipsis').removeClass('hidden');
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                action : "load_tgs_wallet",
                beHonest : "<?=wp_create_nonce('load_tgs_wallet')?>"
            },
            beforeSend : function () {

            },
            success : function (res) {
                var html = '';
                if (res.messageCode == 1) {
                    res.result.forEach(function (one) {
                        html += '<li>' +
                                '   <div class="media">' +
                                '       <img src="' + one.promotionThumbnail + '" class="mr-3" alt="' + one.promotionTitle + '">' +
                                '       <div class="media-body">' +
                                '           <p><span>Còn lại 1 mã</span>' + one.expiryDate + '</p>' +
                                '           <h5 class="mt-0">' + one.promotionTitle + '</h5>' +
                                '           <a class="btn-apply-promotion" href="#" data-serial="' + one.serialNo + '" data-partner-id="' + one.partnerId + '">Sử dụng ngay</a>' +
                                '       </div>' +
                                '   </div>' +
                                '</li>';
                    });
                } else {
                    html = '<li><div class="unavailable-voucher">' + res.message + '</div></li>';
                }

                jQuery('.list-promotion ul').html(html);
                jQuery('.list-promotion ul').removeClass('hidden');
                jQuery('.list-promotion .lds-ellipsis').addClass('hidden');
            },
            error : function (x, y, z) {
            }
        });
    });

    // open cart page on mobile
    jQuery('.btn-open-cart').click(function () {
        location.href = '<?=site_url('cart')?>';

        return false;
    });

    // Reload cart when add product limit
    function reloadCartLimit(successCallback) {
        jQuery.ajax({
            url : '<?=admin_url('admin-ajax.php')?>',
            type : 'post',
            dataType : 'json',
            data : {
                beHonest: '<?=wp_create_nonce('reload_cart')?>',
                action: 'reload_cart'
            },
            success : function (res) {
                successCallback(res);
            }
        });
    }
    
    function plusNumber(e, options = []) {
        let elemPlus = jQuery(e),
            blockChangeQuantity = elemPlus.parent(),
            elemQuantity = blockChangeQuantity.find('.product-quantity'),
            currentValue = Number(elemQuantity.text()),
            newValue = currentValue + 1,
            maxValue = 999;

        if (currentValue === maxValue) {
            return false;
        }

        if (options.max) {
            maxValue = options.max;
        }
        if (newValue <= maxValue) {
            elemQuantity.text(newValue);
        }
    }
    
    function minusNumber(e, options = []) {
        let elemMinus = jQuery(e),
            blockChangeQuantity = elemMinus.parent(),
            elemQuantity = blockChangeQuantity.find('.product-quantity'),
            currentValue = Number(elemQuantity.text()),
            newValue = currentValue -1,
            minValue = 1;

        if (currentValue === minValue) {
            return false;
        }

        if (options.min) {
            minValue = options.min;
        }
        if (newValue >= minValue) {
            elemQuantity.text(newValue);
        }
    }

</script>