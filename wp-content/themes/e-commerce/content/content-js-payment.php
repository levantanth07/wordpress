<script type="text/javascript">
    var intervalCheckPayment;
    var processing = false;

    function requestPayment(orderId, paymentMethod)
    {
        // ajax create order
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=site_url('ajax-request-payment')?>',
            'data' : {
                paymentMethod : paymentMethod,
                orderId : orderId
            },
            'dataType' : 'json',
            'success' : function (res) {
                if (res.messageCode == 1) {
                    // order btn
                    jQuery('.btn-continue').removeAttr('disabled').html('Thanh toán');


                    if (paymentMethod == 'VNPT_EPAY_BANK_ONLINE') {
                        //var frameHeight = jQuery(window).height() - 100;
                        var frameHeight = 650;
                        jQuery('#iframe-bank-online-payment-modal #vnpt-epay-frame')
                            .attr('src', res.result.response)
                            .attr('width', "100%")
                            .attr('height', frameHeight + "px");
                        // open modal
                        jQuery('#iframe-bank-online-payment-modal').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });
                    } else if (paymentMethod == 'VNPAY_BANK_ONLINE' || paymentMethod == 'VNPAY_BANK_ONLINE_INTERNATIONAL_CARD') {
                        var content = 'Trình duyệt sẽ tự động chuyển hướng. Nếu không, vui lòng <a href="' + res.result.response +'">bấm vào đây</a>';

                        jQuery('#bank-online-payment-modal .modal-title').html('Thanh toán ' + res.result.partner.name);
                        jQuery('#bank-online-payment-modal .modal-body .info').html(content);
                        // open modal
                        jQuery('#bank-online-payment-modal').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });

                        location.href = res.result.response;
                    } else {
                        jQuery('#qr-code-payment-modal .modal-title').html('Thanh toán ' + res.result.partner.name);
                        jQuery('#qr-code-payment-modal .qr-code').attr('src', 'data:image/png;base64, ' + res.result.response);

                        if (paymentMethod == 'ZALOPAY') {
                            jQuery('#step-3-zalo').removeClass('d-none');
                            jQuery('#step-3-vnpay').addClass('d-none');
                        } else {
                            jQuery('#step-3-vnpay').removeClass('d-none');
                            jQuery('#step-3-zalo').addClass('d-none');
                        }

                        intervalCheckPayment = setInterval(
                            function () {
                                checkPayment(
                                    res.result.requestId,
                                    orderId
                                );
                            },
                            15000
                        );

                        // open modal
                        jQuery('#qr-code-payment-modal').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });

                        processing = true;
                    }
                } else {
                    openModalAlert('Cảnh báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                    jQuery('.btn-continue').removeAttr('disabled').html('Đặt hàng');
                }
            },
            'error' : function (x, y, z) {

            }
        }); // end ajax
    }

    function checkPayment(requestId, orderId, scene = 'qrcode')
    {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=site_url('ajax-check-payment')?>',
            'data' : {
                'requestId' : requestId,
                'orderId' : orderId
            },
            'dataType' : 'json',
            'success' : function (res) {
                if (res.messageCode == 1) {
                    // clear interval check payment first
                    clearInterval(intervalCheckPayment);
                    processing = false;

                    jQuery('#qr-code-payment-modal .close-action').html('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                    jQuery('#qr-code-payment-modal').modal('hide');

                    var orderDetailUrl = "<?=site_url('order-detail')?>?id=" + orderId;

                    jQuery('#modal-order-success #link-to-order-detail').attr('href', orderDetailUrl);
                    jQuery('#modal-order-success').modal(
                        {
                            'show' : true,
                            'backdrop' : 'static'
                        }
                    );
                } else if (res.messageCode == 30 && scene == 'bank_online') {
                    var orderPage = "<?=site_url('order-detail')?>?id=" + orderId;
                    // redirect to order detail page
                    location.href = orderPage;
                } else {

                }
            },
            'error' : function (x, y, z) {

            }
        }); // end ajax
    }
</script>