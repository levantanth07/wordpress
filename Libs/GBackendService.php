<?php

namespace GDelivery\Libs;

use Abstraction\Object\ApiMessage;
use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class GBackendService
{
    private $httpClient;

    private $logger;

    /**
     * @var Logger
     */
    private $loggerNotify;

    public function __construct()
    {
        $this->httpClient = new Client(
            [
                'base_uri' => Config::ECOMMERCE_BE_BASE_URL
            ]
        );
        $this->logger = new Logger('g-backend');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/g-backend/sync-product-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $this->loggerNotify = new Logger('g-notify');
        $this->loggerNotify->pushHandler(new StreamHandler(ABSPATH.'/logs/g-notify/notification-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
    }

    public function syncElasticSearchProduct($id)
    {
        $res = new Result();
        $requestId = \uniqid('', false);
        $startTimeRequest = microtime(true);

        try {

            $endPoint = "product/{$id}/sync";
            $this->logger->info("Request SYNC product Elasticsearch: RequestId: {$requestId}; Endpoint: {$endPoint}; SYNC ID: ".\json_encode($id));

            $doSync = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::TIMEOUT => 30
                ]
            );
            $strResponse = $doSync->getBody()->getContents();
            $this->logger->info("RESPONSE SYNC product Elasticsearch: RequestId: {$requestId}; RESPONSE: {$strResponse};");

            if ($doSync->getStatusCode() == ApiMessage::SUCCESS) {
                $jsonRes = \json_decode($strResponse);
                if ($jsonRes) {
                    if ($jsonRes->messageCode == ApiMessage::SUCCESS) {
                        $res->result = $jsonRes->result;
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'success';
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call parse json response: '.$strResponse;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call GPos: Status: '.$doSync->getStatusCode();
            }

        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Fail to call G-Backend: Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function clearRedisCache($tags)
    {
        $res = new Result();
        try {

            $endPoint = "services/clear-cache";

            $doCache = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::QUERY => [
                        'tags' => $tags,
                    ],
                    RequestOptions::TIMEOUT => 30
                ]
            );
            $strResponse = $doCache->getBody()->getContents();

            if ($doCache->getStatusCode() == ApiMessage::SUCCESS) {
                $jsonRes = \json_decode($strResponse);
                if ($jsonRes) {
                    if ($jsonRes->messageCode == ApiMessage::SUCCESS) {
                        $res->result = $jsonRes->result;
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'success';
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call parse json response: '.$strResponse;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call GPos: Status: '.$doCache->getStatusCode();
            }

        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Fail to call G-Backend: Exception: '.$e->getMessage();
        }
        return $res;
    }

    public function sendNotify($params = [])
    {
        $res = new Result();
        $requestId = \uniqid('', false);

        try {

            $endPoint = "partner/notification";
            $this->loggerNotify->info("Request SEND notification RequestId: {$requestId}; DATA: ". \json_encode($params));

            $doSend = $this->httpClient->request(
                'POST',
                $endPoint,
                [
                    RequestOptions::JSON => $params,
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer ' . Config::ECOMMERCE_BE_KEY
                    ],
                    RequestOptions::TIMEOUT => 30
                ]
            );
            $strResponse = $doSend->getBody()->getContents();
            $this->loggerNotify->info("RESPONSE SEND notification: RequestId: {$requestId}; RESPONSE: {$strResponse};");

            if ($doSend->getStatusCode() == ApiMessage::SUCCESS) {
                $jsonRes = \json_decode($strResponse);
                if ($jsonRes) {
                    if ($jsonRes->messageCode == ApiMessage::SUCCESS) {
                        $res->result = $jsonRes->result;
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'success';
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->mess;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call parse json response: '.$strResponse;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call GPos: Status: '.$doSend->getStatusCode();
            }

        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Fail to call G-Backend: Exception: '.$e->getMessage();
        }
        $this->loggerNotify->info("RESULT SEND notification: RequestId: {$requestId}; RESPONSE: ". \json_encode($res));
        return $res;
    }

    public function dispatchHandleTransRejectedOrder($orderId)
    {
        $res = new Result();
        try {
            $endPoint = "services/process-trans-rejected-order";
            $doSync = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => ['orderId' => $orderId],
                    RequestOptions::TIMEOUT => 30
                ]
            );
            $strResponse = $doSync->getBody()->getContents();
            if ($doSync->getStatusCode() == ApiMessage::SUCCESS) {
                $jsonRes = \json_decode($strResponse);
                if ($jsonRes) {
                    if ($jsonRes->messageCode == ApiMessage::SUCCESS) {
                        $res->result = $jsonRes->result;
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'success';
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call parse json response: '.$strResponse;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call dispatchHandleTransRejectedOrder: Status: '.$doSync->getStatusCode();
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Fail to call G-Backend: Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function cancelUtilize($voucherCode)
    {
        $res = new Result();
        try {
            $endPoint = "services/voucher/cancel-utilize";
            $doSync = $this->httpClient->request(
                'POST',
                $endPoint,
                [
                    RequestOptions::JSON => [
                        'voucherCode' => $voucherCode
                    ],
                    RequestOptions::TIMEOUT => 30
                ]
            );
            $strResponse = $doSync->getBody()->getContents();
            if ($doSync->getStatusCode() == ApiMessage::SUCCESS) {
                $jsonRes = \json_decode($strResponse);
                if ($jsonRes) {
                    if ($jsonRes->messageCode == ApiMessage::SUCCESS) {
                        $res->result = $jsonRes->result;
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'success';
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call parse json response: '.$strResponse;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call clear voucher: Status: '.$doSync->getStatusCode();
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Fail to call G-Backend: Exception: '.$e->getMessage();
        }

        return $res;
    }
}