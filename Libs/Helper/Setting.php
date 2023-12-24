<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;

class Setting {

    public static function getPaymentMethod($params) {
        $res = new Result();
        $listPaymentMethod = Payment::$paymentMethod;
        $arrPaymentMethod = [];
        if (isset($params['isActive'])) {
            foreach ($listPaymentMethod as $key => $value) {
                $isActive = get_option('enable_' . $key);
                if ($isActive == $params['isActive']) {
                    $arrPaymentMethod[] = [
                        'slug' => $key,
                        'name' => $value,
                        'isActive' => $isActive,
                    ];
                }
            }
        } else {
            foreach ($listPaymentMethod as $key => $value) {
                $isActive = get_option('enable_' . $key);
                $arrPaymentMethod[] = [
                    'slug' => $key,
                    'name' => $value,
                    'isActive' => $isActive,
                ];
            }
        }

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $arrPaymentMethod;

        return $res;
    }
} // end class
