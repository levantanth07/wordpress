<?php
$currentUser = wp_get_current_user();

if (!$currentUser) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

$cancelReason = [
    'Nhà hàng hết món',
    'Không tìm thấy tài xế',
    'Giá món bị sai giá',
    'Nhà hàng quá tải không phục vụ được',
    'Không có nhân viên giao hàng',
    'Đồ ăn kém chất lượng',
    'Khác',
];
?>
<!-- Modal modal-need-to-cancel -->
<div class="modal-transfer modal fade" id="modal-need-to-cancel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Yêu cầu hủy</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="cancel-reason">Lý do hủy</label>
                            <select class="form-control" id="cancel-reason" name="cancel-reason">
                                <?php foreach ($cancelReason as $reason) :?>
                                    <option value="<?=$reason?>"><?=$reason?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cancel-note">Ghi chú</label>
                            <textarea class="form-control" name="note" id="cancel-note"></textarea>
                        </div>
                        <button class="btn-submit btn btn-need-to-cancel">Yêu cầu hủy</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- end modal modal-need-to-cancel -->

<!-- Modal modal-need-to-transfer -->
<div class="modal-transfer modal fade" id="modal-need-to-transfer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Yêu cầu chuyển đơn</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="transfer-reason">Lý do chuyển đơn</label>
                            <select class="form-control" id="transfer-reason" name="transfer-reason">
                                <option value="Nhà hàng quá tải đơn">Nhà hàng quá tải đơn</option>
                                <option value="Nhà hàng hết hàng">Nhà hàng hết hàng</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cancel-note">Ghi chú</label>
                            <textarea class="form-control" name="note" id="cancel-note"></textarea>
                        </div>
                        <button class="btn-submit btn btn-need-to-transfer">Yêu cầu chuyển</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- end modal modal-need-to-transfer -->

<!-- Modal change restaurant -->
<div class="modal-transfer modal fade" id="modal-change-restaurant" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Chuyển nhà hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="exampleFormControlSelect1">Nhà hàng hiện tại</label>
                            <select name="current-restaurant" class="form-control" id="exampleFormControlSelect1"></select>
                        </div>
                        <div class="form-group">
                            <label for="exampleFormControlSelect2">Nhà hàng chuyển sang</label>
                            <select name="destination-restaurant" class="form-control" id="exampleFormControlSelect2"></select>
                        </div>

                        <div class="form-group">
                            <label for="cancel-reason">Lý do chuyển đơn</label>
                            <select class="form-control" id="change-restaurant-reason" name="change-reason">
                                <option value="Nhà hàng quá tải đơn">Nhà hàng quá tải đơn</option>
                                <option value="Nhà hàng hết hàng">Nhà hàng hết hàng</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cancel-note">Ghi chú</label>
                            <textarea class="form-control" name="note" id="cancel-note"></textarea>
                        </div>
                        <button class="btn-submit btn btn-change-restaurant">Xác nhận</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal modal-cancel -->
<div class="modal-transfer modal fade" id="modal-cancel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Hủy đơn</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="cancel-reason">Lý do hủy</label>
                            <select class="form-control" id="cancel-reason" name="cancel-reason">
                                <?php foreach ($cancelReason as $reason) :?>
                                    <option value="<?=$reason?>"><?=$reason?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cancel-note">Ghi chú</label>
                            <textarea class="form-control" name="note" id="cancel-note"></textarea>
                        </div>
                        <button type="" class="btn-submit btn btn-cancel-order">Hủy đơn hàng</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- end modal modal-cancel -->

<!-- Modal complete order -->
<div class="modal-transfer modal fade" id="modal-complete" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Hoàn tất đơn hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <div class="form-group">
                            <label for="billNumber">Nhập số bill</label>
                            <input type="input" class="form-control" id="bill-number" placeholder="Số bill" name="bill-number">
                        </div>
                        <div class="form-group">
                            <label for="checkNumber">Nhập số check</label>
                            <input type="input" class="form-control" id="check-number" placeholder="Số check" name="check-number">
                        </div>
                        <button class="btn-submit btn btn-complete">Hoàn tất</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal update info order -->
