<?php
/** @var WC_Order $order */

$strMessage ="
<html>
    <head>
    <style>
        table {
          border-collapse: collapse;
          width: 100%; 
          max-width: 600px;
        }
        
        table, td, th {
          text-align: left;
          border: 1px solid black;
        }
        
        .td-right {
          text-align: right;
        }
    </style>
    </head>
    <body>
        <h3>Đơn đặt hàng {$order->get_id()} đã được tạo trên Golden SpoonS:</h3>
        <p>Nhà hàng: {$order->get_meta('restaurant_object')->name} - {$order->get_meta('restaurant_object')->telephone} </p>
        <p>Tên khách hàng: {$order->get_shipping_first_name()}</p>
        <p>Điện thoại : {$order->get_shipping_phone()}</p>
        <p>Ngày giao: {$order->get_meta('delivery_date')} {$order->get_meta('delivery_time')}</p>
        <p>Ghi chú: {$order->get_customer_note()}</p>
        <p>Hình thức thanh toán: ".\GDelivery\Libs\Helper\Helper::textPaymentMethod($order->get_meta('payment_method'))."</p>
        <p>Thông tin chi tiết: </p>
        <table>
            <thead>
            <tr>
                <th>Số lượng</th>
                <th>Tên món</th>
                <th class='td-right'>Thành tiền</th>
            </tr>
            </thead>
            <tbody>";
            if ($order->get_items()) {
                foreach ( $order->get_items() as $oneItem ){
                    $strMessage .= "<tr>
                                            <td><span>".$oneItem->get_quantity()."</span></td>
                                            <td>".$oneItem->get_name()."</td>
                                            <td class='td-right'>".number_format($oneItem->get_total('number'), 2)." ₫</td>
                                        </tr>";
                }
            }
            $strMessage .="
            <tr>
                <td>Tổng tiền hàng trước thuế</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('total_price_without_shipping'), 2)." ₫</td>            
            </tr>
            <tr>
                <td>Phí vận chuyển trước VAT</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('shipping_price'), 2)." ₫</td>            
            </tr>
            <tr>
                <td>Tổng tiền giảm giá trước VAT</td>
                <td></td>
                <td class='td-right'>".number_format($totalDiscount, 2)." ₫</td>            
            </tr>
            <tr>
                <td>Tổng tiền trước VAT</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('total_price') - $totalDiscount, 2)." ₫</td>            
            </tr>
            <tr>
                <td>VAT</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('total_tax'), 2)." ₫</td>            
            </tr>
            <tr>
                <td>Tổng tiền sau VAT</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('total_price') - $totalDiscount + $order->get_meta('total_tax'), 2)." ₫</td>            
            </tr>
            <tr>
                <td>Tổng giảm giá sau VAT</td>
                <td></td>
                <td class='td-right'>".number_format($totalCashVoucher, 2)." ₫</td>            
            </tr>
            <tr>
                <td>Tổng tiền thanh toán</td>
                <td></td>
                <td class='td-right'>".number_format($order->get_meta('total_pay_sum'), 2)." ₫</td>            
            </tr>
            </tbody>
        </table>
        <p>Vui lòng cập nhật trạng thái đơn trên trang: <a href='".site_url('restaurant-order-detail')."?id=".$order->get_id()."'>".site_url('restaurant-order-detail')."?id=".$order->get_id()."</a></p>
    </body>
</html>";