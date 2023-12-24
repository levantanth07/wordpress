<?php

use \Abstraction\Core\AAjaxHook;
use \Abstraction\Object\Message;
use \GDelivery\Libs\Helper\Helper;
use \Abstraction\Object\Result;
use \GDelivery\Libs\Helper\Product;
use \GDelivery\Libs\Helper\Response;
use \GDelivery\Libs\BookingService;
use \GDelivery\Libs\Helper\Banner;

class AjaxHome extends AAjaxHook {
    private $locationService;
    private $bookingService;

    private function listBrands($provinceId)
    {
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

        $arrBrands = [];

        $brands = get_terms('product_cat', $args);
        if ($brands) {
            foreach ($brands as $brand) {
                $temp = new \stdClass();
                $temp->logo = get_field('product_category_logo', 'product_cat_'.$brand->term_id);
                $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$brand->term_id);
                $temp->url = get_term_link($brand);
                $temp->name = $brand->name;
                $temp->id = $brand->term_id;

                $arrBrands[] = $temp;
            }

            return $arrBrands;
        } else {
            return [];
        }
    }

    public function __construct()
    {
        parent::__construct();

        $this->bookingService = new BookingService();

        // list search address
        add_action("wp_ajax_refresh_home_content", [$this, "refreshHomeContent"]);
        add_action("wp_ajax_nopriv_refresh_home_content", [$this, "refreshHomeContent"]);

        // Ajax reload hot deal page
        add_action("wp_ajax_refresh_hotdeal_content", [$this, "refreshHotDealContent"]);
        add_action("wp_ajax_nopriv_refresh_hotdeal_content", [$this, "refreshHotDealContent"]);
    }

    public function refreshHomeContent()
    {
        $res = new Result();

        if (isset($_REQUEST['selectedProvinceId'])) {
            $selectedProvinceId = $_REQUEST['selectedProvinceId'];
            $getProvince = $this->bookingService->getProvince($selectedProvinceId);
            $currentProvince = $getProvince->result;
            Helper::setSelectedProvince($currentProvince);
        } else {
            $selectedProvinceId = Helper::getSelectedProvince()->id;
        }

        // return data
        $returnData = new \stdClass();

        // list banners
        $returnData->banners = $sliders = Banner::getBanners('home-sliders', $selectedProvinceId);

        // list brands
        $returnData->brands = $this->listBrands($selectedProvinceId);

        // hot deal products
        $hotDealProducts = \GDelivery\Libs\Helper\Product::getProductByGroup('hot-deal', $selectedProvinceId, 1, -1);
        $hotDealProducts = array_slice(
            \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, "icook:province:{$selectedProvinceId}:home-hotdeal:sort-product"),
            0,
            8,
            true);
        $returnData->hotDealProducts = $hotDealProducts;

        // List tag of product.
        $returnData->listProductTags = Product::getListProductTags($selectedProvinceId);

        // list suggest products
        $allSuggestionProducts = Product::getProductByTagOnHome('', $selectedProvinceId, 1, -1);
        $returnData->suggestProducts = array_slice($allSuggestionProducts->sorted, 0, 8, true);

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $returnData;

        Response::returnJson($res);
        die;
    }

    public function refreshHotDealContent()
    {
        $res = new Result();

        if (isset($_REQUEST['selectedProvinceId'])) {
            $selectedProvinceId = $_REQUEST['selectedProvinceId'];
            $getProvince = $this->bookingService->getProvince($selectedProvinceId);
            $currentProvince = $getProvince->result;
            Helper::setSelectedProvince($currentProvince);
        } else {
            $selectedProvinceId = Helper::getSelectedProvince()->id;
        }

        // return data
        $returnData = new \stdClass();

        // hot deal products
        $hotDealProducts = Product::getProductByGroup('hot-deal', $selectedProvinceId, 1, -1);
        $returnData->hotDealProducts = \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, "icook:province:{$selectedProvinceId}:home-hotdeal:sort-product");

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $returnData;

        Response::returnJson($res);
        die;
    }

} //end class

// init class
$ajaxHome = new AjaxHome();
