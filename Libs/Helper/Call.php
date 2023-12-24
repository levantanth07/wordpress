<?php
namespace GDelivery\Libs\Helper;

class Call {
    /**
     * @param \WC_Order $order
     */
    public static function makeAcall($order)
    {
        $jsonRestaurant = $order->get_meta('restaurant_object');
        if ($jsonRestaurant) {
            // calling new order
            $data['type'] = 'external';
            $content = 'Nhà hàng có đơn hàng mới trên G Delivery, mã đơn hàng: ';
            foreach (str_split((string) $order->get_id()) as $one) {
                $content .= $one .'. ';
            }
            $content .= '. Xin nhắc lại, '.$content;
            $data['content'] = trim($content);
            $data['action'] = 'talk';
            $data['number'] = $jsonRestaurant->telephone;
            $stringeeService = new \GDelivery\Libs\StringeeService();
            $stringeeService->makeACall($data);
        }
    }

} // end class
