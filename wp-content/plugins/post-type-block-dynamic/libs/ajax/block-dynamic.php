<?php
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Message;
use GDelivery\Libs\Config;
use GDelivery\Libs\Helper\Product;

class BlockDynamic extends \Abstraction\Core\AAjaxHook {

    private $redis;

    public function __construct()
    {
        parent::__construct();

        // Block dynamic
        add_action("wp_ajax_get_item_by_province", [$this, "getItemOnBlockDynamic"]);
        add_action("wp_ajax_get_item_by_merchant", [$this, "getItemOnBlockDynamicByMerchant"]);
        add_action("wp_ajax_save_block_dynamic", [$this, "saveDataBlockDynamic"]);

        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
    }

    public function getItemOnBlockDynamic() {
        $res = new Result();
        $keyWord = $_REQUEST['keyWord'];
//        if (mb_strlen($keyWord) < 3 ) {
//            $res->messageCode = Message::GENERAL_ERROR;
//            $res->message = 'Bạn phải nhập nhiều hơn 3 kí tự để tìm kiếm';
//
//            Response::returnJson($res);
//            die;
//        }
        $provinceId = $_REQUEST['provinceId'];

        if (!$provinceId) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Phải chọn tỉnh thành';

            Response::returnJson($res);
            die;
        }

        $items = [];
        $options = [
            'screen' => $_REQUEST['screen']
        ];
        if ($_REQUEST['blockType'] == 'merchant') {
            $getMerchants = Helper::getMerchantByProvince($provinceId, null, $options);
            if ($getMerchants->messageCode == Message::SUCCESS) {
                $items = $getMerchants->result;
            }
        } else {
            $getProducts = Helper::getProductSortUnSortByProvince($provinceId, null, $options);
            if ($getProducts->messageCode == Message::SUCCESS) {
                $items = $getProducts->result;
            }
        }

        if (!empty($items)) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'sorted' => $items['sorted'],
                'unSort' => $items['unSort']
            ];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

    public function getItemOnBlockDynamicByMerchant() {
        $res = new Result();
        $merchantId = $_REQUEST['merchantId'];

        if (!$merchantId) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Phải chọn merchant';

            Response::returnJson($res);
            die;
        }

        $items = [];
        $getProducts = Helper::getProductSortUnSortByMerchant($merchantId);
        if ($getProducts->messageCode == Message::SUCCESS) {
            $items = $getProducts->result;
        }

        if (!empty($items)) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = [
                'sorted' => $items['sorted'],
                'unSort' => $items['unSort']
            ];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm';
        }

        Response::returnJson($res);
        die;
    }

    public function saveDataBlockDynamic()
    {
        global $wpdb;
        $res = new Result();

        if ($_POST) {
            $data = $_REQUEST;

            if (empty($data['name']) ||
                empty($data['slug']) ||
                empty($data['shortName']) ||
                empty($data['items']) ) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thiếu dữ liệu';

                Response::returnJson($res);
                die;
            }

            $arr_img_ext = array('image/png', 'image/jpeg', 'image/jpg', 'image/gif');
            if (in_array($_FILES['thumbnail']['type'], $arr_img_ext)) {
                $uploadThumbnail = wp_upload_bits($_FILES['thumbnail']['name'], null, file_get_contents($_FILES['thumbnail']['tmp_name']));
            }
            if (in_array($_FILES['banner']['type'], $arr_img_ext)) {
                $uploadBanner = wp_upload_bits($_FILES['banner']['name'], null, file_get_contents($_FILES['banner']['tmp_name']));
            }

            $items = $data['items'];
            $arrTemp = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'short_name' => $data['shortName'],
                'type' => $data['blockType'],
                'province_id' => $data['provinceId'],
                'thumbnail' => $uploadThumbnail['url'] ?? '',
                'banner_url' => $uploadBanner['url'] ?? '',
                'description' => $data['description'],
                'items' => implode(',', $items),
            ];

            $wpdb->insert('wp_block_dynamic', $arrTemp);
            wp_reset_query();

            $res->result = '';
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Lưu thành công';

            Response::returnJson($res);
            die;
        }
    }
} //end class

// init class
$blockDynamicAjax = new BlockDynamic();
