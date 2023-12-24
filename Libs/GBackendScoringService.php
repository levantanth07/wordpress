<?php

namespace GDelivery\Libs;

use Abstraction\Object\ApiMessage;
use Abstraction\Object\Message;
use Abstraction\Object\Result;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class GBackendScoringService
{
    /** @var Client */
    private $httpClient;

    /** @var Logger */
    private $logger;

    /** @var Logger */
    private $loggerScoringMerchant;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => Config::ECOMMERCE_BE_BASE_URL . 'services/',
        ]);
        $this->logger = new Logger('g-backend');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/g-backend/sync-scoring-product-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->loggerScoringMerchant = new Logger('g-backend');
        $this->loggerScoringMerchant->pushHandler(new StreamHandler(ABSPATH.'/logs/g-backend/sync-scoring-merchant-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->loggerScoringMerchant->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function addScoringProductsToQueueForSyncing($productIds)
    {
        $requestId = \uniqid();
        try {
            $endPoint = 'elasticsearch/product/scoring/queue';
            $productIds = is_array($productIds) ? $productIds : (array) $productIds;
            $this->logger->info("Request SYNC scoring product Elasticsearch: RequestId: {$requestId}; Endpoint: {$endPoint}; SYNC IDs: ". \json_encode($productIds));
            $queueRequest = $this->httpClient->request('POST', $endPoint, [
                RequestOptions::QUERY => [
                    'productIds' => $productIds,
                ],
                RequestOptions::VERIFY => false,
            ]);

            if ($queueRequest->getStatusCode() !== ApiMessage::SUCCESS) {
                $this->logger->error("RESPONSE FAILED SYNC scoring product Elasticsearch: RequestId: $requestId; Endpoint: $endPoint;");
                return;
            }

            $content = $queueRequest->getBody()->getContents();
            $jsonContent = json_decode($content);

            if (! $jsonContent) {
                $this->logger->error("RESPONSE FAILED to call parse json response: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
                return;
            }

            if ($jsonContent->messageCode !== Message::SUCCESS) {
                $this->logger->error("RESPONSE FAILED SYNC scoring product Elasticsearch: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
                return;
            }

            $this->logger->info("RESPONSE SUCCESS SYNC scoring product Elasticsearch: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
        } catch (\Throwable $e) {
            $this->logger->error("RESPONSE FAILED SYNC scoring product Elasticsearch: RequestId: $requestId; RESPONSE: {$e->getMessage()};");
        }
    }

    public function addScoringMerchantsToQueueForScoring($merchantIds)
    {
        $requestId = \uniqid();
        try {
            $endPoint = 'elasticsearch/merchant/scoring/queue';
            $merchantIds = is_array($merchantIds) ? $merchantIds : (array) $merchantIds;
            $this->loggerScoringMerchant->info("Request SYNC scoring merchant Elasticsearch: RequestId: {$requestId}; Endpoint: {$endPoint}; SYNC IDs: ". \json_encode($merchantIds));
            $queueRequest = $this->httpClient->request('POST', $endPoint, [
                RequestOptions::QUERY => [
                    'merchantIds' => $merchantIds,
                ],
                RequestOptions::VERIFY => false,
            ]);

            if ($queueRequest->getStatusCode() !== ApiMessage::SUCCESS) {
                $this->loggerScoringMerchant->error("RESPONSE FAILED SYNC scoring merchant Elasticsearch: RequestId: $requestId; Endpoint: $endPoint;");
                return;
            }

            $content = $queueRequest->getBody()->getContents();
            $jsonContent = json_decode($content);

            if (! $jsonContent) {
                $this->loggerScoringMerchant->error("RESPONSE FAILED to call parse json response: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
                return;
            }

            if ($jsonContent->messageCode !== Message::SUCCESS) {
                $this->loggerScoringMerchant->error("RESPONSE FAILED SYNC scoring merchant Elasticsearch: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
                return;
            }

            $this->loggerScoringMerchant->info("RESPONSE SUCCESS SYNC scoring merchant Elasticsearch: RequestId: $requestId; Endpoint: $endPoint; RESPONSE: $content;");
        } catch (\Throwable $e) {
            $this->loggerScoringMerchant->error("RESPONSE FAILED SYNC scoring merchant Elasticsearch: RequestId: $requestId; RESPONSE: {$e->getMessage()};");
        }
    }

    /**
     * @param $tags
     * @return Result
     */
    public function clearRedisCacheByTags($tags)
    {
        $result = new Result();
        $requestId = \uniqid();
        try {
            $httpClearCacheRequest = $this->httpClient->request(
                'get',
                'clear-cache',
                [
                    RequestOptions::QUERY => [
                        'tags' => $tags,
                    ],
                    RequestOptions::TIMEOUT => 30,
                ]
            );

            if ($httpClearCacheRequest->getStatusCode() != ApiMessage::SUCCESS) {
                $result->messageCode = Message::GENERAL_ERROR;
                $result->message = "Fail to call GPos: Status: {$httpClearCacheRequest->getStatusCode()}";
                $this->logger->error("RESPONSE FAILED Clear Redis Cache: RequestId: $requestId; Status: {$httpClearCacheRequest->getStatusCode()};");
                return $result;
            }

            $response = $httpClearCacheRequest->getBody()->getContents();
            $jsonResponse = \json_decode($response);
            if (! $jsonResponse) {
                $result->messageCode = Message::GENERAL_ERROR;
                $result->message = "Fail to call parse json response: $response";
                $this->logger->error("RESPONSE FAILED Clear Redis Cache: RequestId: $requestId; Response: Fail to call parse json response: $response;");
                return $result;
            }

            if ($jsonResponse->messageCode != Message::SUCCESS) {
                $result->messageCode = Message::GENERAL_ERROR;
                $result->message = $jsonResponse->message;
                $this->logger->error("RESPONSE FAILED Clear Redis Cache: RequestId: $requestId; Response: $jsonResponse->message;");
                return $result;
            }

            $result->result = $jsonResponse->result;
            $result->messageCode = Message::SUCCESS;
            $result->message = 'Thành công';
            $this->logger->info("RESPONSE SUCCESS Clear Redis Cache: RequestId: $requestId;");
            return $result;

        } catch (GuzzleException $guzzleException) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = "Fail to call G-Backend. Guzzle: {$guzzleException->getMessage()}";
            $this->logger->error("RESPONSE FAILED Clear Redis Cache: RequestId: $requestId; Exception: {$guzzleException->getMessage()};");
            return $result;
        } catch (\Exception $e) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = "Fail to call G-Backend: Exception: {$e->getMessage()}";
            $this->logger->error("RESPONSE FAILED Clear Redis Cache: RequestId: $requestId; Exception: {$e->getMessage()};");
            return $result;
        }
    }
}
