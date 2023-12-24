<?php

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Category;

class AjaxBrand extends \Abstraction\Core\AAjaxHook {
    public function __construct()
    {
        parent::__construct();

        add_action("wp_ajax_check_selected_brand", [$this, "checkSelectedBrand"]);
        add_action("wp_ajax_nopriv_check_selected_brand", [$this, "checkSelectedBrand"]);
    }

    public function checkSelectedBrand()
    {
        $res = new Result();

        if (isset($_POST['categoryId'])) {
            $categoryId = $_POST['categoryId'];
            $res = Category::checkSelectedBrand($res, $categoryId);
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Hmm, bạn chưa chọn thương hiệu';
        }

        Response::returnJson($res);
        die;
    }
} // end class

// init
$ajaxBrand = new AjaxBrand();