<?php
/*
Api for brand

Get with post type: chinhanh

*/

use Abstraction\Core\AApiHook;
use GDelivery\Libs\Helper\Helper;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use GDelivery\Libs\Helper\Merchant;
use Abstraction\Object\ApiMessage;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Order;
use GDelivery\Libs\Helper\ScoringMerchant;

class MerchantApi extends AApiHook {

    public function __construct()
    {
        parent::__construct();

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', 'merchant/list', array(
                'methods' => 'GET',
                'callback' => [$this, "getListMerchants"],
            ) );
            register_rest_route( 'api/v1/merchant', '/(?P<id>\d+)/detail', array(
                'methods' => 'GET',
                'callback' => [$this, "merchantDetail"],
            ) );
            register_rest_route( 'api/v1/merchant/code', '/(?P<code>\d+)/detail', array(
                'methods' => 'GET',
                'callback' => [$this, "getMerchantByCode"],
            ) );
            register_rest_route( 'api/v1/merchant', '/(?P<id>\d+)/province', array(
                'methods' => 'GET',
                'callback' => [$this, "getMerchantsInProvince"],
            ) );
            register_rest_route( 'api/v1/merchant', '/(?P<id>\d+)/order-time', array(
                'methods' => 'POST',
                'callback' => [$this, "getMerchantsOrderTime"],
            ) );
            register_rest_route( 'api/v1/merchant', '/(?P<id>\d+)/category', array(
                'methods' => 'GET',
                'callback' => [$this, "getMerchantCategory"],
            ) );

            register_rest_route( 'api/v1', '/merchant/from/ids', array(
                'methods' => 'GET',
                'callback' => [$this, "getListMerchantFromListId"],
            ) );

            register_rest_route(
                'api/v1',
                '/merchant/scoring/list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getListScoringMerchants']
                ]
            );

            register_rest_route(
                'api/v1',
                '/merchant/scoring/specific-list',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getListSpecificScoringMerchants']
                ]
            );

            register_rest_route(
                'api/v1',
                '/merchant/scoring/(?P<id>\d+)',
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'getDetailScoringMerchant']
                ]
            );

            register_rest_route( 'api/v1', '/merchant/scoring/sort',
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'getSortedScoringMerchants'],
                ]
            );
        } );

        add_action('update_post', [$this, 'updatePostMerchant']);
    }

    public function updatePostMerchant($post_id){
        global $post;
        if ($post->post_type == 'merchant'){
            $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);
            $query = http_build_query(
                array(
                    'type' => 'tag',
                    'value' => [
                        'merchant-list',
                        'favorite',
                        'product',
                    ]
                )
            );
            try {
                if ($ipsBe) {
                    foreach ($ipsBe as $ip) {
                        if ($ip) {
                            file_get_contents("http://{$ip}/api/v1/services/clear-cache?{$query}");
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Clear cache không thành công: ' . $e->getMessage();
            }
            return;
        }
    }

    /**
     * Get list restaurant from brandID
     *
     * @param WP_REST_Request $request
     */
    public function getListMerchants(WP_REST_Request $request) {

        $res = new Result();
        $provinceId = $request['provinceId'];
        $filters = $request['filter'] ?? [];

        $option = [];
        if (isset($request['latitude'], $request['longitude'])) {
            $option = [
                'fromLatitude' => $request['latitude'],
                'fromLongitude' => $request['longitude']
            ];
        }
        if (isset($request['conceptIds']) && $request['conceptIds'] != '') {
            $option['conceptIds'] = $request['conceptIds'];
        }
        if (isset($request['brandId']) && $request['brandId'] != '') {
            $option['brandId'] = $request['brandId'];
        }
        if (isset($request['merchantType']) && $request['merchantType'] != '') {
            $option['merchantType'] = $request['merchantType'];
        }
        if (isset($filters['type']) && $filters['type'] != '') {
            $option['filterType'] = $filters['type'];
        }
        $option['page'] = isset($request['page']) ? (int) $request['page'] : 1;
        $option['perPage'] = isset($request['perPage']) ? (int) $request['perPage'] : 8;
        $listMerchants = Helper::getMerchantsInProvince($provinceId, $option);
        if ($listMerchants->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'success';
            $res->numberOfResult = $listMerchants->total;
            $res->total = $listMerchants->total;
            $res->lastPage = $listMerchants->lastPage;
            $res->currentPage = $listMerchants->currentPage;
            $res->result = [
                'data' => $listMerchants->result,
                'total' => $listMerchants->total,
                'currentPage' => $option['page'],
                'lastPage' => $listMerchants->lastPage,
                'perPage' => $option['perPage'],
            ];
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = $listMerchants->message;
        }

        Response::returnJson($res);
        die;
    }

    public function getMerchantByCode(WP_REST_Request $request)
    {
        $res = new \Abstraction\Object\Result();
        try {

            $restaurantCode = $request['code'];

            $option = [];
            if (isset($request['latitude'], $request['longitude'])) {
                $option = [
                    'fromLatitude' => $request['latitude'],
                    'fromLongitude' => $request['longitude']
                ];
            }
            $getMerchant = Helper::getMerchantByCode($restaurantCode, $option);
            if ($getMerchant->messageCode == Message::SUCCESS) {
                $res->result = $getMerchant->result;
                $res->messageCode = ApiMessage::SUCCESS;
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
            }
            $res->message = $getMerchant->message;

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function merchantDetail(WP_REST_Request $request)
    {
        $res = new \Abstraction\Object\Result();
        try {

            $id = $request['id'];

            $option = [];
            if (isset($request['latitude'], $request['longitude'])) {
                $option = [
                    'fromLatitude' => $request['latitude'],
                    'fromLongitude' => $request['longitude']
                ];
            }
            $getMerchant = Helper::getMerchant($id, $option);
            if ($getMerchant->messageCode == Message::SUCCESS) {
                $res->result = $getMerchant->result;
                $res->messageCode = ApiMessage::SUCCESS;
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
            }
            $res->message = $getMerchant->message;

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function getMerchantsInProvince(WP_REST_Request $request)
    {
        $res = new \Abstraction\Object\Result();
        try {
            $provinceId = $request['id'];
            $params = $request->get_params();
            $getMerchant = Helper::getMerchantsInProvince($provinceId, $params);
            if ($getMerchant->messageCode == Message::SUCCESS) {
                $res->result = $getMerchant->result;
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = $getMerchant->message;
            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = $getMerchant->message;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getMerchantsOrderTime(WP_REST_Request $request)
    {
        $res = new \Abstraction\Object\Result();
        try {

            $id = $request['id'];
            $time = '';
            if (isset($request['time']) && $request['time']) {
                $time = $request['time'];
            }

            $getMerchant = Helper::getMerchant($id);
            if ($getMerchant->messageCode == Message::SUCCESS) {

                $validRangeTime = Order::allowBlockTimesToOrder($getMerchant->result, $time);
                $res->result = $validRangeTime;
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'success';

            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = $getMerchant->message;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function getMerchantCategory(WP_REST_Request $request)
    {
        $res = new \Abstraction\Object\Result();
        try {
            $merchantId = $request['id'];

            $getMerchantCategory = Helper::getMerchantCategory($merchantId);

            if ($getMerchantCategory->messageCode == Message::SUCCESS) {
                $res->result = $getMerchantCategory->result;
                $res->messageCode = ApiMessage::SUCCESS;
                $res->message = 'success';

            } else {
                $res->messageCode = ApiMessage::GENERAL_ERROR;
                $res->message = $getMerchantCategory->message;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Có lỗi khi get data merchant category Woo: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function getListMerchantFromListId(WP_REST_Request $request)
    {
        $res = new Result();
        $merchantIds = explode(',', $request['merchantIds']);

        $query = [
            'post_type' => 'merchant',
            'post_status' => 'publish',
            'posts_per_page' => $request['perPage'] ?? -1,
            'paged' => $request['page'] ?? 1,
            'post__in' => $merchantIds,
        ];

        $options = [];
        if (isset($request['latitude'], $request['longitude'])) {
            $options = [
                'fromLatitude' => $request['latitude'],
                'fromLongitude' => $request['longitude']
            ];
        }

        $listData = Merchant::getListMerchantFromListId($query, $options);

        if ($listData->messageCode == Message::SUCCESS) {
            $res->messageCode = ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listData->result;
        } else {
            $res->messageCode = ApiMessage::GENERAL_ERROR;
            $res->message = 'Lấy danh sách merchant thất bại';
        }

        Response::returnJson($res);
        die;
    }

    public function getListScoringMerchants(WP_REST_Request $request)
    {
        $result = new Result();
        $params = $request->get_params();
        $merchants = ScoringMerchant::getListMerchants($params);
        if ($merchants->messageCode == Message::SUCCESS) {
            $result->messageCode = ApiMessage::SUCCESS;
            $result->message = 'Thành công';
            $result->result = [
                'data' => $merchants->result,
                'total' => $merchants->total,
                'currentPage' => isset($params['page']) ? (int) $params['page'] : 1,
                'lastPage' => $merchants->lastPage,
                'perPage' => isset($params['perPage']) ? (int) $params['perPage'] : 20,
            ];
        } else {
            $result->messageCode = ApiMessage::GENERAL_ERROR;
            $result->message = 'Lấy danh sách merchant thất bại';
        }

        Response::returnJson($result);
        die;
    }

    public function getListSpecificScoringMerchants(WP_REST_Request $request)
    {
        $result = new Result();
        $params = $request->get_params();
        $merchants = ScoringMerchant::getListSpecificMerchants($params);
        if ($merchants->messageCode == Message::SUCCESS) {
            $result->messageCode = ApiMessage::SUCCESS;
            $result->message = 'Thành công';
            $result->result = [
                'data' => $merchants->result,
                'total' => $merchants->total,
                'currentPage' => isset($params['page']) ? (int) $params['page'] : 1,
                'lastPage' => $merchants->lastPage,
                'perPage' => isset($params['perPage']) ? (int) $params['perPage'] : 20,
            ];
        } else {
            $result->messageCode = ApiMessage::GENERAL_ERROR;
            $result->message = 'Lấy danh sách merchant thất bại';
        }

        Response::returnJson($result);
        die;
    }

    public function getDetailScoringMerchant(WP_REST_Request $request)
    {
        $result = new Result();
        $merchantId = $request['id'];
        $merchant = ScoringMerchant::getDetailMerchant($merchantId);
        if ($merchant->messageCode == Message::SUCCESS) {
            $result->messageCode = ApiMessage::SUCCESS;
            $result->message = 'Thành công';
            $result->result = $merchant->result;
        } else {
            $result->messageCode = ApiMessage::BAD_REQUEST;
            $result->message = $merchant->message;
        }

        Response::returnJson($result);
        die;
    }

    public function getSortedScoringMerchants(WP_REST_Request $request)
    {
        $result = new Result();
        $params = $request->get_params();
        $merchantsResult = Merchant::getSortedScoringMerchants($params);
        if ($merchantsResult->messageCode == Message::SUCCESS) {
            $result->messageCode = ApiMessage::SUCCESS;
            $result->message = 'Thành công';
            $result->result = $merchantsResult->result;
        } else {
            $result->messageCode = ApiMessage::BAD_REQUEST;
            $result->message = $merchantsResult->message;
        }
        Response::returnJson($result);
        die;
    }
}

// init
$restaurantApi = new MerchantApi();
