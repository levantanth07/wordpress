<?php

namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class TgsServiceV2 {

    /**
     * @var
     */
    private $httpClient;

    /**
     * @var
     */
    private $logger;

    public function __construct()
    {
        $this->httpClient = new Client(
            [
                'base_uri' => \GDelivery\Libs\Config::TGS_API_V2_BASE_URL,
                'headers' => [
                    'Authorization' => 'Bearer '.\GDelivery\Libs\Config::TGS_API_V2_KEY
                ]
            ]
        );

        $this->logger = new Logger('tgs');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function sendNotify($params)
    {
        $startTime = microtime(true);
        $requestId = uniqid();
        $res = new Result();
        try {
            $requestPayment = $this->httpClient->request(
                'POST',
                'notify-partners/ecommerce',
                [
                    \GuzzleHttp\RequestOptions::JSON => $params,
                    RequestOptions::TIMEOUT => 3
                ]
            );

            $jsonRes = \json_decode($requestPayment->getBody());
            if ($jsonRes) {
                if ($jsonRes->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;

                }
                $res->message = $jsonRes->message;
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi xử lý dữ liệu gửi noti.';
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi xử lý dữ liệu gửi noti. Guzzle Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi xử lý dữ liệu gửi noti. Exception: '.$e->getMessage();
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/tgs/send-notify-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request send notify, RequestId: {$requestId}; SendData: ".\json_encode($params)."; RESPONSE: ".\json_encode($res));

        return $res;
    }
}