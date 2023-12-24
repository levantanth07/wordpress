<?php
/*
    Api for complain
*/

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;

add_action('admin_menu', 'registerManageComplainMenu');

function registerManageComplainMenu() {
    add_menu_page(
        'Quản lý khiếu nại',
        'Khiếu nại',
        'edit_posts',
        'manage-complain',
        '',
        'dashicons-warning',
        4
    );
}

class ComplainApi extends \Abstraction\Core\AApiHook {

    public function __construct()
    {
        parent::__construct();

        add_action( 'rest_api_init', function () {
            register_rest_route( 'api/v1', '/make/complain',
                [
                    'methods' => 'POST',
                    'callback' => [$this, "makeAComplain"],
                ]
            );
            register_rest_route( 'api/v1', '/get/(?P<orderId>\d+)/complain',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getComplain"],
                ]
            );
            register_rest_route( 'api/v1', '/update/(?P<id>\d+)/complain',
                [
                    'methods' => 'PUT',
                    'callback' => [$this, "updateComplain"],
                ]
            );

            register_rest_route( 'api/v1', '/complain/(?P<postId>\d+)/addComment',
                [
                    'methods' => 'POST',
                    'callback' => [$this, "addComment"],
                ]
            );
            register_rest_route( 'api/v1', '/complain/(?P<postId>\d+)/resolve',
                [
                    'methods' => 'PUT',
                    'callback' => [$this, "resolveComplain"],
                ]
            );
            register_rest_route( 'api/v1', '/complain/(?P<postId>\d+)/complete',
                [
                    'methods' => 'PUT',
                    'callback' => [$this, "completeComplain"],
                ]
            );
        });
    }

    public function makeAComplain(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $isPending = false;
        $postId = null;

        if (
            isset(
                $params['orderId'],
                $params['title'],
                $params['customerName'],
                $params['customerPhone'],
                $params['restaurantCode'],
                $params['errorType']
            )
        ) {
            $getComplains = (new GDelivery\Libs\Helper\Complain)->getComplain(
                [
                    'orderId' => $params['orderId']
                ]
            );
            if ($getComplains->messageCode == Message::SUCCESS) {
                $complains = $getComplains->result;
                if ($complains[0]->status != \GDelivery\Libs\Helper\Complain::STATUS_CLOSE) {
                    $postId = $complains[0]->id;
                    $isPending = true;
                }
            }

            $orderId = $params['orderId'];
            $getOrder = wc_get_order($orderId);

            if ($getOrder) {
                $userId = $getOrder->get_customer_id();
                $customer = get_user_by('id', $userId);
                if (!$isPending) {
                    $postId = wp_insert_post(array(
                        'post_title' => $params['title'],
                        'post_type' => 'complain',
                        'post_status' => 'publish',
                        'meta_input' => [
                            'orderId' => $params['orderId'],
                            'customerName' => $params['customerName'],
                            'customerPhone' => $params['customerPhone'],
                            'restaurantCode' => $params['restaurantCode'],
                            'errorType' => $params['errorType'],
                            'note' => $params['note'] ?? '',
                            'status' => \GDelivery\Libs\Helper\Complain::STATUS_PENDING,
                            'source' => $params['source'] ?? "Ecommerce App",
                        ]
                    ));
                    if ($postId) {
                        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                        $res->message = 'Thành công';
                        $res->result = $postId;
                    } else {
                        $res->messageCode = \Abstraction\Object\ApiMessage::NOT_FOUND;
                        $res->message = 'Đã có lỗi xãy ra';
                    }
                }
                // Todo make comment
                if ($postId) {
                    $commentId = (new GDelivery\Libs\Helper\Complain)->addComment(
                        $postId,
                        $userId,
                        [
                            'role' => 'customer',
                            'email' => $customer->user_email,
                            'comment' => $params['note'],
                            'commentType' => $params['commentType'] ?? 'comment',
                            'commentMeta' => [
                                'title' => $params['errorType'] ?? ""
                            ]
                        ]
                    );
                    if ($commentId) {
                        $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
                        $res->message = 'Thành công';
                        $res->result = $postId;
                    } else {
                        $res->messageCode = \Abstraction\Object\ApiMessage::NOT_FOUND;
                        $res->message = 'Đã có lỗi xãy ra';
                    }
                }

            } else {
                $res->messageCode = \Abstraction\Object\ApiMessage::NOT_FOUND;
                $res->message = 'Không tìm thấy order';
            }
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::NOT_FOUND;
            $res->message = 'Vui lòng truyền đầy đủ params';
        }
        return $res;
    }

    public function getComplain(WP_REST_Request $request)
    {
        $orderId = $request['orderId'];

        $args = [
            'post_type' => 'complain',
            'showposts' => 999,
            'meta_query' => [
                [
                    'key'     => 'orderId',
                    'value'   => $orderId,
                    'compare' => '='
                ],
            ]
        ];

        $getComplain = new \WP_Query($args);

        $res = new Result();
        if ($getComplain->have_posts()) {
            $res->messageCode = \Abstraction\Object\ApiMessage::SUCCESS;
            $res->message = 'Thành công';
            $threads = [];
            foreach ($getComplain->posts as $post) {
                $threads[] = (new GDelivery\Libs\Helper\Complain)->getComplainInfo($post);
            }
            $res->result = $threads;
        } else {
            $res->messageCode = \Abstraction\Object\ApiMessage::NOT_FOUND;
            $res->message = 'Chưa có khiếu nại nào!';
        }
        return $res;
    }

    public function addComment(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $postId = $params['postId'];
        if (isset($params['orderId']) && $params['orderId']) {
            $orderId = $params['orderId'];
            $getOrder = wc_get_order($orderId);
            if ($getOrder) {
                $userId = $getOrder->get_customer_id();
                $customer = get_user_by('id', $userId);
                $commentId = (new GDelivery\Libs\Helper\Complain)->addComment(
                    $postId,
                    $userId,
                    [
                        'role' => 'customer',
                        'email' => $customer->user_email,
                        'comment' => $params['comment'],
                        'commentType' => $params['commentType'] ?? 'comment',
                        'commentMeta' => [
                            'title' => $params['title']
                        ]
                    ]
                );
                if ($commentId) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $postId;
                } else {
                    $res->messageCode = Message::NOT_FOUND;
                    $res->message = 'Đã có lỗi xãy ra';
                }
            }
        } else {
            $res->messageCode = Message::MISSING_PARAMS;
            $res->message = 'Vui lòng truyền orderId';
        }
        return $res;
    }

    public function updateComplain(WP_REST_Request $request)
    {
        $res = new Result();
        $id = $request['id'];
        $params = $request->get_params();
        $getComplain = get_post($id);
        if ($getComplain) {
            $doUpdate = wp_update_post(
                [
                    'ID' => $getComplain->ID,
                    'meta_input' => [
                        'issueType' => $params['issueType'],
                        'issue' => $params['issue'] ?? '',
                        'issueDetail' => $params['issueDetail'] ?? '',
                        'issueComment' => $params['issueComment'] ?? '',
                        'levelError' => $params['levelError'] ?? '',
                        'status' => \GDelivery\Libs\Helper\Complain::STATUS_PROCESSING,
                    ]
                ]
            );
            $complain = (new GDelivery\Libs\Helper\Complain)->getComplainInfo($getComplain);
            if ($doUpdate) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $complain;
            } else {
                $res->messageCode = Message::NOT_FOUND;
                $res->message = 'Đã có lỗi xãy ra';
            }
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tìm thấy post';
        }
        return $res;
    }

    public function resolveComplain(WP_REST_Request $request)
    {
        $res = new Result();
        $params = $request->get_params();
        $postId = $params['postId'];
        if (isset($params['orderId']) && $params['orderId']) {
            $orderId = $params['orderId'];
            $getOrder = wc_get_order($orderId);
            if ($getOrder) {
                $userId = $params['userId'];
                $customer = get_user_by('id', $userId);

                wp_update_post(
                    [
                        'ID' => $postId,
                        'meta_input' => [
                            'status' => \GDelivery\Libs\Helper\Complain::STATUS_PROCESSING,
                        ]
                    ]
                );

                $commentId = (new GDelivery\Libs\Helper\Complain)->addComment(
                    $postId,
                    $userId,
                    [
                        'role' => 'admin',
                        'email' => $customer->user_email,
                        'comment' => $params['comment'],
                        'commentType' => $params['commentType'] ?? 'comment',
                        'commentMeta' => [
                            'title' => $params['result']
                        ]
                    ]
                );
                if ($commentId) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $postId;
                } else {
                    $res->messageCode = Message::NOT_FOUND;
                    $res->message = 'Đã có lỗi xãy ra';
                }
            }
        } else {
            $res->messageCode = Message::MISSING_PARAMS;
            $res->message = 'Vui lòng truyền orderId';
        }
        return $res;
    }

    public function completeComplain(WP_REST_Request $request)
    {
        $res = new Result();
        $id = $request['postId'];
        $params = $request->get_params();
        $getComplain = get_post($id);
        if ($getComplain) {
            $doUpdate = wp_update_post(
                [
                    'ID' => $getComplain->ID,
                    'meta_input' => [
                        'resultComment' => $params['resultComment'] ?? '',
                        'commentPoint' => $params['commentPoint'] ?? '',
                        'status' => \GDelivery\Libs\Helper\Complain::STATUS_CLOSE,
                    ]
                ]
            );
            $complain = (new GDelivery\Libs\Helper\Complain)->getComplainInfo($getComplain);
            if ($doUpdate) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $complain;
            } else {
                $res->messageCode = Message::NOT_FOUND;
                $res->message = 'Đã có lỗi xãy ra';
            }
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tìm thấy post';
        }
        return $res;
    }
}

$complainApi = new ComplainApi();