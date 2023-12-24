<?php
namespace GDelivery\Libs\Helper;

use GDelivery\Libs\BookingService;
use GDelivery\Libs\Helper\Response as Response;
use Abstraction\Object\Message;

class Restaurant {

    /**
     * @param \WP_Post $post
     * @param array|boolean $options
     *                      when pass true, get with booking restaurant
     *
     * @return \stdClass
     */
    public static function getRestaurantInfo($post, $options = [])
    {
        $temp = new \stdClass();

        $temp->id = $post->ID;
        $temp->name = $post->post_title;

        $type = get_field('restaurant_type', $post->ID);
        $temp->type = $type;

        // nhà hàng nội bộ
        if ($type == 1) {
            // get restaurant info from booking
            $bookingService = new BookingService();
            if ($options === true) {
                $getRestaurantInfo = $bookingService->getRestaurant($temp->restaurantCode);
                if ($getRestaurantInfo->messageCode == Message::SUCCESS) {
                    $temp->restaurant = $getRestaurantInfo->result;
                } else {
                    $temp->restaurant = null;
                }
            } elseif(is_array($options)) {
                $getRestaurantInfo = $bookingService->getRestaurant($temp->restaurantCode, $options);
                if ($getRestaurantInfo->messageCode == Message::SUCCESS) {
                    $temp->restaurant = $getRestaurantInfo->result;
                } else {
                    $temp->restaurant = null;
                }
            } else {
                $temp->restaurant = null;
            }
            $temp->restaurantCode = get_field('restaurant_code', $temp->id);
            $temp->rkOrderCategoryCode = get_field('restaurant_order_category_code', $temp->id);
            $temp->rkWaiterCode = get_field('restaurant_waiter_code', $temp->id);
            $temp->rkTableCode = get_field('restaurant_table_code', $temp->id);
            $temp->allowCallNewOrder = get_field('restaurant_allow_calling_new_order', $temp->id);
            $temp->allowEmailNewOrder = get_field('restaurant_allow_send_mail_new_order', $temp->id);
            $temp->allowGrabExpress = get_field('restaurant_allow_grab_express', $temp->id);
            $temp->allowCutleryTool = get_field('restaurant_allow_cutlery_tool', $temp->id);

            if ($temp->restaurant) {
                $temp->provinceId = $temp->restaurant->province->id;
                $temp->telephone = $temp->restaurant->telephone;
                $temp->address = $temp->restaurant->address;
                $temp->longitude = $temp->restaurant->longitude;
                $temp->latitude = $temp->restaurant->latitude;
            } else {
                $temp->provinceId = null;
                $temp->telephone = null;
                $temp->address = null;
                $temp->longitude = null;
                $temp->latitude = null;
            }
        } else {
            // điểm bán liên kết
            $temp->restaurantCode = null;
            $temp->rkOrderCategoryCode = null;
            $temp->rkWaiterCode = null;
            $temp->rkTableCode = null;
            $temp->allowCallNewOrder = null;
            $temp->allowEmailNewOrder = null;
            $temp->allowGrabExpress = null;
            $temp->allowCutleryTool = null;

            $temp->provinceId = get_field('restaurant_province_id', $temp->id);
            $temp->telephone = get_field('restaurant_telephone', $temp->id);
            $temp->address = get_field('restaurant_address', $temp->id);
            $temp->longitude = get_field('restaurant_longitude', $temp->id);
            $temp->latitude = get_field('restaurant_latitude', $temp->id);

        }

        return $temp;
    }

    /**
     * Check restaurant closed
     *
     * @param object $selectedRestaurant
     * @param object $res Result
     */
    public static function checkStatusRestaurant($selectedRestaurant, $res) {
        if ('publish' !== get_post_status($selectedRestaurant->id)) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Hiện tại nhà hàng đã đóng cửa. Vui lòng liên hệ hotline để được hỗ trợ!';
            Response::returnJson($res);
            die;
        }
    }

    /**
     * Selected restaurant empty
     *
     * @param object $selectedRestaurant
     * @param object $res Result
     */
    public static function selectedRestaurantEmpty($selectedRestaurant, $res) {
        if (!$selectedRestaurant) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Hiện tại không có nhà hàng nào phục vụ.</br>Vui lòng liên hệ hotline để được hỗ trợ!';
            Response::returnJson($res);
            die;
        }
    }
}