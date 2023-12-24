<?php

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;

class AjaxProvince extends \Abstraction\Core\AAjaxHook {
    private $bookingService;

    public function __construct()
    {
        parent::__construct();

        $this->bookingService = new \GDelivery\Libs\BookingService();

        // list provinces
        add_action("wp_ajax_list_province", [$this, "listProvinces"]);
        add_action("wp_ajax_nopriv_list_province", [$this, "listProvinces"]);

        // province info
        add_action("wp_ajax_province_info", [$this, "provinceInfo"]);
        add_action("wp_ajax_nopriv_province_info", [$this, "provinceInfo"]);

        // district info
        add_action("wp_ajax_district_info", [$this, "districtInfo"]);
        add_action("wp_ajax_nopriv_district_info", [$this, "districtInfo"]);

        // list brand by province
        add_action("wp_ajax_list_brand_in_province", [$this, "listBrandInProvince"]); // todo review and remove it later
        add_action("wp_ajax_nopriv_list_brand_in_province", [$this, "listBrandInProvince"]); // todo review and remove it later

        add_action("wp_ajax_list_brands_in_province", [$this, "listBrandsInProvince"]);
        add_action("wp_ajax_nopriv_list_brands_in_province", [$this, "listBrandsInProvince"]);

        add_action("wp_ajax_setting_get_brands_in_province", [$this, "settingGetBrandsInProvince"]);
    }

