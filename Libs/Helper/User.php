<?php
namespace GDelivery\Libs\Helper;

class User {

    const USER_ROLE_RESTAURANT = 'restaurant';
    const USER_ROLE_CS = 'cs';

    public static function isLogin()
    {
        // todo process read cookie to check remember login
        if (
            isset($_SESSION['isLogin'], $_SESSION['customerInfo'], $_SESSION['customerAuthentication'], $_SESSION['customerAuthentication']->token)
            && $_SESSION['isLogin'] && $_SESSION['customerInfo'] && $_SESSION['customerAuthentication'] && $_SESSION['customerAuthentication']->token
            && is_user_logged_in()
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function currentCustomerInfo()
    {
        if (
            isset($_SESSION['customerInfo'], $_SESSION['customerAuthentication'])
            && $_SESSION['customerInfo'] && $_SESSION['customerAuthentication']) {
            $temp = new \stdClass();
            $temp->customerInfo = $_SESSION['customerInfo'];
            $temp->customerAuthentication = $_SESSION['customerAuthentication'];
        } else {
            $temp = null;
        }

        return $temp;
    }

    public static function setIsLogin($clmProfile, $tgsAuth)
    {
        $_SESSION['isLogin'] = true;
        $_SESSION['customerInfo'] = $clmProfile;
        $_SESSION['customerAuthentication'] = $tgsAuth;

        // todo set cookie when remember login
    }

    public static function logout()
    {
        unset($_SESSION['isLogin'], $_SESSION['customerInfo'], $_SESSION['customerAuthentication']);

        wp_logout();

        // todo remove remember login cookie
    }

    public static function randomPassword($length = 8) {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $password = []; //remember to declare $pass as an array
        $alphaLength = strlen($str) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $password[] = $str[$n];
        }
        return implode($password); //turn the array into a string
    }

} // end class
