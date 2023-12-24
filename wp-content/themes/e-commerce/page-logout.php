<?php
/*
Template Name: Logout
*/

// check current user permission
$user = Permission::checkCurrentUserRole();

// do logout
\GDelivery\Libs\Helper\User::logout();

if ($user->role == 'restaurant' || $user->role == 'operator') {
    header('Location: '.site_url('wp-login.php'));
} else {
    header('Location: '.site_url());
}
