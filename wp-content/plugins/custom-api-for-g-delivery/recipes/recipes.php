<?php
/*
Api for banner
*/

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Recipes;
use Abstraction\Object\ApiMessage;

class RecipesApi extends \Abstraction\Core\AApiHook
{

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'recipes/list', array(
                'methods' => 'GET',
                'callback' => [$this, "listAll"],
            ));
        });
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'recipes/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "detail"],
            ));
        });
    }

    // add api
    public function listAll(WP_REST_Request $request)
    {
        $res = new Result();

        $params['page'] = $request['page'] ?? 1;
        $params['perPage'] = $request['perPage'] ?? 8;
        $recipes = Recipes::getAll($params);

        if ($recipes->messageCode === Message::GENERAL_ERROR) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Lấy danh sách recipes thất bại';

            return $res;
        }

        $res->messageCode = ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $recipes->result;

        Response::returnJson($res);
        die;
    }

    public function detail(WP_REST_Request $request)
    {
        $res = new Result();

        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = '';

        Response::returnJson($res);
        die;
    }
}

// init
$bannerApi = new BannerApi();
