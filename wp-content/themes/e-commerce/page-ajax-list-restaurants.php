<?php
/*
Template Name: Ajax List Restaurants
*/

$res = new \Abstraction\Object\Result();

$categoryId = $_REQUEST['category'];

$args = [
    'post_type' => 'chinhanh',
    'showposts' => 999,
];

if ($categoryId) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'product_cat',
            'field' => 'id',
            'terms' => $categoryId,
        ],
    ];
}

$bookingService = new \GDelivery\Libs\BookingService();
$arrRestaurants = [];
$loop = new WP_Query($args);
while ($loop->have_posts()) :
    $loop->the_post();

    $restaurantCode = get_field('restaurant_code');
    $getRestaurant = $bookingService->getRestaurant($restaurantCode);
    if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
        $arrRestaurants[] = $getRestaurant->result;
    }
endwhile;
wp_reset_query();

if ($arrRestaurants) {
    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
    $res->message = 'Thành công';
    $res->result = $arrRestaurants;
} else {
    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
    $res->message = 'Không có thông tin nhà hàng';
}

header('Content-Type: application/json');
echo \json_encode($res);