<div class="modal-transfer modal fade" id="modal-update-order" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Cập nhật thông tin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <input type="hidden" name="order_id" value="">
                        <div class="form-group">
                            <label for="payment_method">Đối tác thanh toán</label>
                            <select name="payment_method" class="form-control" id="payment_method">
                                <option value="VNPAY">Vnpay</option>
                                <option value="ZALOPAY">Zalo pay</option>
                                <option value="MOMO">Momo</option>
                                <option value="SHOPEE_PAY">Shopee Pay</option>
                                <option value="VNPT_EPAY_BANK_ONLINE">VNPT ePay (ATM/Visa/Master...)</option>
                                <option value="VNPAY_BANK_ONLINE">Thẻ ngân hàng nội địa</option>
                                <option value="VNPAY_BANK_ONLINE_INTERNATIONAL_CARD">Thẻ quốc tế</option>
                                <option value="COD">Nhận tiền khi giao hàng</option>
                                <option value="<?=\GDelivery\Libs\Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME?>">G-Business</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="is_paid">Trạng thái đã thanh toán online</label>
                            <select name="is_paid" class="form-control" id="is_paid">
                                <option value="1">Đã thanh toán</option>
                                <option value="0">Chưa thanh toán</option>
                            </select>
                        </div>
                        <!--<div class="form-group">
                            <label for="vendor_transport">Phương thức vận chuyển</label>
                            <select name="vendor_transport" class="form-control" id="vendor_transport">
                                <option value="restaurant">Nhà hàng tự vận chuyển</option>
                                <option value="grab_express"> Grab Express giao hàng</option>
                            </select>
                        </div>-->
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" class="form-control" id="status">
                                <?php
                                $arrStatus = \GDelivery\Libs\Helper\Order::$arrayStatus;
                                foreach ($arrStatus as $key => $item) :
                                    if ($user->role != 'operator'
                                        || (
                                            $user->role == 'operator'
                                            && (
                                                $key == 'waiting-payment'
                                                || $key == 'pending'
                                            )
                                        )
                                    ) :
                                ?>
                                    <option value="<?php echo $key; ?>"><?php echo $item; ?></option>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_partner_transaction_id">Mã giao dịch ở hệ thống đối tác</label>
                            <input type="text" name="payment_partner_transaction_id" class="form-control" id="payment_partner_transaction_id">
                        </div>
                        <button class="btn-submit btn btn-update-order">Xác nhận</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal Change Items -->
<div class="modal fade" id="modal-switch-item" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Chọn sản phẩm thay thế</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" class="lineItemId" value="">
                    <input type="hidden" class="merchantId" value="">
                    <input type="hidden" class="currentProductId" value="">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-7">
                                <label for="newProductId">Sản phẩm</label>
                                <select name="newProductId" class="form-control single-select2" id="newProductId">
                                    <option value="">Chọn sản phẩm thay thế</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="quantity">Số lượng</label>
                                <input type="number" class="form-control newQuantity" name="quantity" value="1">
                            </div>
                            <div class="col-md-3">
                                <div class="text-right">
                                    <label for="quantity">Thành tiền</label>
                                </div>
                                <div class="text-right">
                                    <strong class="totalProductPrice">0</strong>đ
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="item-product-to-switch">

                    </div>
                    <div class="form-group mt-3">
                        <label for="itemNote">Ghi chú</label>
                        <textarea class="form-control changeItemNote" name="note" cols="3"></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button class="btn btn-secondary" class="close" data-dismiss="modal" aria-label="Close">Hủy</button>
                        <button type="button" class="btn btn-warning btn-apply-change">Đồng ý</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Modal Change Items -->
