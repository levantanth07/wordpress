<?php
namespace GDelivery\Libs\Helper;

use GDelivery\Libs\Config;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Mail {
    public static function send($order)
    {

        $logger = new Logger('send-mail');
        $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/send-mail/send-mail-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));

        $getOrder = wc_get_order($order->get_id());
        $jsonRestaurant = $getOrder->get_meta('restaurant_in_tgs');
        $multiEmail = [];
        $provinceBrand = trim($getOrder->get_meta('province_brand'));
        $args = array (
            'meta_key' => 'user_operator_province_brand',
            'meta_value' => '"'.$provinceBrand.'"',
            'meta_compare' => 'LIKE' // everything but the exact match
        );

        $users = new \WP_User_Query( $args );
        /** @var \WP_User $user */
        foreach ($users->get_results() as $user) {
            // check role
            $userPermission = \Permission::checkCurrentUserRole($user);
            if (in_array($userPermission->role, ['am', 'operator'])) {
                array_push($multiEmail, $user->data->user_email);
            }
        }

        if ($jsonRestaurant->restaurant->email) {
            array_push($multiEmail, $jsonRestaurant->restaurant->email);
        }

        if (Config::CO_RECEIVING_EMAIL_ORDER) {
            array_push($multiEmail, Config::CO_RECEIVING_EMAIL_ORDER);
        }

        $selectedVouchers = $getOrder->get_meta('selected_vouchers');
        $totalDiscount = 0;
        $totalCashVoucher = 0;
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $selectedVoucher) {
                if ($selectedVoucher->type == 1) {
                    $totalCashVoucher += $selectedVoucher->denominationValue;
                } else {
                    $totalDiscount += $selectedVoucher->denominationValue;
                }
            }
        }

        // send mail restaurant setup
        require_once ABSPATH.'/wp-content/plugins/custom-api-for-g-delivery/order/email-new-order.php';
        if ($jsonRestaurant->allowEmailNewOrder) {
            $mail['to'] = $multiEmail;
            $mail['subject'] = 'Golden SpoonS thông báo đã có đơn hàng '.$getOrder->get_id().' được tạo';
            /** @var string $strMessage get this variable from require */
            $mail['message'] = $strMessage;
            $mail['headers'] = array('Content-Type: text/html; charset=UTF-8');
            $emailService = new \GDelivery\Libs\EmailService();
            $emailService->send($mail);
        }
    }

    public static function sendMailDownload($email, $subject = null, $link = null)
    {

        $logger = new Logger('send-mail');
        $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/send-mail/send-mail-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));

        if ($link) {
            $tpl = file_get_contents(ABSPATH . '/wp-content/themes/gdelivery-v2/content/email/download.html');
            $tpl = str_replace('{{downloadLink}}', $link, $tpl);
        } else {
            $tpl = file_get_contents(ABSPATH . '/wp-content/themes/gdelivery-v2/content/email/emptyResult.html');
        }

        $static = get_bloginfo('template_url');
        $logo = $static."/assets/v2.3/desktop/images/logo.svg";
        $tpl = str_replace('{{logo}}', $logo, $tpl);

        $domain = site_url();
        $tpl = str_replace('{{domain}}', $domain, $tpl);

        $multiEmail = [
            $email,
            \GDelivery\Libs\Config::DOWNLOAD_CC_EMAIL
        ];

        $mail['to'] = $multiEmail;
        $mail['subject'] = $subject ?: 'Kết quả yêu cầu download report order';
        /** @var string $strMessage get this variable from require */
        $mail['message'] = $tpl;
        $mail['headers'] = array('Content-Type: text/html; charset=UTF-8');

        $logger->info("Request Send Email Download  DebugInfo: ".\json_encode($mail));

        $emailService = new \GDelivery\Libs\EmailService();
        $doSend = $emailService->send($mail);
        $logger->info("Response Send Email Download  DebugInfo: ".\json_encode($doSend));

        return $doSend;
    }

} // end class
