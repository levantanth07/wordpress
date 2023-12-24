<?php
/*
Api for banner
*/

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\News;
use Abstraction\Object\ApiMessage;

class NewsApi extends \Abstraction\Core\AApiHook
{

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'news/list', array(
                'methods' => 'GET',
                'callback' => [$this, "newsList"],
            ));
        });
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'news/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => [$this, "detail"],
            ));
        });
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'news/top-one', array(
                'methods' => 'GET',
                'callback' => [$this, "topOne"],
            ));
        });
        add_action('rest_api_init', function () {
            register_rest_route('api/v1', 'news/top-list', array(
                'methods' => 'GET',
                'callback' => [$this, "topList"],
            ));
        });
    }

    // add api
    public function newsList(WP_REST_Request $request)
    {
        $res = new Result();

        $params['page'] = $request['page'] ?? 1;
        $params['perPage'] = $request['perPage'] ?? 8;
        $news = News::getAll($params);

        if ($news->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $news->result;

            Response::returnJson($res);
            die;
        }

        $res->messageCode = ApiMessage::GENERAL_ERROR;
        $res->message = 'Lấy dánh sách bản tin thất bại!';

        Response::returnJson($res);
        die;
    }

    public function detail(WP_REST_Request $request)
    {
        $res = new Result();

        $news = News::newsDetail($request['id']);

        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $news;

        Response::returnJson($res);
        die;
    }

    public function topOne(WP_REST_Request $request)
    {
        $res = new Result();

        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = '';

        Response::returnJson($res);
        die;
    }

    public function topList(WP_REST_Request $request)
    {
        $res = new Result();

        $params['page'] = $request['page'] ?? 1;
        $params['perPage'] = $request['perPage'] ?? 8;
        $news = News::topList($params);

        if ($news->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $news->result;

            Response::returnJson($res);
            die;
        }

        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = '';

        Response::returnJson($res);
        die;
    }
}

// init
$newsApi = new NewsApi();
