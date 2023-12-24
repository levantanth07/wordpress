<?php
/*
Template Name: Update Bank Online
*/
// process login
if (!isset($_SESSION['isLogin']) || !$_SESSION['isLogin']) {
    header('Location: '.site_url('checkout-login'));
}

// get order detail
$order = wc_get_order( isset($_GET['orderId']) ? $_GET['orderId'] : 0);

if ($order) :

// customer only can view their order
if ($order->get_customer_id() != get_current_user_id()) {
    wp_die('Be honest, play fair :)');
}

$queryData = [];
parse_str($_SERVER['QUERY_STRING'], $queryData);

$arrayCheckHash = $queryData;

// check hash data
$hash = $queryData['hash'];
unset($arrayCheckHash['orderId']);
unset($arrayCheckHash['hash']);

ksort($arrayCheckHash);

$checkHash = md5(
    $arrayCheckHash['requestId'].
        md5(\GDelivery\Libs\Config::PAYMENT_HUB_BACHMAI_SENDER).
        http_build_query($arrayCheckHash)
);

if ($hash == $checkHash) {
    // update order request payment id
    $order->update_meta_data('payment_request_id', $queryData['requestId']);
    $order->save();

    if ($queryData['messageCode'] == 1) {
        if ($order->get_status() == 'waiting-payment') {
            $order->set_status('pending');
            $order->add_order_note('Đã nhận thanh toán');
            $order->set_date_paid(time());
            $order->update_meta_data('is_paid', 1);
            $order->update_meta_data('payment_partner_transaction_id', $queryData['partnerTransactionId']);
            $order->update_meta_data('total_pay_sum', $queryData['amount']);
            $order->save();

            // save to report
            $report = new \GDelivery\Libs\Helper\Report();
            $report->updateOrder($order);

            // calling new order
            GDelivery\Libs\Helper\Call::makeAcall($order);

            // send mail new order
            GDelivery\Libs\Helper\Mail::send($order);

        } else {
            header('Location: '.site_url('order-detail?id='.$order->get_id()));
        }
    }

    // not pay yet
    get_header('order');
    ?>

    <!-- content list -->
    <div class="wrap-list">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    Đang cập nhật giao dịch <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>
    <!-- end list -->

    <!-- Modal order success -->
    <div class="modal-msg modal fade" id="modal-order-success" tabindex="-1" aria-labelledby="modal-order-success" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-success">
                        <div class="wrap-icon">
                            <img src="<?=bloginfo('template_url')?>/assets/images/icon-success.svg" />
                        </div>
                        <h4>Đặt hàng thành công</h4>
                        <p>Cảm ơn bạn đã đặt hàng trên G-Delivery</p>
                        <a id="link-to-order-detail" href="<?=site_url('order-detail?id='.$order->get_id())?>" title="Thành công"><button>Theo dõi đơn hàng</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- modal order fail -->
    <div class="modal-msg modal fade" id="modal-order-fail" tabindex="-1" aria-labelledby="modal-order-fail" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="modal-success">
                        <div class="wrap-icon">
                            <img src="<?=bloginfo('template_url')?>/assets/images/icon-fail.svg" />
                        </div>
                        <h4>Không thành công</h4>
                        <p>Vui lòng kiểm tra thông tin đơn hàng và thử lại</p>
                        <a id="link-to-order-detail-2" href="<?=site_url('order-detail?id='.$order->get_id())?>" title="Thành công"><button>Đồng ý</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($queryData['messageCode'] == 1) :?>
        <script type="text/javascript">
            var orderId = <?=$queryData['orderId']?>;
            var orderDetailUrl = "<?=site_url('order-detail')?>?id=" + orderId;

            jQuery('#modal-order-success #link-to-order-detail').attr('href', orderDetailUrl);
            jQuery('#modal-order-success').modal(
                {
                    'show' : true,
                    'backdrop' : 'static'
                }
            );
        </script>
    <?php elseif($queryData['messageCode'] == 30) :?>
    <script type="text/javascript">
        var orderId = <?=$queryData['orderId']?>;
        var requestId = <?=$queryData['requestId']?>;
        intervalCheckPayment = setInterval(
            function () {
                checkPayment(
                    requestId,
                    orderId,
                    'bank_online'
                );
            },
            5000
        );
    </script>
    <?php else: ?><script type="text/javascript">
        jQuery('#modal-order-fail').modal(
            {
                'show' : true,
                'backdrop' : 'static'
            }
        );
    </script>

    <?php endif; ?>


<?php
    get_template_part('content/content', 'js-payment');
    get_footer();
} else {
    wp_die('Fail to checksum. Be honest, play fair :)');
}
else:
    wp_die('Order không tồn tại');
endif; // end if order
