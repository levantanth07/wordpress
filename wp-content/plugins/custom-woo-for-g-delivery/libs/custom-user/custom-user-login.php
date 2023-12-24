<?php
////////////////////////////////////////////////////////////////////////////////
///   custom login
/// ///////////////////////////////////////////////////////////////////////////

// redirect to homepage after login
function redirectAdminPage()
{
    $user = Permission::checkCurrentUserRole();

    if (!str_contains($_SERVER['REQUEST_URI'], 'admin-ajax.php')) {
        if ($user->role == 'restaurant') {
            wp_redirect(site_url('restaurant-list-orders-v2'));
        } elseif (
            $user->role == 'operator'
            || $user->role == 'am'
            || $user->role == 'acc'
        ) {
            wp_redirect(site_url('operator-list-orders'));
        } elseif (
            $user->role == 'administrator'
            || $user->role == 'shop_manager'
            || $user->role == 'it'
            || $user->role == 'marketing'
        ) {

        } else {
            wp_redirect(site_url());
        }
    }

}
add_filter('admin_init', 'redirectAdminPage');

function autoLoginTGS()
{
   if (isset($_REQUEST['token'], $_REQUEST['customerNumber'])) {
       $currentUrl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
       $currentUrl = str_replace('?'.$_SERVER['QUERY_STRING'], '', $currentUrl);

       // parse query uri and remove name
       $arrParams = [];
       parse_str($_SERVER['QUERY_STRING'], $arrParams);

       unset($arrParams['name']); // name is preserved by wordpress
       // token and customerNumber to prevent loop here
       unset($arrParams['token']);
       unset($arrParams['customerNumber']);

       $token = $_REQUEST['token'];
       $customerNumber = $_REQUEST['customerNumber'];
       $provinceId = isset($_REQUEST['provinceId']) ?? null;

       // process province
       if ($provinceId) {
           $bookingService = new \GDelivery\Libs\BookingService();
           $getProvince = $bookingService->getProvince($provinceId);
           if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
               \GDelivery\Libs\Helper\Helper::setSelectedProvince($getProvince->result);
           }
       }

       // get profile info
       $tgsService = new \GDelivery\Libs\TGSService();
       $getProfile = $tgsService->getProfileInfo($customerNumber, $token);
       if ($getProfile->messageCode == \Abstraction\Object\Message::SUCCESS) {
           $profile = $getProfile->result;
           $otp = \uniqid();
           $cellphone = $profile->cellphone;
           // auth class
           $auth = new \stdClass();
           $auth->token = $token;
           $auth->customerNumber = $customerNumber;
           \GDelivery\Libs\Helper\User::setIsLogin($profile, $auth);

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
                       'first_name' => $profile->firstName,
                       'last_name' => $profile->lastName,
                       'user_email' => $profile->email,
                       'role' => 'customer'
                   ]
               );  // correct email address
           } else {
               // create user
               $user = array(
                   'user_login' => $cellphone,
                   'user_pass' => $otp,
                   'first_name' => $profile->firstName,
                   'last_name' => $profile->lastName,
                   'user_email' => $profile->email,
                   'role' => 'customer'
               );
               $user_id = wp_insert_user($user);
           }

           // wp login
           $credential = array();
           $credential['user_login'] = $cellphone;
           $credential['user_password'] = $otp;
           //$credential['rememberme'] = true;
           $user = wp_signon( $credential, true );
       }

       $currentUrl .= '?'.http_build_query($arrParams);

       echo '<meta http-equiv="refresh" content="0; url='.$currentUrl.'" />';
       die;
   }

}
add_filter('init', 'autoLoginTGS');