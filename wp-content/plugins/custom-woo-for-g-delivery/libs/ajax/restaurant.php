<?php
class AjaxRestaurant extends \Abstraction\Core\AAjaxHook {
    private $bookingService;

    public function __construct()
    {
        parent::__construct();

        $this->bookingService = new \GDelivery\Libs\BookingService();

        // list nearest restaurant
        add_action("wp_ajax_nearest_restaurant", [$this, "nearestRestaurant"]);
        add_action("wp_ajax_nopriv_nearest_restaurant", [$this, "mustLogin"]);

        // list change restaurant
        add_action("wp_ajax_change_restaurant", [$this, "changeRestaurant"]);
        add_action("wp_ajax_nopriv_change_restaurant", [$this, "mustLogin"]);

    }

    public function nearestRestaurant()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['categoryId'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "nearest_restaurant")) {
                $currentCategoryId = $_REQUEST['categoryId'];
                if (isset($_REQUEST['wardId'])) {
                    $wardId = $_REQUEST['wardId'];
                    $getWard = $this->bookingService->getWard($wardId);
                    if ($getWard->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $address = new \stdClass();
                        $address->addressLine1 = $address->address = $_REQUEST['addressLine1'];
                        $address->provinceId = $_REQUEST['provinceId'];
                        $address->districtId = $_REQUEST['districtId'];
                        $address->wardId = $wardId;

                        $address->longitude = $getWard->result->longitude;
                        $address->latitude = $getWard->result->latitude;
                    } else {
                        // get selected address
                        $address = \GDelivery\Libs\Helper\Helper::getSelectedAddress();
                    }
                } elseif (isset($_REQUEST['googleMapPlaceId'], $_REQUEST['latitude'], $_REQUEST['longitude'])) {
                    $address = new \stdClass();
                    $address->addressLine1 = $address->address = isset($_REQUEST['addressLine1']) ? $_REQUEST['addressLine1'] : '';
                    $address->id = isset($_REQUEST['addressId']) ? $_REQUEST['addressId'] : '';
                    $address->wardName = isset($_REQUEST['wardName']) ? $_REQUEST['wardName'] : '';
                    $address->districtName = isset($_REQUEST['districtName']) ? $_REQUEST['districtName'] : '';
                    $address->provinceName = isset($_REQUEST['provinceNameName']) ? $_REQUEST['provinceName'] : '';
                    $address->longitude = $_REQUEST['longitude'];
                    $address->latitude = $_REQUEST['latitude'];
                    $address->googleMapPlaceId = $_REQUEST['googleMapPlaceIdPlaceId'];
                } else {
                    // get selected address
                    $address = \GDelivery\Libs\Helper\Helper::getSelectedAddress();
                }

                if ($address) {
                    $getNearestRestaurant = \GDelivery\Libs\Helper\Helper::getNearestRestaurant($currentCategoryId, $address);
                    if ($getNearestRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = 'Thành công';
                        $res->result = $getNearestRestaurant->result;

                        // set to session
                        \GDelivery\Libs\Helper\Helper::setSelectedRestaurant($getNearestRestaurant->result);
                        \GDelivery\Libs\Helper\Helper::setSelectedAddress($address);
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = $getNearestRestaurant->message;
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Có lỗi khi lấy thông tin địa chỉ';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass params';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function changeRestaurant()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['beHonest'], $_REQUEST['restaurantCode'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "change_restaurant")) {
                $restaurantCode = $_REQUEST['restaurantCode'];
                $selectedAddress = \GDelivery\Libs\Helper\Helper::getSelectedAddress();

                // get restaurant info
                $getRestaurant = \GDelivery\Libs\Helper\Helper::getMerchantByCode(
                    $restaurantCode,
                    [
                        'fromLatitude' => $selectedAddress->latitude,
                        'fromLongitude' => $selectedAddress->longitude,
                    ]
                );
                if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $selectedRestaurant = $getRestaurant->result;

                    \GDelivery\Libs\Helper\Helper::setSelectedRestaurant($selectedRestaurant);
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $selectedRestaurant;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Nhà hàng không tồn tại';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass params';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

} //end class

// init class
$provinceAjax = new AjaxRestaurant();
