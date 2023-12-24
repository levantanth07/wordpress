<?php
 ob_start(); 
session_start();

add_theme_support( 'woocommerce' );

load_theme_textdomain('g-delivery', get_template_directory() . '/languages');

///////////////////////////////////////////////////////////
/// REPORT MENU
//////////////////////////////////////////////////////////
// register report menu
function registerManageOperationMenu() {

    add_menu_page(
        'Quản lý Vận hành',
        'Quản lý vận hành',
        'edit_posts',
        'manage-operation',
        '',
        'dashicons-media-spreadsheet',
        4
    );

    add_submenu_page(
        'manage-operation',
        'Báo cáo bán hàng',
        'Báo cáo bán hàng',
        'edit_posts',
    site_url('restaurant-order-report/'),
        ''
    );

    add_submenu_page(
        'manage-operation',
        'Danh sách đơn hàng cho Vận Đơn',
        'Danh sách đơn hàng cho Vận Đơn',
        'edit_posts',
        site_url('operator-list-orders/'),
        ''
    );

    add_submenu_page(
        'manage-operation',
        'Danh sách đơn hàng cho Nhà hàng',
        'Danh sách đơn hàng cho Nhà hàng',
        'edit_posts',
        site_url('restaurant-list-orders-v2/'),
        ''
    );

    add_submenu_page(
        'manage-operation',
        'On/Off product',
        'On/Off product',
        'edit_posts',
        site_url('setting-product/'),
        ''
    );

    if (current_user_can('show_feedback_list')) {
        add_submenu_page(
            'manage-operation',
            'Feedback',
            'Feedback',
            'edit_posts',
            site_url('feedback-list/'),
            ''
        );
    }
}
add_action('admin_menu', 'registerManageOperationMenu');

function registerReportJavascriptCss(){
    // Register file css
    wp_enqueue_style('bootstrap', get_template_directory_uri() . "/assets/restaurant/css/bootstrap.css");
    wp_enqueue_style('bootstrapGrid', get_template_directory_uri() . "/assets/restaurant/css/bootstrap-grid.css");
    wp_enqueue_style('bootstrapReboot', get_template_directory_uri() . "/assets/restaurant/css/bootstrap-reboot.css");
    wp_enqueue_style('styles', get_template_directory_uri() . "/assets/restaurant/css/styles.css");
    wp_enqueue_style('bootstrapDatepicker', get_template_directory_uri() . "/assets/css/bootstrap-datepicker.min.css");
    // wp_enqueue_style('dataTableCss', get_template_directory_uri() . "/assets/css/jquery.dataTables.min.css");


    // Register file javascript
    wp_enqueue_script('bootstrapDate', get_template_directory_uri() . "/assets/js/bootstrap-datepicker.min.js");
    wp_enqueue_script('bootstrapDateVi', get_template_directory_uri() . "/assets/js/bootstrap-datepicker.vi.min.js");
    // wp_enqueue_script('dataTableJs', get_template_directory_uri() . "/assets/js/jquery.dataTables.min.js");
}

add_action('admin_enqueue_scripts', 'registerReportJavascriptCss');


function pageReports() {
    echo 'Vui lòng chọn báo cáo ở menu Report';
}
///////////////////////////////////////////////////////////
/// END REPORT MENU
//////////////////////////////////////////////////////////

add_action( 'wp_ajax_restaurant_order_list', 'restaurantOrderListHandler' );

function restaurantOrderListHandler() {
    $defaultResponse = [
        "draw" => 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ];
    
    $currentUser = wp_get_current_user();
    if (!is_user_logged_in()) {
        wp_send_json($defaultResponse);
    }
    $user = Permission::checkCurrentUserRole($currentUser);
    if ($user->role != 'restaurant' && $user->role != 'administrator') {
        wp_send_json($defaultResponse);
    }
    
    $restaurantOrderList = new \GDelivery\Libs\Helper\RestaurantOrderList();
    list($orders, $totalItems, $otherData) = $restaurantOrderList->getRestaurantOrderList($_POST);

    $response = [
        "draw" => intval($_POST['draw']),
        "recordsTotal" => $totalItems,
        "recordsFiltered" => $totalItems,
        "currentTab" => $otherData['currentTab'],
        "data" => $orders
    ];

    wp_send_json($response);
}

add_action( 'wp_ajax_restaurant_order_summary', 'restaurantOrderSummaryHandler' );

function restaurantOrderSummaryHandler() {
    $currentUser = wp_get_current_user();
    if (!is_user_logged_in()) {
        wp_send_json([]);
    }
    $user = Permission::checkCurrentUserRole($currentUser);
    if ($user->role != 'restaurant' && $user->role != 'administrator') {
        wp_send_json([]);
    }

    $restaurantOrderList = new \GDelivery\Libs\Helper\RestaurantOrderList();
    list($totalOrders, $totalRevenue, $listTabTotalOrders) = $restaurantOrderList->getOrderSummaryInfo($_POST);

    $response = [
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'listTabTotalOrders' => $listTabTotalOrders,
    ];
    wp_send_json($response);
}

