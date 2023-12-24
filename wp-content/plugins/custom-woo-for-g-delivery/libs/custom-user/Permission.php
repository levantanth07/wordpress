<?php
class Permission {
    const ROLE_ADMIN = 1;
    const ROLE_CUSTOMER = 2;
    const ROLE_RESTAURANT = 3;
    const ROLE_OPERATOR = 4;
    const ROLE_IT = 5;
    const ROLE_MARKETING = 6;
    const ROLE_AM = 7;

    public static $roles = [
        self::ROLE_ADMIN => 'administrator',
        self::ROLE_CUSTOMER => 'customer',
        self::ROLE_RESTAURANT => 'restaurant',
        self::ROLE_OPERATOR => 'operator',
        self::ROLE_IT => 'it',
        self::ROLE_MARKETING => 'marketing',
        self::ROLE_AM => 'am',
    ];

    public static function checkCurrentUserRole($currentUser = null)
    {
        if ($currentUser === null) {
            $currentUser = wp_get_current_user();
        }

        $user = new \stdClass();
        $bookingService = new \GDelivery\Libs\BookingService();
        if (in_array('administrator', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_ADMIN];
        } elseif (in_array('shop_manager', $currentUser->roles)) {
            $user->role = 'shop_manager';
        } elseif(in_array('restaurant', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_RESTAURANT];

            $restaurantCode = $currentUser->get('user_restaurant');
            $getRestaurant = $bookingService->getRestaurant($restaurantCode);
            if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $user->restaurant = $getRestaurant->result;
            }
        } elseif(in_array('operator', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_OPERATOR];
            $user->rights = $currentUser->get('user_operator_rights');
        } elseif(in_array('am', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_AM];
            $user->rights = $currentUser->get('user_operator_rights');
        } elseif(in_array('it', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_IT];
            $user->rights = $currentUser->get('user_operator_rights');
        } elseif(in_array('marketing', $currentUser->roles)) {
            $user->role = self::$roles[self::ROLE_MARKETING];
            $user->rights = $currentUser->get('user_operator_rights');
        } elseif (in_array('customer', $currentUser->roles)) {
            $user->role = 'customer';
        } else {
            $user->role = isset($currentUser->roles[0]) ? $currentUser->roles[0] : '';
        }

        return $user;
    }

}