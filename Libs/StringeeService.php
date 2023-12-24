<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class StringeeService {

    private $httpClient;

    public function __construct()
    {
        // Create httpClient
        $this->httpClient = new Client(
            [
                'base_uri' => Config::MESSAGE_SYSTEM_API_BASE_URL,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.Config::MESSAGE_SYSTEM_API_KEY,
                ]
            ]
        );

    }

    public function makeACall($data)
    {
        $res = new Result();

        // uri API
        $endPoint = "call/stringee/make-a-call";

        try {
            $params['type'] = $data['type'];
            $params['messageId'] = $data['messageId'];
            $params['number'] = $data['number'];
            $params['content'] = $data['content'];
            $params['action'] = $data['action'];

            // Run httpClient API
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $params
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody()->getContents());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api stringee: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }
}