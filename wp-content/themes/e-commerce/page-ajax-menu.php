<?php 
/*
Template Name =>  AJAX Menu
*/
header('Content-Type: application/json');
$ID = $_GET['id'];
foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
	if($cart_item['product_id'] == $ID)
	{
		$total_data = $cart_item['quantity'];
	}
endforeach;
echo json_encode( array( 
"success" => true,
"error_code" => 0,
"message" => "Lấy thông tin menu thành công",
"data" => array(
"brand" => "5",
"name" => get_the_title($ID),
"category" => "",
"quantitative" => get_field('unit',$ID),
"price" => get_field('_price',$ID),
"des" => get_field('desc',$ID),
"img" => thumb($ID,'full',1),
"only_hcm" => "0"
),
"total_data"=>$total_data
) );
?>