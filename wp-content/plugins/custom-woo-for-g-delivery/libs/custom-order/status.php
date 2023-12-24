<?php
/*
 * list status:
 * pending - core - tạo đơn cod
 * need-to-transfer - custom - yêu cầu chuyển đơn
 * processing - core - nhà xác nhận hoặc đã thanh toán
 * transport-requested - custom - yêu cầu vận chuyển: grab/now....
 * transport-rejected - custom - yêu cầu vận chuyển bị từ chối: grab/now....
 * transport-allocating - custom - đang tìm tài xế: grab/now....
 * transport-accepted - custom - vendor nhận đơn vận chuyển
 * transport-going - custom - vendor đang giao hàng
 * transport-delivered - custom - đã giao hàng
 * transport-returned - custom - vendor trả lại hàng
 * completed - core - hoàn thành
 *
 */
// Firstly: register new status
add_action('init', 'registerSomeOrderStatus');
function registerSomeOrderStatus()
{
    // need to transfer
    register_post_status(
        'wc-need-to-transfer',
        [
            'label' => 'Yêu cầu chuyển đơn',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Yêu cầu chuyển đơn <span class=”count”>(%s)</span>', 'Yêu cầu chuyển đơn <spanclass=”count”>(%s)</span>')
        ]
    );

    // need to cancel
    register_post_status(
        'wc-need-to-cancel',
        [
            'label' => 'Yêu cầu hủy',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Yêu cầu hủy <span class=”count”>(%s)</span>', 'Yêu cầu hủy <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport requested
    register_post_status(
        'wc-trans-requested',
        [
            'label' => 'Yêu cầu giao hàng',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Yêu cầu giao hàng <span class=”count”>(%s)</span>', 'Yêu cầu giao hàng <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport accepted
    register_post_status(
        'wc-trans-accepted',
        [
            'label' => 'Nhận yêu cầu giao hàng',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Nhận yêu cầu giao hàng <span class=”count”>(%s)</span>', 'Nhận yêu cầu giao hàng <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport rejected
    register_post_status(
        'wc-trans-rejected',
        [
            'label' => 'Yêu cầu giao hàng bị từ chối',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Yêu cầu giao hàng bị từ chối <span class=”count”>(%s)</span>', 'Yêu cầu giao hàng bị từ chối <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport rejected, transport not found
    register_post_status(
        'wc-trans-allocating',
        [
            'label' => 'Đang tìm tài xế',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Đang tìm tài xế <span class=”count”>(%s)</span>', 'Đang tìm tài xế <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport on going
    register_post_status(
        'wc-trans-going',
        [
            'label' => 'Đang giao hàng',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Giao hàng thành công <span class=”count”>(%s)</span>', 'Giao hàng thành công <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport delivered
    register_post_status(
        'wc-trans-delivered',
        [
            'label' => 'Giao hàng thành công',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Giao hàng thành công <span class=”count”>(%s)</span>', 'Giao hàng thành công <spanclass=”count”>(%s)</span>')
        ]
    );

    // transport returned
    register_post_status(
        'wc-trans-returned',
        [
            'label' => 'Bị trả hàng',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Bị trả hàng <span class=”count”>(%s)</span>', 'Bị trả hàng <spanclass=”count”>(%s)</span>')
        ]
    );

    // wating for payment
    register_post_status(
        'wc-waiting-payment',
        [
            'label' => 'Chờ thanh toán',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Chờ thanh toán <span class=”count”>(%s)</span>', 'Chờ thanh toán <spanclass=”count”>(%s)</span>')
        ]
    );

    // request support
    register_post_status(
        'wc-request-support',
        [
            'label' => 'Yêu cầu hỗ trợ',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Yêu cầu hỗ trợ <span class=”count”>(%s)</span>', 'Yêu cầu hỗ trợ <spanclass=”count”>(%s)</span>')
        ]
    );

    // customer rejected
    register_post_status(
        'wc-customer-reject',
        [
            'label' => 'Khách hàng không nhận đơn',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Khách hàng không nhận đơn <span class=”count”>(%s)</span>', 'Khách hàng không nhận đơn <spanclass=”count”>(%s)</span>')
        ]
    );

    // ready to delivery
    register_post_status(
        'wc-ready-to-pickup',
        [
            'label' => 'Đã sẳn sàng để nhận',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Đã sẳn sàng để nhận <span class=”count”>(%s)</span>', 'Đã sẳn sàng để nhận <spanclass=”count”>(%s)</span>')
        ]
    );

    // confirmed
    register_post_status(
        'wc-confirmed',
        [
            'label' => 'Đã xác nhận',
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop('Đã xác nhận <span class=”count”>(%s)</span>', 'Đã xác nhận <spanclass=”count”>(%s)</span>')
        ]
    );
}

// Secondly: add statuses to order
add_filter('wc_order_statuses', 'addAwaitingNewStatusToOrderStatuses');
function addAwaitingNewStatusToOrderStatuses($order_statuses) {
    $new_order_statuses = [];

    $new_order_statuses['wc-need-to-transfer'] = 'Yêu cầu chuyển đơn';
    $new_order_statuses['wc-need-to-cancel'] = 'Yêu cầu hủy';
    $new_order_statuses['wc-trans-requested'] = 'Yêu cầu giao hàng';
    $new_order_statuses['wc-trans-rejected'] = 'Yêu cầu giao hàng bị từ chối';
    $new_order_statuses['wc-trans-allocating'] = 'Đang tìm tài xế';
    $new_order_statuses['wc-trans-accepted'] = 'Nhận yêu cầu giao hàng';
    $new_order_statuses['wc-ready-to-pickup'] = 'Đã sẳn sàng để nhận';
    $new_order_statuses['wc-trans-going'] = 'Đang giao hàng';
    $new_order_statuses['wc-trans-delivered'] = 'Đã giao hàng';
    $new_order_statuses['wc-trans-returned'] = 'Bị trả hàng';
    $new_order_statuses['wc-waiting-payment'] = 'Chờ thanh toán';
    $new_order_statuses['wc-request-support'] = 'Yêu cầu hỗ trợ';
    $new_order_statuses['wc-customer-reject'] = 'Khách hàng không nhận đơn';
    $new_order_statuses['wc-confirmed'] = 'Đã xác nhận';
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        // todo arrange order status
        /*if ('wc-completed' === $key) {
            $new_order_statuses['wc-arrival-shipment'] = 'Đã giao hàng';
        }*/
    }
    return $new_order_statuses;
}

// change default order status on COD (in generally, without payment)
add_action( 'woocommerce_thankyou', 'woocommerce_thankyou_change_order_status', 10, 1 );
function woocommerce_thankyou_change_order_status( $order_id ) {
    if( ! $order_id ) return;

    $order = wc_get_order( $order_id );

    if( $order->get_status() == 'processing' )
        $order->update_status( 'pending' );
}

// allow some status can cancel order
add_filter( 'woocommerce_valid_order_statuses_for_cancel', 'filter_valid_order_statuses_for_cancel', 20, 2 );
function filter_valid_order_statuses_for_cancel( $statuses, $order = '' ) {
    return array( 'pending');
}

use GDelivery\Libs\Helper\Order;
use GDelivery\Libs\Helper\User;
add_action('woocommerce_order_status_changed', 'orderStatusChanged', 10, 3);
function orderStatusChanged($orderId, $oldStatus, $newStatus)
{
    $order = wc_get_order($orderId);
    $currentUser = wp_get_current_user();
    $user = Permission::checkCurrentUserRole($currentUser);
    $currentTime = date_i18n('Y-m-d H:i');

    switch ($newStatus) {
        case Order::STATUS_PENDING:
            if (empty($order->get_meta('order_success_time'))) {
                $order->update_meta_data('order_success_time', $currentTime);
            }
            break;
        case Order::STATUS_READY_TO_PICKUP:
            $order->update_meta_data('restaurant_complete_time', $currentTime);
            break;
        case Order::STATUS_TRANS_ACCEPTED:
            $order->update_meta_data('shipper_accept_time', $currentTime);
            break;
        case Order::STATUS_TRANS_GOING:
            $order->update_meta_data('trans_going_time', $currentTime);
            break;
        case Order::STATUS_TRANS_DELIVERED:
            $order->update_meta_data('trans_complete_time', $currentTime);
            break;
        default:
            //
    }

    switch ($oldStatus) {
        case Order::STATUS_REQUEST_SUPPORT:
            if ($user->role == User::USER_ROLE_CS) {
                $order->update_meta_data('cs_processing_last_time', $currentTime);
            }
            break;
        case Order::STATUS_PENDING:
            if ($user->role == User::USER_ROLE_RESTAURANT) {
                if (empty($order->get_meta('restaurant_processing_first_time'))) {
                    $order->update_meta_data('restaurant_processing_first_time', $currentTime);
                }
                $order->update_meta_data('restaurant_processing_last_time', $currentTime);
            }
            break;
        default:
            //
    }

    $order->save();
}

add_action('woocommerce_new_order', 'orderCreated', 10, 3);
function orderCreated($orderId)
{
    $order = wc_get_order($orderId);
    if ($order->get_status() == Order::STATUS_PENDING
        && empty($order->get_meta('order_success_time'))) {
        $order->update_meta_data('order_success_time', date_i18n('Y-m-d H:i:s'));
        $order->save();
    }
}