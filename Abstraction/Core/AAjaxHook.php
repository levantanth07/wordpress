<?php
namespace Abstraction\Core;

use GDelivery\Libs\Helper\Response;

abstract class AAjaxHook {

    public function mustLogin()
    {
        $res = new \Abstraction\Object\Result();
        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
        $res->messageCode = 'Be honest, play fair!';

	    Response::returnJson($res);
        die;
    }

    public function __construct()
    {

    }
}