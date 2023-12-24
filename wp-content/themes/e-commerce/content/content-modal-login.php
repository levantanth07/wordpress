<!-- modal login -->
<?php if (!\GDelivery\Libs\Helper\User::isLogin()) :?>
<div class="modal fade popup modal-login" id="modal-login" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <div class="modal-body">
                <section class="login">
                    <h4><strong>Đăng nhập tài khoản</strong></h4>
                    <div class="form-group">
                        <input type="tel" name="cellphone" placeholder="Số điện thoại *" class="form-control">
                        <small id="emailHelp" class="form-text text-muted">* Mã xác thực (OTP) sẽ được gửi đến số điện thoại của bạn </small>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="otp-sms" name="otp-method" value="sms" checked>
                        <label class="form-check-label" for="otp-sms">Tin nhắn SMS</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="otp-voice" name="otp-method" value="voice">
                        <label class="form-check-label" for="otp-voice">Gọi điện</label>
                    </div>
                    <div class="form-group">
                        <button id="btn-request-otp">Tiếp tục</button>
                    </div>
                </section>
                <section class="input-otp">
                    <h4><strong>Xác thực số điện thoại nhận hàng</strong></h4>
                    <div class="form-group">
                        <input type="tel" name="otp" placeholder="Nhập OTP để xác thực số điện thoại nhận hàng" class="form-control">
                        <input type="hidden" name="cellphone" value="">
                    </div>
                    <button id="btn-login">Xác thực</button>
                    <div class="form-group">
                        <small id="login-message" class="form-text text-muted">OTP đã được gửi đến số điện thoại của bạn <b class="phone">0973345125</b></small>
                    </div>
                    <div class="form-group row-last">
                        <div class="text-center"><a id="re-send-otp" href="#">Gửi lại mã OTP</a></div>
                        <div class="text-center"><a id="change-cellphone" href="#"> Nhập lại số điện thoại</a></div>
                    </div>
                    <div class="form-group">
                        <small class="form-text text-muted" style="font-size: 10.5px; line-height: 16px;">Bằng việc xác thực, bạn xác nhận mình đã đủ 18 tuổi khi mua các sản phẩm có nhãn Rượu-bia.</small>
                    </div>
                </section>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript">
    // click btn request otp
    jQuery('#modal-login #btn-request-otp').click(function () {
        var thisElement = jQuery(this);
        var cellphone = jQuery('#modal-login section.login input[name=cellphone]').val();

        if (cellphone != '') {
            // loading this button
            thisElement.html('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>').attr('disabled', 'disabled');

            var sendData = {
                'cellphone' : cellphone,
                'method' : jQuery('input[name=otp-method]:checked').val()
            };

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('ajax-request-otp')?>',
                'dataType' : 'json',
                'data' : sendData,
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        jQuery('#modal-login section.login #emailHelp').html(res.message);

                        if (res.result) {
                            // hide login form
                            jQuery('#modal-login section.login').hide();

                            // prepare data for otp form
                            jQuery('#modal-login section.input-otp #login-message').html(res.message);
                            jQuery('#modal-login section.input-otp input[name=cellphone]').val(res.result);

                            // show otp form
                            jQuery('#modal-login section.input-otp').show();
                        }
                    } else {
                        jQuery('#modal-login section.login #emailHelp').html(res.message);
                    }

                    thisElement.html('Đăng nhập').removeAttr('disabled');
                },
                'error' : function (x, y, z) {
                    jQuery('#modal-login section.login #emailHelp').html('Vui lòng thử lại sau ít phút.');
                    thisElement.html('Đăng nhập').removeAttr('disabled');
                }
            }); // end ajax
        } else {
            jQuery('#modal-login section.login #emailHelp').html('Vui lòng nhập chính xác số điện thoại.');
        }
    });

    // click btn login
    jQuery('#modal-login #btn-login').click(function () {
        var thisElement = jQuery(this);
        var otp = jQuery('#modal-login section.input-otp input[name=otp]').val();
        var cellphone = jQuery('#modal-login section.input-otp input[name=cellphone]').val();

        if (otp != '') {
            // loading this button
            thisElement.html('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>').attr('disabled', 'disabled');

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('ajax-login')?>',
                'dataType' : 'json',
                'data' : {
                    'cellphone' : cellphone,
                    'otp' : otp
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        // fire Netcore event
                        if (isEnabledNetCore == 1) {
                            // selected restaurant
                            smartech('identify', cellphone);
                            smartech('dispatch', 'nc_login_success', {
                                'nc_user_phonenumber': cellphone
                            });
                        }

                        window.location.reload();
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                        thisElement.html('Đăng nhập').removeAttr('disabled');
                    }

                },
                'error' : function (x, y, z) {
                    openModalAlert('Thông báo', 'Vui lòng thử lại sau ít phút.', {scene : 'info', btnOkText: 'Đóng'});
                    thisElement.html('Đăng nhập').removeAttr('disabled');
                }
            }); // end ajax
        } else {
            openModalAlert('Thông báo', 'Vui lòng nhập mã OTP.', {scene : 'info', btnOkText: 'Đóng'});
        }
    });

    // re-input cellphone
    jQuery('#modal-login #change-cellphone').click(function () {
        jQuery('#modal-login input[name=cellphone]').val('');
        jQuery('#modal-login input[name=otp]').val('');

        // login form
        jQuery('#modal-login section.login').show();

        // otp form
        jQuery('#modal-login section.input-otp').hide();

        // clear message
        jQuery('#modal-login section.login #emailHelp').html('* Mã xác thực (OTP) sẽ được gửi đến số điện thoại của bạn');

        jQuery('#btn-request-otp').html('Tiếp tục');

        return false;
    });

    // todo resend otp
    jQuery('#re-send-otp').click(function () {
        var thisElement = jQuery(this);
        thisElement.html('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        jQuery('#modal-login section.input-otp #login-message').html('');
        var sendData = {
            'cellphone' : jQuery('#modal-login section.login input[name=cellphone]').val(),
            'method' : 'sms',
            'forceResend' : true
        };

        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=site_url('ajax-request-otp')?>',
            'dataType' : 'json',
            'data' : sendData,
            'success' : function (res) {
                jQuery('#modal-login section.input-otp #login-message').html(res.message);
                thisElement.html('Gửi lại mã OTP');
            },
            'error' : function (x, y, z) {
                jQuery('#modal-login section.input-otp #login-message').html('Vui lòng thử lại sau ít phút.');

                thisElement.html('Gửi lại mã OTP');
            }
        }); // end ajax
    });


    function openModalLogin()
    {
        jQuery('#modal-login').modal(
            {
                'show' : true,
                'backdrop' : 'static'
            }
        );
    }
</script>

<?php endif; ?>
<!-- end modal login -->