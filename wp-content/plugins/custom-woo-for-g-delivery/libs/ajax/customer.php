<?php
class AjaxCustomer extends \Abstraction\Core\AAjaxHook {
    private $tgsService;

    public function __construct()
    {
        parent::__construct();

        $this->bookingService = new \GDelivery\Libs\BookingService();
        $this->tgsService = new \GDelivery\Libs\TGSService();

        // list nearest restaurant
        add_action("wp_ajax_save_address", [$this, "saveAddress"]);
        add_action("wp_ajax_nopriv_save_address", [$this, "mustLogin"]);

    }

    public function saveAddress()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "save_address")) {
                $address = $_REQUEST['address'];

                // process address
                $textDeliveryAddress = $address['address'];
                $addressParams = explode(',', $textDeliveryAddress);

                if (\count($addressParams) == 6) {
                    // in case: "Tầng 5 TTTM Hà Nội Centerpoint, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội, Vietnam"
                    $address['addressLine1'] = $address['address'] = trim($addressParams[0]).', '.trim($addressParams[1]);
                    $address['wardName'] = trim($addressParams[2]);
                } elseif (\count($addressParams) == 5) {
                    // in case: "315 Trường Chinh, Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $address['addressLine1'] = $address['address'] = trim($addressParams[0]);
                    $address['wardName'] = trim($addressParams[1]);
                } elseif (\count($addressParams) == 4) {
                    // in case: "Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $address['addressLine1'] = $address['address'] = trim($addressParams[0]);
                    $address['wardName'] = '';
                } else {
                    $address['addressLine1'] = $address['address'] = $textDeliveryAddress;
                    $address['wardName'] = '';
                }

                if (isset($address['id'])) {
                    // update address
                    $doAddress = $this->tgsService->updateAddress(
                        $address['id'],
                        $address,
                        \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                    );
                } else {
                    // add new address
                    $doAddress = $this->tgsService->addNewAddress(
                        $address,
                        \GDelivery\Libs\Helper\User::currentCustomerInfo()->customerAuthentication
                    );
                }

                if ($doAddress->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';

                    $addressObj = $doAddress->result;
                    if (!isset($addressObj->address)) {
                        $addressObj->address = $addressObj->addressLine1;
                    }
                    $res->result = $addressObj;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi xử lý địa chỉ: '.$doAddress->message;
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
$provinceAjax = new AjaxCustomer();
