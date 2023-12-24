<?php

namespace GDelivery\Libs\Helper;

use GGGMnASDK\Service\VnpaySms;
use GGGMnASDK\Service\VMGSms;
use GuzzleHttp\Client;
use GGGMnASDK\Abstraction\Object\Authentication;
use GDelivery\Libs\Config;
use GGGMnASDK\Service\Client as ServiceClient;
use GGGMnASDK\Abstraction\Object\SMS as ObjectSMS;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SMS
{
    /**
     * Helper send SMS
     *
     * @param ObjectSMS $objectMessage Object message: receiver, brandName, message content, ...
     * @param string $vendor Vendor send sms: VMG, VNPAY, ...
     */
    public static function send(ObjectSMS $objectMessage, $vendor)
    {
        $requestId = \uniqid();
        $startTime = microtime(true);

        $auth = new Authentication(
            [
                'apiBaseUrl' => Config::MESSAGE_SYSTEM_API_BASE_URL,
                'token' => Config::MESSAGE_SYSTEM_API_KEY
            ]
        );
        if (strtolower($vendor) == 'vmg') {
            // Sender vmg.
            $sender = new VMGSms(new Client(), $auth);
        } else {
            // Sender vnpay.
            $sender = new VnpaySms(new Client(), $auth);
        }

        // Init client
        $logger = new Logger('send-sms');
        $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/sms/sms-'.date('Y-m-d').'.log', Logger::DEBUG));
        $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        try {
            $logger->info("Request send SMS: RequestId: {$requestId}; Vendor: {$vendor}; Receiver: {$objectMessage->getReceiver()}; BrandName: {$objectMessage->getBrandName()}; MessageContent: {$objectMessage->getMessage()}");
            $client = new ServiceClient($sender, $objectMessage);
            $doSend = $client->sendMessage();

            $logger->info((microtime(true) - $startTime)."||||Response send sms; RequestId: {$requestId}; Response message: {$doSend->message}");
        } catch (Exception $e) {
            $logger->error((microtime(true) - $startTime)."||||Response send sms; RequestId: {$requestId}; Exception: {$e->getMessage()}");
        }
    }

}