    public function listProvinces()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "list_province")) {
                $listProvinces = $this->bookingService->getProvinces();
                if ($listProvinces->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $listProvinces->result;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi lấy thông tin tỉnh thành';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass order id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function provinceInfo($provinceId)
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['id'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "province_info")) {
                $provinceId = $_REQUEST['id'];
                $getProvince = $this->bookingService->getProvince($provinceId);
                if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $getProvince->result;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi lấy thông tin tỉnh thành';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass province id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function districtInfo($districtId)
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['id'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "district_info")) {
                $districtId = $_REQUEST['id'];
                $get = $this->bookingService->getDistrict($districtId);
                if ($get->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $get->result;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi lấy thông tin tỉnh thành';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass province id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function listBrandInProvince()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['provinceId'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "list_brand_in_province")) {
                $scene = isset($_REQUEST['scene']) ? $_REQUEST['scene'] : '';
                $provinceId = $_REQUEST['provinceId'];

                $getProvince = $this->bookingService->getProvince($provinceId);
                if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $currentProvince = $getProvince->result;
                    
                    if (in_array($currentProvince->id, [43, 27, 12])) {
                        $checkProvinceAvailable = \GDelivery\Libs\Helper\Province::checkProvinceAvailable($currentProvince->id);
                        $res->messageCode = $checkProvinceAvailable->messageCode;
                        $res->message = $checkProvinceAvailable->message;
                    } else {
                        // get brand in province
                        $args = [
                            'hide_empty' => true,
                            'meta_query' => [
                                [
                                    'key'       => 'product_category_province_id',
                                    'value'     => $provinceId,
                                    'compare'   => '='
                                ],
                                [
                                    'key'       => 'product_category_is_show',
                                    'value'     => 1,
                                    'compare'   => '='
                                ]
                            ]
                        ];

                        $brands = get_terms('product_cat', $args);
                        if ($brands) {
                            // reset current category and cart
                            // todo xử lý thêm logic các kiểu gì đó thì xử lý tiếp đoạn phía sau
                            $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
                            $validCategory = false;

                            $arrBrands = [];
                            foreach ( $brands as $brand ) :
                                $temp = new \stdClass();
                                $temp->logo = get_field('product_category_logo', 'product_cat_'.$brand->term_id);
                                $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$brand->term_id);
                                $temp->url = get_term_link($brand);
                                $temp->name = $brand->name;
                                $temp->id = $brand->term_id;

                                $arrBrands[] = $temp;

                                // check category
                                if ($brand->term_id == $currentCategory->term_id) {
                                    $validCategory = true;
                                }
                            endforeach; // end foreach brands

                            if ($validCategory) {
                                // result
                                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                                $res->message = 'Thành công';
                                $res->result = [
                                    'brands' => $arrBrands,
                                    'province' => $currentProvince
                                ];
                                // set selected province
                                \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);

                                \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
                                WC()->cart->empty_cart();
                            } else {
                                $bookingService = new \GDelivery\Libs\BookingService();
                                $getProvince = $bookingService->getProvince(get_field('product_category_province_id', 'product_cat_'.$currentCategory->term_id));

                                $res->messageCode = 407;
                                $res->message = "Bạn đang chọn địa chỉ ở {$currentProvince->name} không hợp lệ để mua ở {$currentCategory->name} {$getProvince->result->name}";
                            }
                        } else {
                            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                            $res->message = "G-Delivery chưa phục vụ giao hàng tại khu vực {$currentProvince->name}";
                        }
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Không có thông tin tỉnh thành phục vụ';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Fail to check nonce. Be honest, play fair!';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass province id';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function listBrandsInProvince()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['provinceId'])) {
            $provinceId = $_REQUEST['provinceId'];
        } else {
            $provinceId = \GDelivery\Libs\Helper\Helper::getSelectedProvince()->id;
        }

        if (isset($_REQUEST['scene'])) {
            $scene = $_REQUEST['scene'];
        } else {
            $scene = 'home';
        }

        $getProvince = $this->bookingService->getProvince($provinceId);
        if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $currentProvince = $getProvince->result;

            // set selected province
            \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);

            if (in_array($currentProvince->id, [43, 27, 12])) {
                $checkProvinceAvailable = \GDelivery\Libs\Helper\Province::checkProvinceAvailable($currentProvince->id);
                $res->messageCode = $checkProvinceAvailable->messageCode;
                $res->message = $checkProvinceAvailable->message;
            } else {
                $brands = \GDelivery\Libs\Helper\Category::getListInProvince($provinceId);
                if ($brands) {
                    // reset current category and cart
                    // todo xử lý thêm logic các kiểu gì đó thì xử lý tiếp đoạn phía sau
                    \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
                    \GDelivery\Libs\Helper\Helper::setCurrentCategory(null);
                    WC()->cart->empty_cart();

                    $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
                    $customerAddress = \GDelivery\Libs\Helper\Helper::getSelectedAddress();
                    $validCategory = false;

                    $arrBrands = [];
                    foreach ( $brands as $brand ) :
                        $arrBrands[] = $brand;

                        // check category
                        if ($brand->id == $currentCategory->term_id) {
                            $validCategory = true;
                        }
                    endforeach; // end foreach brands

                    if ($currentCategory && $scene != 'home' ) {
                        if ($validCategory) {
                            // result
                            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                            $res->message = 'Thành công';
                            $res->result = [
                                'brands' => $arrBrands,
                                'province' => $currentProvince
                            ];

                            // set selected province
                            \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);
                        } else {
                            $bookingService = new \GDelivery\Libs\BookingService();
                            $getProvince = $bookingService->getProvince(get_field('product_category_province_id', 'product_cat_'.$currentCategory->term_id));

                            $res->messageCode = 407;
                            $res->message = 'Bạn đang chọn địa chỉ ở '.$customerAddress->provinceName.' không hợp lệ để mua ở '.$currentCategory->name.' '.$getProvince->result->name;
                        }
                    } else {
                        // result
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = 'Thành công';
                        $res->result = [
                            'brands' => $arrBrands,
                            'province' => $currentProvince
                        ];
                        // set selected province
                        \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = "G-Delivery chưa phục vụ giao hàng tại khu vực {$currentProvince->name}";
                }
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Không có thông tin tỉnh thành phục vụ';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function settingGetBrandsInProvince()
    {
        $res = new Result();
        if (isset($_REQUEST['provinceId'])) {
            $provinceId = $_REQUEST['provinceId'];
            // get brand in province
            $args = [
                'hide_empty' => true,
                'meta_query' => [
                    [
                        'key' => 'product_category_province_id',
                        'value' => $provinceId,
                        'compare' => '='
                    ],
                    [
                        'key' => 'product_category_is_show',
                        'value' => 1,
                        'compare' => '='
                    ]
                ]
            ];

            $brands = get_terms('product_cat', $args);
            if ($brands) {
                $arrBrands = [];
                foreach ($brands as $brand) :
                    $temp = new \stdClass();
                    $temp->logo = get_field('product_category_logo', 'product_cat_' . $brand->term_id);
                    $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_' . $brand->term_id);
                    $temp->url = get_term_link($brand);
                    $temp->name = $brand->name;
                    $temp->id = $brand->term_id;
                    $temp->brandId = (int) get_field('product_category_brand_id', 'product_cat_' . $brand->term_id);

                    $arrBrands[] = $temp;
                endforeach; // end foreach brands

                $res->messageCode = Message::SUCCESS;
                $res->message = "Lấy danh sách thương hiệu thành công";
                $res->result = $arrBrands;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = "G-Delivery chưa phục vụ giao hàng tại khu vực bạn chọn";
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Need to pass province id';
        }

        Response::returnJson($res);
        die;
    }
} //end class

// init class
$provinceAjax = new AjaxProvince();
