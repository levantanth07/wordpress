<?php
class AjaxAddress extends \Abstraction\Core\AAjaxHook {

    private $locationService;

    public function __construct()
    {
        parent::__construct();

        $this->locationService = new \GDelivery\Libs\Location();
        // list search address
        add_action("wp_ajax_search_address", [$this, "search"]);
        add_action("wp_ajax_nopriv_search_address", [$this, "mustLogin"]);

    }

    public function search()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'], $_REQUEST['address'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "search_address")) {
                // search address
                $search = $this->locationService->getGoongAddress($_REQUEST['address']);
                if ($search->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $search->result;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi tìm thông tin địa chỉ';
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
$provinceAjax = new AjaxAddress();
