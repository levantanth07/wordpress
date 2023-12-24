<?php

namespace GDelivery\Libs\Helper;
use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\PaymentHubService;

class Complain
{

    const STATUS_PENDING = "pending";
    const STATUS_PROCESSING = "processing";
    const STATUS_CLOSE = "close";

    static public $arrStatus = [
        self::STATUS_PENDING => 'Chưa xử lý',
        self::STATUS_PROCESSING => 'Đang xử lý',
        self::STATUS_CLOSE => 'Đã xử lý',
    ];

    public function getComplainInfo($post, $params = [])
    {
        $complain = new \stdClass();
        $complain->id = $post->ID;
        $complain->name = $post->post_title;
        $complain->orderId = get_post_meta($post->ID, 'orderId', true);

        if (isset($params['with']) && str_contains($params['with'], 'order')) {
            $getOrder = wc_get_order($complain->orderId);
            $order = new \stdClass();

            $createdAt = new \DateTime($getOrder->get_date_created());
            $createdAt->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
            $order->createdAt = $createdAt->format('Y-m-d H:i:s');

            $updatedAt = new \DateTime($getOrder->get_date_modified());
            $updatedAt->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
            $order->updatedAt = $updatedAt->format('Y-m-d H:i:s');
            $order->orderGuid = $getOrder->get_meta('rkOrder') ? $getOrder->get_meta('rkOrder')->guid : '';
            if ($order->orderGuid) {
                $paymentService = new \GDelivery\Libs\PaymentHubService();
                $getRating = $paymentService->listRating($order->orderGuid);
                if ($getRating->messageCode == Message::SUCCESS) {
                    $order->rating = $getRating->result->ratings[0] ?? null;
                }
            }
            $complain->order = $order;

            /*$merchantId = $getOrder->get_meta('merchant_id');
            $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchant($merchantId, ['post_status' => true]);
            if ($getMerchant->messageCode == Message::SUCCESS) {
                $complain->merchant = $getMerchant->result;
            }*/
        }

        $complain->customerName = get_post_meta($post->ID, 'customerName', true);
        $complain->customerPhone = get_post_meta($post->ID, 'customerPhone', true);
        $complain->restaurantCode = get_post_meta($post->ID, 'restaurantCode', true);

        if (isset($params['with']) && str_contains($params['with'], 'restaurant')) {
            $restaurantCode = get_post_meta($post->ID, 'restaurantCode', true);
            $getMerchant = \GDelivery\Libs\Helper\Helper::getMerchantByCode($restaurantCode, ['post_status' => true]);
            if ($getMerchant->messageCode == Message::SUCCESS) {
                $complain->merchant = $getMerchant->result;
            }
        }

        $complain->errorType = get_post_meta($post->ID, 'errorType', true);
        $complain->note = get_post_meta($post->ID, 'note', true);
        $complain->status = get_post_meta($post->ID, 'status', true);
        $complain->source = get_post_meta($post->ID, 'source', true);
        $complain->statusText = self::$arrStatus[$complain->status];
        $complain->adminErrorType = get_post_meta($post->ID, 'adminErrorType', true);
        $complain->createdAt = $post->post_date;
        $complain->updatedAt = $post->post_modified_gmt;

        $classify = new \stdClass();
        $classify->issueType = get_post_meta($post->ID, 'issueType', true);
        $classify->issue = get_post_meta($post->ID, 'issue', true);
        $classify->issueDetail = get_post_meta($post->ID, 'issueDetail', true);
        $classify->comment = get_post_meta($post->ID, 'issueComment', true);
        $classify->level = get_post_meta($post->ID, 'levelError', true);
        $complain->classify = $classify;

        $response = new \stdClass();
        $response->comment = get_post_meta($post->ID, 'resultComment', true);
        $response->point = get_post_meta($post->ID, 'commentPoint', true);
        $complain->response = $response;

        $getComments = get_comments(
            [
                'post_id' => $post->ID
            ]
        );
        $comments = [];
        if ($getComments) {
            foreach ($getComments as $comment) {
                $cmt = new \stdClass();
                $cmt->author = $comment->comment_author;
                $cmt->authorEmail = $comment->comment_author_email;
                $cmt->date = $comment->comment_date;
                $cmt->content = $comment->comment_content;
                $cmt->commentType = $comment->comment_type;
                $cmt->contentMeta = get_comment_meta($comment->comment_ID);
                $getUser = get_user_by('id', $comment->user_id);
                $user = new \stdClass();
                $user->displayName = $cmt->author == 'admin' ? $getUser->display_name : $complain->customerName;
                $cmt->user = $user;
                $comments[] = $cmt;
            }
        }
        $complain->comments = $comments;
        return $complain;
    }

    public function addComment($postId, $userId, $params = [])
    {
        $commentId = wp_insert_comment(
            [
                'comment_post_ID' => $postId,
                'comment_author' => $params['role'],
                'comment_author_email' => $params['email'],
                'comment_author_url' => $params['url'] ?? '',
                'comment_content' => $params['comment'],
                'user_id' => $userId,
                'comment_date' => current_time('mysql'),
                'comment_approved' => 1,
                'comment_type' => $params['commentType'],
                'comment_meta' => (isset($params['commentMeta']) && !empty($params['commentMeta'])) ? $params['commentMeta'] : []
            ]
        );
        return $commentId;
    }

    public function getComplain($params = [])
    {
        $args = [
            'post_type' => 'complain',
            'showposts' => 999
        ];
        $metaQuery = [];
        if (isset($params['orderId']) && $params['orderId']) {
            $metaQuery[] = [
                'key'     => 'orderId',
                'value'   => $params['orderId'],
                'compare' => '='
            ];
        }
        if (isset($params['status']) && $params['status']) {
            $metaQuery[] = [
                'key'     => 'status',
                'value'   => $params['status'],
                'compare' => '='
            ];
        }
        $args['meta_query'] = $metaQuery;
        $getComplains = new \WP_Query($args);

        $res = new Result();
        if ($getComplains->have_posts()) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $complains = [];
            foreach ($getComplains->posts as $item) {
                $complains[] = self::getComplainInfo($item);
            }
            $res->result = $complains;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Chưa có khiếu nại nào!';
        }
        return $res;
    }

}