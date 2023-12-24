<?php
namespace GDelivery\Libs\Helper;

class Response {
    public static function returnJson($res, $status = 200)
    {
        header('Content-Type: application/json', true, $status);
        echo \json_encode($res);
    }
}