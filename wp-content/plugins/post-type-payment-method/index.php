<?php
/*
Plugin Name: Post Type Payment Method
Plugin URI: http://ggg.com.vn/
Description: Manage Payment Method.
Author: hoang.daohuy <hoang.daohuy@ggg.com.vn>
Version: 1.0
*/

use Abstraction\Object\Result;
use Abstraction\Object\ApiMessage;

class PaymentMethodPostType {

    /**
     * @param WP_Post $post
     *
     * @return stdClass
     */
    private function convertToStdClass($post)
    {
        $temp = new \stdClass();

        $temp->id = $post->ID;
        $temp->title = html_entity_decode($post->post_title);
        $temp->status = $post->post_status == 'publish';
        $temp->paymentMethodCode = get_field('payment_method_code', $post->ID);
        $temp->logo = get_field('payment_method_logo', $post->ID);
        $temp->type = (int) get_field('payment_method_type', $post->ID);

        return $temp;
    }

    public function __construct()
    {
        // register payment method post type
        add_action( 'init', [$this, 'registerPostType'], 0 );

        // register rest api
        add_action( 'rest_api_init', function () {
            register_rest_route(
                'api/v1',
                '/payment-method',
                [
                    'methods' => 'GET',
                    'callback' => [$this, "getPaymentMethod"],
                    'permission_callback' => function() {
                        return '';
                    }
                ]
            );
        } );

    }

    public function registerPostType()
    {
        $post_type = 'payment_methods';
        $args = [
            'label' => 'Phương thức thanh toán',
            'labels' => [
                'name' => 'Phương thức thanh toán',
                'singular_name' => 'Phương thức thanh toán',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Phương thức thanh toán',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-money-alt',
            'rewrite' => ['slug' => 'payment_methods'],
            'supports' => ['title'],
            'exclude_from_search' => false,
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type( $post_type, $args );
    }

    // add api
    public function getPaymentMethod( WP_REST_Request $request )
    {
        $res = new Result();
        if ($request['isActive']) {
            $params['isActive'] = $request['isActive'];
        } else {
            $params['isActive'] = false;
        }
        $getListPaymentMethod = \GDelivery\Libs\Helper\PaymentMethod::getListPaymentMethod($params);

        // The Loop
        $listPaymentMethod = [];
        if ($getListPaymentMethod) {
            foreach ($getListPaymentMethod as $post) {
                $listPaymentMethod[] = $this->convertToStdClass($post);
            }
        }

        $res->messageCode = ApiMessage::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $listPaymentMethod;

        return $res;
    } // end get payment method

} // end class

// init
$paymentMethod = new PaymentMethodPostType();