<div class="modal fade" id="modal-note-item" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ghi chú</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" class="itemNoteLineItem">
                <textarea class="form-control mb-2 txtItemNote" name="itemNote" id="itemNote" cols="3" rows="2"></textarea>
                <button type="button" class="btn btn-success btn-make-item-note">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click','.btn-make-item-note',function(){
            let note = $('.txtItemNote').val();
            let lineItemId = $('.itemNoteLineItem').val();
            if (!note) {
                alert('Vui lòng nhập nội dung ghi chú');
                return false;
            }

            $.ajax({
                'type' : 'POST',
                'url' : '/wp-json/api/v1/order/make-item-note',
                'dataType' : 'json',
                data: {
                    orderId: $('.orderId').val(),
                    lineItemId: lineItemId,
                    note: note
                },
                beforeSend: function() {
                    $('.btn-make-item-note').html('<span class="fa fa-spinner fa-spin"></span> Processing');
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Tạo ghi chú thành công');
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                }
            });
        });
        $(document).on('click','.btn-apply-change',function(){
            let lineItemId = $('.lineItemId').val();
            let currentProductId = $('.currentProductId').val();
            let productId = $('.main-product input.instead-product').val();
            let variationId = $('.variations ul li a.instead-product.active').attr('data-id');
            let quantity = $('.newQuantity').val();
            let toppingIds = [];
            $('.toppings ul li input:checked').each(function() {
                toppingIds.push($(this).val());
            });

            let modifiers = [];
            $('.item-modifier .tags-list li a.active').each(function() {
                let data = [
                    {
                        'id': $(this).attr('data-id')
                    }
                ];
                let md = {
                    'categoryId': $(this).attr('parent-id'),
                    'data': data
                };
                modifiers.push(md);
            });
            if (!productId && !variationId) {
                alert('Vui lòng chọn sản phẩm thay thế!');
                return false;
            }
            $.ajax({
                'type' : 'POST',
                'url' : '/wp-json/api/v1/apply-switch-product',
                'dataType' : 'json',
                data: {
                    lineItemId: lineItemId,
                    currentProductId: currentProductId,
                    productId: productId,
                    variationId: variationId,
                    quantity: quantity,
                    toppingIds: toppingIds,
                    modifiers: modifiers,
                    orderId: $('.orderId').val(),
                    note: $('.changeItemNote').val(),
                    userId: <?=$currentUser->ID?>
                },
                beforeSend: function() {
                    $('.btn-apply-change').html('<span class="fa fa-spinner fa-spin"></span> Processing');
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Yêu cầu đổi món thành công');
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                    $('.btn-apply-change').html('Đồng ý');
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                }
            });
        });

        $(document).on('click','.variations ul li a',function(){
            $('.variations ul li a').removeClass('active');
            $(this).addClass('active');
        });

        $(document).on('click','.item-modifier ul li a',function(){
            $(this).parent().parent().find('a').removeClass('active');
            $(this).addClass('active');
        });

        $(document).on('click','.instead-product',function(){
            getTotal();
        });

        $("body .newQuantity").change(function() {
            getTotal();
        });

        function getTotal() {
            let total = 0;

            let mainPrice = $('.main-product input.instead-product').attr('data-price');
            let quantity = parseFloat($('.newQuantity').val());
            if (mainPrice) {
                quantity = quantity ? quantity : 1;
                total += parseFloat(mainPrice) * quantity;
            }

            let vPrice = $('.variations ul .instead-product.active').attr('data-price');
            if (vPrice) {
                total += parseFloat(vPrice) * quantity;
            }

            $('.toppings ul li input:checked').each(function() {
                total += parseFloat($(this).attr('data-price')) * quantity;
            });
            $('.totalProductPrice').html(total.toLocaleString('en-US'));
        }

        $('.single-select2').select2({
            minimumInputLength: 3,
            allowClear: true,
            placeholder: "Chọn sản phẩm thay thế",
            ajax: {
                url: '/wp-json/api/v1/product/list',
                type: 'GET',
                dataType: 'json',
                data: function (params) {
                    return {
                        keyword: params.term,
                        merchantId: $('.merchantId').val(),
                        productId: $('.currentProductId').val(),
                    };
                },
                processResults: function (response, params) {
                    return {
                        results: $.map(response.result.data, function (item) {
                            return {
                                text: item.name,
                                id: item.id,
                                data: item
                            };
                        })
                    };
                }
            }
        });

        $('.single-select2').on('select2:select', function (e) {
            let id = e.params.data.id;
            $.ajax({
                'type' : 'GET',
                'url' : '/wp-json/api/v1/switch-product/' + id,
                'dataType' : 'json',
                beforeSend: function() {
                    $('.item-product-to-switch').html('<div>Loading ...</div>');
                },
                'success' : function (res) {
                    if (res.messageCode == 200) {
                        $('.item-product-to-switch').html(res.result.html);
                        $('.totalProductPrice').html(res.result.strPrice);
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                }
            }); // end ajax
        });
    });
