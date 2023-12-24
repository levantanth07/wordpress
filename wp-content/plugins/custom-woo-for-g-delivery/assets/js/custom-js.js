// custom text for form form check out
jQuery('.woocommerce-shipping-totals td').attr('data-title', 'Phí giao hàng');
jQuery('.woocommerce-shipping-totals th').html('Phí giao hàng');
setTimeout(function(){ jQuery('.woocommerce-shipping-totals th').html('Phí giao hàng'); }, 2000);

jQuery('.cart_totals h2').html('Tổng đơn hàng');
jQuery('.cart_totals .checkout-button').html('Thanh toán');

// change for up sell block
jQuery('section.up-sells').each(function() {
    jQuery(this).insertBefore( jQuery(this).prev('div.woocommerce-tabs') );
});
jQuery('section.up-sells h2:first-child').html('Sản phẩm thường được mua kèm với ....');
jQuery('section.up-sells ul').removeClass('columns-3').addClass('columns-6');
jQuery('.upsells .product.type-product').removeClass('first last');