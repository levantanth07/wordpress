<?php 
/*
Template Name: Ajax Login
*/

// process login
$res = new \Abstraction\Object\Result();

try {
    if (\GDelivery\Libs\Helper\User::isLogin()) {
        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
        $res->message = 'Bạn đã đăng nhập';
    } else {
        if (isset($_POST['cellphone'], $_POST['otp'])) {
            $cellphone = \Abstraction\Core\Helper::correctCellphoneNumber($_POST['cellphone']);
            $otp = $_POST['otp'];

            $tgsService = new \GDelivery\Libs\TGSService();

            $doLogin = $tgsService->login($cellphone, $otp);

            if ($doLogin->messageCode == \Abstraction\Object\Message::SUCCESS) {
                \GDelivery\Libs\Helper\User::setIsLogin($doLogin->result->profile, $doLogin->result->authentication);

                // do wordpress login or register
                // check existing user first
                $existingUserId = username_exists($cellphone);
                if ($existingUserId) {
                    $userObj = get_user_by('id', $existingUserId);
                    // reset password with otp
                    reset_password($userObj, $otp);

                    // update email, firstName, lastName
                    wp_update_user(
                        [
                            'ID' => $existingUserId,
                            'first_name' => $doLogin->result->profile->firstName,
                            'last_name' => $doLogin->result->profile->lastName,
                            'user_email' => $doLogin->result->profile->email,
                            'role' => 'customer'
                        ]
                    );  // correct email address
                } else {
                    // create user
                    $user = array(
                        'user_login' => $cellphone,
                        'user_pass' => $otp,
                        'first_name' => $doLogin->result->profile->firstName,
                        'last_name' => $doLogin->result->profile->lastName,
                        'user_email' => $doLogin->result->profile->email,
                        'role' => 'customer'
                    );
                    $user_id = wp_insert_user( $user );
                }

                // wp login
                $credential = array();
                $credential['user_login'] = $cellphone;
                $credential['user_password'] = $otp;
                $user = wp_signon( $credential, true );

                // process cart if need
                // persistent carts created in WC 3.2+
                $blog_id = get_current_blog_id();
                if ( metadata_exists( 'user', $user->ID, '_woocommerce_persistent_cart_' . $blog_id ) ) {
                    delete_user_meta( $user->ID, '_woocommerce_persistent_cart_' . $blog_id );
                }
                unset(
                    $_SESSION['selectedAddress'],
                    $_SESSION['selectedRestaurant'],
                    $_SESSION['tempShippingFee'],
                    $_SESSION['selectedVouchers']
                );

                // after all, return success
                $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                $res->message = 'Đăng nhập thành công';
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = $doLogin->message;
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Bạn cần nhập số điện thoại hợp lệ';
        }
    }
} catch (\Exception $e) {
    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
    $res->message = 'Có lỗi ngoại lệ, E: '.$e->getMessage();
}

\GDelivery\Libs\Helper\Response::returnJson($res);