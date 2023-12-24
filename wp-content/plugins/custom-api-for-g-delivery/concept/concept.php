<?php
/*
Api for Concept

*/

use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;

class ConceptApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/concept/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getConcepts"],
            ) );
        } );
    }

    public function getConcepts(WP_REST_Request $request)
    {
        $res = new Result();

        $getBrand = \GDelivery\Libs\Helper\Helper::getConcepts();

        if ($getBrand->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->result = $getBrand->result;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::GENERAL_ERROR;
            $res->message = $getBrand->message;
        }

        Response::returnJson($res);
        die;
    }
}
// init
$conceptApi = new ConceptApi();