<?php
/*
Template Name: Export complain
*/

use Box\Spout\Common\Entity\Style\Border;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}
$user = Permission::checkCurrentUserRole($currentUser);
if (
    $user->role != 'operator'
    && $user->role != 'administrator'
    && $user->role != 'am'
) {
    header('Location: '.site_url('wp-login.php'));
    //wp_die('Bạn không được phép truy cập trang này');
}

//$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
$perPage = isset($_GET['perPage']) ? (int) $_GET['perPage'] : 10;

$fromDate = $_GET['fromDate'] ?? date_i18n('Y-m-d');
$toDate = $_GET['toDate'] ?? date_i18n('Y-m-d');

$search = $_GET['search'] ?? '';

$time = current_time('mysql');

$args = [
    'post_type' => 'complain',
    'post_status' => 'publish',
    'posts_per_page' => -1
];

if ($fromDate && $toDate) {
    $args['date_query'] = [
        'column' => 'post_date',
        'after'     => $fromDate,
        'before'    => $toDate,
        'inclusive' => true,
    ];
}
$metaQuery = [];
if ($search) {
    $metaQuery[] = [
        'key'     => 'customerPhone',
        'value'   => $search,
        'compare' => '='
    ];
}
$args['meta_query'] = $metaQuery;

$query = new \WP_Query($args);

$complains = [];
foreach ($query->posts as $post) {
    $complains[] = (new GDelivery\Libs\Helper\Complain)->getComplainInfo(
        $post,
        [
            'with' => 'order, restaurant'
        ]
    );
}
//print_r($complains); die();
$fileName = "complain_export.xlsx";
$writer = WriterEntityFactory::createXLSXWriter();
$writer->openToBrowser($fileName);

$border = (new BorderBuilder())
    ->setBorderBottom(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
    ->setBorderTop(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
    ->setBorderLeft(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
    ->setBorderRight(Color::BLACK, Border::WIDTH_THIN, Border::STYLE_SOLID)
    ->build();

$headerStyle = (new StyleBuilder())
    ->setFontBold()
    ->setBorder($border)
    ->setFontSize(12)
    ->setBackgroundColor(Color::YELLOW)
    ->build();

$style = (new StyleBuilder())
    ->setShouldWrapText(false)
    ->setFontSize(11)
    ->build();

$header = [
    WriterEntityFactory::createCell('Thời gian tiếp nhận', $headerStyle),
    WriterEntityFactory::createCell('Thời gian sử dụng dịch vụ', $headerStyle),
    WriterEntityFactory::createCell('Điểm đánh giá', $headerStyle),
    WriterEntityFactory::createCell('Tình trạng xử lý', $headerStyle),
    WriterEntityFactory::createCell('Nguồn', $headerStyle),
    WriterEntityFactory::createCell('Vùng', $headerStyle),
    WriterEntityFactory::createCell('Khu vực', $headerStyle),
    WriterEntityFactory::createCell('Chuỗi', $headerStyle),
    WriterEntityFactory::createCell('Brand', $headerStyle),
    WriterEntityFactory::createCell('Tên nhà hàng', $headerStyle),
    WriterEntityFactory::createCell('Tên khách hàng', $headerStyle),
    WriterEntityFactory::createCell('Thông tin liên hệ', $headerStyle),
    WriterEntityFactory::createCell('Phân loại phản ánh', $headerStyle),
    WriterEntityFactory::createCell('Vấn đề', $headerStyle),
    WriterEntityFactory::createCell('Chi tiết vấn đề', $headerStyle),
    WriterEntityFactory::createCell('Thông tin chi tiết', $headerStyle),
    WriterEntityFactory::createCell('Người xử lý', $headerStyle),
    WriterEntityFactory::createCell('Ngày xử lý', $headerStyle),
    WriterEntityFactory::createCell('Kết quả xử lý', $headerStyle),
    WriterEntityFactory::createCell('Note', $headerStyle),
    WriterEntityFactory::createCell('Mã nhà hàng', $headerStyle),
    WriterEntityFactory::createCell('Mã brand', $headerStyle),
    WriterEntityFactory::createCell('RegionID', $headerStyle),
    WriterEntityFactory::createCell('Phân loại lỗi', $headerStyle),
    WriterEntityFactory::createCell('Mã đơn', $headerStyle),
];
$singleHeader = WriterEntityFactory::createRow($header);
$writer->addRow($singleHeader);

foreach ($complains as $complain) {

    $adminComment = [];
    foreach ($complain->comments as $comment) {
        if ($comment->author == 'admin') {
            $adminComment = $comment;
            break;
        }
    }

    $zone = '';
    if (isset($complain->merchant->restaurant->sapSegmentCode)) {
        if ($complain->merchant->restaurant->sapSegmentCode == 1000) {
            $zone = 'Miền Bắc';
        } elseif ($complain->merchant->restaurant->sapSegmentCode == 3000) {
            $zone = 'Miền Nam';
        }
    }

    $values = [
        $complain->createdAt,
        $complain->order->createdAt,
        isset($complain->order->rating) ? $complain->order->rating->points : '',
        $complain->statusText,
        $complain->source,
        isset($complain->merchant) ? $complain->merchant->restaurant->regionName : '',
        $zone,
        isset($complain->merchant) ? $complain->merchant->restaurant->conceptName : '',
        isset($complain->merchant) ? $complain->merchant->restaurant->brand->name : '',
        isset($complain->merchant) ? $complain->merchant->restaurant->name : '',
        $complain->customerName,
        $complain->customerPhone,
        $complain->classify->issueType,
        $complain->classify->issue,
        $complain->classify->issueDetail,
        $complain->classify->comment,
        $adminComment ? $adminComment->authorEmail : '',
        $adminComment ? $adminComment->date : '',
        $complain->response->comment,
        $complain->note,
        isset($complain->merchant) ? $complain->merchant->restaurantCode : '',
        '',
        '',
        $complain->classify->level,
        $complain->orderId
    ];
    $rowFromValues = WriterEntityFactory::createRowFromArray($values, $style);
    $writer->addRow($rowFromValues);
}

$writer->close();
exit;

//print_r($complains); die();
?>