</script>

<script type="text/javascript">

    jQuery('.switch-item').click(function () {
        let lineItemId = $(this).attr('line-id');
        $('.lineItemId').val(lineItemId);

        let merchantId = $(this).attr('merchant-id');
        $('.merchantId').val(merchantId);

        let currentProductId = $(this).attr('product-id');
        $('.currentProductId').val(currentProductId);

        jQuery('#modal-switch-item').modal({
            'show' : true,
            'backdrop' : 'static'
        });
    });

    jQuery('.note-item').click(function () {
        let lineItemId = $(this).attr('line-id');
        $('.itemNoteLineItem').val(lineItemId);
        jQuery('#modal-note-item').modal({
            'show' : true,
            'backdrop' : 'static'
        });
    });

    // change status
    jQuery('.change-status').click(function () {
        var thisElement = jQuery(this);
        var action = thisElement.attr('data-action');
        var orderId = thisElement.attr('data-order-id');
        var thisOldHtml = thisElement.html();
        var status = thisElement.attr('data-order-status');
        var statusText = thisElement.attr('data-order-status-text');
        var orderPrice = thisElement.attr('data-order-price');
        var paymentMethod = thisElement.attr('data-payment-method');
        var extraData = thisElement.attr('data-extra-data') ? JSON.parse(thisElement.attr('data-extra-data')) : {};

        // loading
        thisElement.html('<span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        if (action == 'changeRestaurant') {
            thisElement.html(thisOldHtml);
            jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
            jQuery.ajax({
                'type' : 'get',
                'url' : '<?=site_url('/ajax-order-detail')?>',
                'dataType' : 'json',
                'data' : {
                    'orderId' : orderId
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        // current restaurant
                        jQuery('#modal-change-restaurant select[name=current-restaurant]').html('<option>' + res.result.order.restaurant.name + '</option>');

                        // destination restaurant
                        var htmlDestinationRestaurant = '';
                        res.result.availableRestaurant.forEach(function (one) {
                            if (one.restaurant.code != res.result.order.restaurant.code) {
                                htmlDestinationRestaurant += '<option value="' + one.restaurant.code + '">' + one.name + ' ( ' + (one.restaurant.distance/1000).toFixed(1) + 'km)</option>';
                            }
                        });
                        jQuery('#modal-change-restaurant select[name=destination-restaurant]').html(htmlDestinationRestaurant);

                        // add order id
                        jQuery('#modal-change-restaurant .btn-change-restaurant').attr('data-order-id', res.result.order.id);

                        // old html
                        thisElement.html(thisOldHtml);

                        // show modal
                        jQuery('#modal-change-restaurant').modal({
                            'show' : true,
                            'backdrop' : 'static'
                        });
                    } else {
                        alert(res.message);
                        // old html
                        thisElement.html(thisOldHtml);
                    }
                },
                'error' : function (x, y, z) {
                    // old html
                    thisElement.html(thisOldHtml);
                }
            }); // end ajax
        } else if (action == 'cancel') {
            //order id
            jQuery('#modal-cancel .btn-cancel-order').attr('data-order-id', orderId);

            // old html
            thisElement.html(thisOldHtml);

            // show modal
            jQuery('#modal-cancel').modal({
                'show' : true,
                'backdrop' : 'static'
            });
            jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
        } else if (action == 'needToCancel') {

            // order id
            jQuery('#modal-need-to-cancel .btn-need-to-cancel').attr('data-order-id', orderId);

            // old html
            thisElement.html(thisOldHtml);
            jQuery('#modal-need-to-cancel').modal(
                {
                    'show' : true,
                    'backdrop' : 'static'
                }
            );
            jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
        } else if (action == 'needToTransfer') {
            // order id
            jQuery('#modal-need-to-transfer .btn-need-to-transfer').attr('data-order-id', orderId);

            // old html
            thisElement.html(thisOldHtml);
            jQuery('#modal-need-to-transfer').modal(
                {
                    'show' : true,
                    'backdrop' : 'static'
                }
            );
            jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
        } else if (action == 'complete') {
            // order id
            jQuery('#modal-complete .btn-complete').attr('data-order-id', orderId);
            jQuery('#modal-complete input[name=bill-number]').val('');
            jQuery('#modal-complete input[name=check-number]').val('');

            // old html
            thisElement.html(thisOldHtml);
            jQuery('#modal-complete').modal(
                {
                    'show' : true,
                    'backdrop' : 'static'
                }
            );
        } else if (action == 'vendorTransport') {
            if (extraData['partner'] == 'grab_express') {
                if (paymentMethod == 'COD' && orderPrice && Number(orderPrice) >= 2000000) {
                    thisElement.html(thisOldHtml);
                    return alert('Chỉ được yêu cầu Grab vận chuyển với đơn COD nhỏ hơn 2 triệu');
                } 
                // old html
                thisElement.html(thisOldHtml);
                var cf = confirm("Nhà hàng đã chắc chắn gói hàng đúng quy định?\nKích thước tối đa: 50x50x50cm\nTrọng lượng tối đa: 15kg");
                if (cf) {
                    jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                    <?php if (get_option('google_map_service_address') == 'goong_address'): ?>
                    openModalSelectAddress(orderId);
                    <?php else: ?>
                    updateOrderStatus(orderId, status, '', 0, extraData);
                    <?php endif; ?>
                }
            } else {
                alert('Chưa hỗ trợ vẫn chuyển ['+ extraData['partner'] +']');
            }
        } else if (action == 'vendorScheduleTransport') {
            var cf = confirm('Xác nhận hẹn giao ['+ extraData['partner'] +'] ?');
            if (cf) {
                updateOrderStatus(orderId, status, '', 0, extraData);
            } else {
                thisElement.html(thisOldHtml);
            }
        } else if (action == 'cancelVendorTransport') {
            var cf = confirm('Thực hiện hủy ['+ extraData['partner'] +'] ?');
            if (cf) {
                updateOrderStatus(orderId, status, '', 0, extraData);
            } else {
                thisElement.html(thisOldHtml);
            }
        } else if (action == 'refund') {
            var cf = confirm('Thực hiện hoàn tiền?');

            if (cf) {
                refund(orderId);
            } else {
                thisElement.html(thisOldHtml);
            }
        } else {
            // old html
            thisElement.html(thisOldHtml);
            var cf = confirm(statusText + '?');
            if (cf) {
                jQuery(this).attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                updateOrderStatus(orderId, status, '', 0, extraData);
            }
        }

        return false;
    }); // end change status

    removeSpinnerAfterChangeStatus('modal-need-to-transfer');
    removeSpinnerAfterChangeStatus('modal-need-to-cancel');
    removeSpinnerAfterChangeStatus('modal-change-restaurant');
    removeSpinnerAfterChangeStatus('modal-cancel');
    function removeSpinnerAfterChangeStatus(modalId) {
        jQuery('#' + modalId).on('hide.bs.modal', function () {
            jQuery('.change-status .fa-spinner').remove();
        });
    }

    function requestCallback() {
        if (typeof ordersTable !== 'undefined') {
            $('.modal').modal('hide');
            ordersTable.ajax.reload(null, false);
            if (typeof ajaxGetOrderSummary === 'function') {
                ajaxGetOrderSummary();
            }
        } else {
            window.location.reload();
        }
    }

    //  change restaurant
    jQuery('#modal-change-restaurant .btn-change-restaurant').click(function () {
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();


        var fixedNote = jQuery('#modal-change-restaurant select[name=change-reason]').val();
        var otherNote = jQuery('#modal-change-restaurant textarea[name=note]').val();
        if (fixedNote == 'Khác' && otherNote == '') {
            alert('Cần nhập rõ lý do.');
        } else {
            // loading button
            thisElement.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
            jQuery('#modal-change-restaurant .modal-header button').hide();

            var note =  fixedNote;
            if (otherNote != '') {
                note += '__' + otherNote;
            }
            var destinationRestaurantId = jQuery('#modal-change-restaurant select[name=destination-restaurant]').val();
            var status = 'pending';
            var orderId = jQuery('#modal-change-restaurant .btn-change-restaurant').attr('data-order-id');

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId,
                    'note' : note,
                    'status' : status,
                    'restaurant' : destinationRestaurantId
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đơn hàng đã được chuyển.');
                        requestCallback();
                    } else {
                        alert(res.message);
                        thisElement.removeAttr('disabled').html(oldHtml);
                        jQuery('#modal-change-restaurant .modal-header button').show();
                        if (res.messageCode == 2) {
                            requestCallback();
                        }
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                    thisElement.removeAttr('disabled').html(oldHtml);
                    jQuery('#modal-change-restaurant .modal-header button').show();
                }
            }); // end ajax
        }


        return false;
    }); // change restaurant

    //  cancel order
    jQuery('#modal-cancel .btn-cancel-order').click(function () {
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();

        var fixedNote = jQuery('#modal-cancel select[name=cancel-reason]').val();
        var otherNote = jQuery('#modal-cancel textarea[name=note]').val();
        if (fixedNote == 'Khác' && otherNote == '') {
            alert('Cần nhập rõ lý do.');
        } else {
            // loading button
            thisElement.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

            var note = fixedNote;
            if (otherNote != '') {
                note += '__' + otherNote;
            }
            var status = 'cancelled';
            var orderId = jQuery('#modal-cancel .btn-cancel-order').attr('data-order-id');

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId,
                    'note' : note,
                    'status' : status
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đơn hàng đã được hủy.');
                        requestCallback();
                    } else {
                        alert(res.message);
                        if (res.messageCode == 2) {
                            requestCallback();
                        }
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                },
                'complete' : function () {
                    thisElement.removeAttr('disabled').html(oldHtml);
                }
            }); // end ajax
        }

        return false;
    }); // change restaurant

    //  need to cancel
    jQuery('#modal-need-to-cancel .btn-need-to-cancel').click(function () {
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();

        var fixedNote = jQuery('#modal-need-to-cancel select[name=cancel-reason]').val();
        var otherNote = jQuery('#modal-need-to-cancel textarea[name=note]').val();
        if (fixedNote == 'Khác' && otherNote == '') {
            alert('Cần nhập rõ lý do.');
        } else {
            // loading button
            thisElement.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

            var note = fixedNote;
            if (otherNote != '') {
                note += '__' + otherNote;
            }
            var status = 'need-to-cancel';
            var orderId = jQuery('#modal-need-to-cancel .btn-need-to-cancel').attr('data-order-id');

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId,
                    'note' : note,
                    'status' : status
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đã gửi yêu cầu hủy tới vận đơn.');
                        requestCallback();
                    } else {
                        alert(res.message);
                        if (res.messageCode == 2) {
                            requestCallback();
                        }
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                },
                'complete' : function () {
                    thisElement.removeAttr('disabled').html(oldHtml);
                }
            }); // end ajax
        }

        return false;
    }); // change restaurant

    //  need to transfer
    jQuery('#modal-need-to-transfer .btn-need-to-transfer').click(function () {
        var fixedNote = jQuery('#modal-need-to-transfer select[name=cancel-reason]').val();
        var otherNote = jQuery('#modal-need-to-transfer textarea[name=note]').val();
        if (fixedNote == 'Khác' && otherNote == '') {
            alert('Cần nhập rõ lý do.');
        } else {
            var note = fixedNote;
            if (otherNote != '') {
                note += '__' + otherNote;
            }
            var status = 'need-to-transfer';
            var orderId = jQuery('#modal-need-to-transfer .btn-need-to-transfer').attr('data-order-id');

            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId,
                    'note' : note,
                    'status' : status
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đã gửi yêu cầu chuyển nhà hàng tới vận đơn.');
                        requestCallback();
                    } else {
                        alert(res.message);
                        if (res.messageCode == 2) {
                            requestCallback();
                        }
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                }
            }); // end ajax
        }

        return false;
    }); // change restaurant

    //  complete
    jQuery('#modal-complete .btn-complete').click(function () {
        var billNumber = jQuery('#modal-complete input[name=bill-number]').val();
        var checkNumber = jQuery('#modal-complete input[name=check-number]').val();
        if (billNumber == '' && checkNumber == '') {
            alert('Cần nhập đầy đủ số bill và số check.');
        } else {
            let thisButton = jQuery(this);
            thisButton.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
            var orderId = jQuery('#modal-complete .btn-complete').attr('data-order-id');
            var status = 'completed';
            
            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-update-order-status')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId,
                    'status' : status,
                    'rkOrder' : {
                        'billNumber' : billNumber,
                        'checkNumber' : checkNumber
                    }
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert('Đơn hàng đã hoàn tất.');
                        requestCallback();
                    } else {
                        alert(res.message);
                        if (res.messageCode == 2) {
                            requestCallback();
                        }
                    }
                },
                'complete': function (x, y){
                    thisButton.removeAttr('disabled');
                    thisButton.find('span').remove();
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                }
            }); // end ajax
        }

        return false;
    }); // change restaurant

    // print
    jQuery('.open-printer').click(function () {
        window.print();

        return false;
    });

    jQuery('#btn-complete-order').click(function () {
        var orderId = jQuery(this).attr('data-order-id');
        // order id
        jQuery('#modal-complete .btn-complete').attr('data-order-id', orderId);

        jQuery('#modal-complete').modal(
            {
                'show': true,
                'backdrop': 'static'
            }
        );
    });

    jQuery('#btn-create-rk-order').click(function () {
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();
        var orderId = jQuery(this).attr('data-order-id');

        var r = confirm('Tạo Order trên POS???');

        if (r) {
            jQuery.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-create-rk-order')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : orderId
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        alert(res.message);
                        requestCallback();
                    } else {
                        alert(res.message);
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                },
                'complete': function (x, y){
                    thisElement.html(oldHtml);
                },
                'beforeSend': function () {
                    thisElement.append(' <span style="color: #fff;" class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');
                }
            }); // end ajax
        }
    });

    jQuery('#bill-number').keypress(function (e){
        var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
    });

    jQuery('#check-number').keypress(function (e){
        var charCode = (e.which) ? e.which : e.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
    });

    // update info order
    jQuery('.update-order').click(function () {
        var thisElement = jQuery(this);
        var orderId = thisElement.attr('data-order-id');
        var thisOldHtml = thisElement.html();

        // loading
        thisElement.html('<span class="fa fa-1x fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        jQuery.ajax({
            'type' : 'get',
            'url' : '<?=site_url('/ajax-order-detail')?>',
            'dataType' : 'json',
            'data' : {
                'orderId' : orderId
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    var order = res.result.order;
                    jQuery('#modal-update-order select[name=payment_method]').val(order.payment_method);
                    if (order.payment_method == 'COD') {
                        jQuery('#modal-update-order select[name=is_paid]').val(1);
                    } else {
                        jQuery('#modal-update-order select[name=is_paid]').val(order.is_paid);
                    }
                    jQuery('#modal-update-order input[name=payment_partner_transaction_id]').val(order.payment_partner_transaction_id);
                    jQuery('#modal-update-order input[name=order_id]').val(orderId);
                    jQuery('#modal-update-order select[name=vendor_transport]').val(order.vendor_transport);
                    jQuery('#modal-update-order select[name=status]').val(order.status);
                    //
                    // // add order id
                    // jQuery('#modal-update-order input[name = "order_id"]').val(orderId);

                    // old html
                    thisElement.html(thisOldHtml);

                    // show modal
                    jQuery('#modal-update-order').modal({
                        'show' : true,
                        'backdrop' : 'static'
                    });
                } else {
                    alert(res.message);
                    // old html
                    thisElement.html(thisOldHtml);
                }
            },
            'error' : function (x, y, z) {
                // old html
                thisElement.html(thisOldHtml);
            }
        }); // end ajax

        return false;
    }); // end change status

    // confirm update order
    jQuery('#modal-update-order .btn-update-order').click(function () {
        var thisElement = jQuery(this);
        var oldHtml = thisElement.html();

        var payment_method = jQuery('#modal-update-order select[name=payment_method]').val();
        var is_paid = jQuery('#modal-update-order select[name=is_paid]').val();
        var payment_partner_transaction_id = jQuery('#modal-update-order input[name=payment_partner_transaction_id]').val();
        var orderId = jQuery('#modal-update-order input[name=order_id]').val();
        var vendor_transport = jQuery('#modal-update-order select[name=vendor_transport]').val();
        var status = jQuery('#modal-update-order select[name=status]').val();

	    if (payment_method !== 'COD'
		    && payment_method !== 'BizAccount'
		    && is_paid == 1
		    && payment_partner_transaction_id == '') {
            alert("Vui lòng nhập mã giao dịch đối tác");
            return false;
	    }

	    // loading button
	    thisElement.attr('disabled', 'disabled').append('<span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>');

        jQuery.ajax({
            'type' : 'get',
            'url' : '<?=site_url('/ajax-update-order-info')?>',
            'dataType' : 'json',
            'data' : {
                'orderId' : orderId,
                'is_paid' : is_paid,
                'payment_method' : payment_method,
                'payment_partner_transaction_id' : payment_partner_transaction_id,
                'vendor_transport' : vendor_transport,
                'status' : status,
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    alert('Đơn hàng đã được cập nhật thành công.');
                    requestCallback();
                    jQuery('#modal-update-order .modal-header button').hide();
                } else {
                    alert(res.message);
                    thisElement.removeAttr('disabled').html(oldHtml);
                }
            },
            'error' : function (x, y, z) {
                alert('Lỗi khi gọi ajax');
                thisElement.removeAttr('disabled').html(oldHtml);
            }
        }); // end ajax

        return false;
    }); // change restaurant

    $('#modal-update-order select[name=payment_method]').change(function() {
        var payment_method = $(this).val();
        // if (payment_method == 'COD') {
        //     jQuery('#modal-update-order select[name=is_paid]').val(1).prop('disabled', true);
        // } else {
        //     jQuery('#modal-update-order select[name=is_paid]').val(1).prop('disabled', false);
        // }
    });

    // Refund
    function refund(orderId) {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=admin_url('admin-ajax.php')?>',
            'dataType' : 'json',
            'data' : {
                'id' : orderId,
                'beHonest' : '<?=wp_create_nonce('order_refund')?>',
                'action': 'order_refund'
            },
            'success' : function (res) {
                alert(res.message);
                location.reload();
            },
            'error' : function (x, y, z) {
                // old html

            }
        }); // end ajax
    }
</script>