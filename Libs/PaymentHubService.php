<?php
namespace GDelivery\Libs;

use Abstraction\Object\ApiMessage;
use Abstraction\Object\GBiz\HoldBalance;
use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class PaymentHubService {

    private $httpClient;
    private $logger;

    public function __construct()
    {
        $this->httpClient = new Client(
            [
                'base_uri' => \GDelivery\Libs\Config::PAYMENT_HUB_API_BASE_URL,
                'headers' => [
                    'Authorization' => 'Bearer '.\GDelivery\Libs\Config::PAYMENT_HUB_API_KEY
                ]
            ]
        );

        $this->logger = new Logger('payment-hub');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function updateRequestPayment($requestPaymentId, $orderGuid)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $sendData = [
            "restaurantCode" => 99002,
            "requestId" => $requestPaymentId,
            "orderId" => $orderGuid
        ];

        $res = new Result();
        try {
            $requestPayment = $this->httpClient->request(
                'post',
                'gpos/update-payment',
                [
                    \GuzzleHttp\RequestOptions::JSON => $sendData,
                    RequestOptions::TIMEOUT => 3
                ]
            );

            $jsonRes = \json_decode($requestPayment->getBody());
            if ($jsonRes) {
                if ($jsonRes->messageCode == \Abstraction\Object\Message::SUCCESS) {

                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;

                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi xử lý dữ liệu update giao dịch.';
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi cập nhật yêu cầu thanh toán. Guzzle Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi cập nhật yêu cầu kiểm tra. Exception: '.$e->getMessage();
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/update-request-payment-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request update Request payment, RequestId: {$requestId}; SendData: ".\json_encode($sendData)."; RESPONSE: ".\json_encode($res));

        return $res;
    }

    public function checkPayment($requestPaymentId)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $sendData = [
            "restaurantCode" => 99002,
            "requestId" => $requestPaymentId
        ];

        $res = new Result();
        try {
            $requestPayment = $this->httpClient->request(
                'post',
                'gpos/check-payment',
                [
                    \GuzzleHttp\RequestOptions::JSON => $sendData
                ]
            );

            $jsonRes = \json_decode($requestPayment->getBody());
            if ($jsonRes) {
                if ($jsonRes->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $temp = [
                        'requestId' => $jsonRes->result->id,
                        'partner' => [
                            'logo' => $jsonRes->result->partner->logo,
                            'name' => $jsonRes->result->partner->name,
                        ],
                        'partnerPaymentTime' => $jsonRes->result->partnerPaymentTime,
                        'ggRequestId' => $jsonRes->result->ggRequestId,
                        'partnerTransactionId' => $jsonRes->result->partnerTransactionId,
                        'status' => $jsonRes->result->status,
                        'amount' => $jsonRes->result->amount
                    ];

                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $temp;
                } else {
                    if ($jsonRes->result) {
                        $temp = [
                            'requestId' => $jsonRes->result->id,
                            'partner' => [
                                'logo' => $jsonRes->result->partner->logo,
                                'name' => $jsonRes->result->partner->name,
                            ],
                            'partnerPaymentTime' => $jsonRes->result->partnerPaymentTime,
                            'ggRequestId' => $jsonRes->result->ggRequestId,
                            'partnerTransactionId' => $jsonRes->result->partnerTransactionId,
                            'status' => $jsonRes->result->status,
                            'amount' => $jsonRes->result->amount
                        ];
                        $res->messageCode = $jsonRes->result->status;
                        $res->message = $jsonRes->message;
                        $res->result = $temp;
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi xử lý dữ liệu kiểm tra thanh toán.';
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi yêu cầu thanh toán. Guzzle Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi yêu cầu kiểm tra. Exception: '.$e->getMessage();
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/request-payment-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request check Request payment, RequestId: {$requestId}; SendData: ".\json_encode($sendData)."; RESPONSE: ".\json_encode($res));

        return $res;
    }

    /**
     * @param        $partner
     * @param        $amount
     * @param string $region
     * @param \WC_Order   $order
     *
     * @return Result
     */
    public function requestPayment($partner, $amount, $region = 'Ha Noi', $order = null)
    {
        $res = new Result();

        $sendData = [
            "sender" => Config::PAYMENT_HUB_TGS_SENDER,
            "requestAmount" => $amount,
        ];

        if ($region == 'Ha Noi') {
            $sendData['restaurantCode'] = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE;
            $sendData['posId'] = Config::PAYMENT_HUB_TGS_RESTAURANT_POS_ID;
        } elseif ($region == 'Ho Chi Minh') {
            $sendData['restaurantCode'] = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE_HCM;
            $sendData['posId'] = Config::PAYMENT_HUB_TGS_RESTAURANT_POS_ID_HCM;
        }

        if ($partner == 'VNPAY') {
            $sendData["rkPaymentCode"] = Config::PAYMENT_HUB_VNPAY_RK_PAYMENT_CODE;
        } elseif ($partner == 'ZALOPAY') {
            $sendData["rkPaymentCode"] = Config::PAYMENT_HUB_ZALO_RK_PAYMENT_CODE;
        } elseif ($partner == 'VINID') {
            $sendData["rkPaymentCode"] = Config::PAYMENT_HUB_VINID_RK_PAYMENT_CODE;
        } elseif ($partner == 'MOMO') {
            $sendData["rkPaymentCode"] = Config::PAYMENT_HUB_MOMO_RK_PAYMENT_CODE;
        } elseif ($partner == 'SHOPEE_PAY') {
            $sendData["rkPaymentCode"] = Config::PAYMENT_HUB_SHOPEE_PAY_RK_PAYMENT_CODE;
        } elseif ($partner == 'VNPAY_BANK_ONLINE') {
            $sendData['partnerId'] = Config::PAYMENT_HUB_VNPAY_BANK_ONLINE_PARTNER_ID;
            $sendData['bankCode'] = 'VNBANK';
        } elseif ($partner == 'VNPAY_BANK_ONLINE_INTERNATIONAL_CARD') {
            $sendData['partnerId'] = Config::PAYMENT_HUB_VNPAY_BANK_ONLINE_PARTNER_ID;
            $sendData['bankCode'] = 'INTCARD';
        } elseif ($partner == 'VNPT_EPAY_BANK_ONLINE') {
            $sendData['partnerId'] = Config::PAYMENT_HUB_VNPT_EPAY_BANK_ONLINE_PARTNER_ID;
            //$sendData['bankCode'] = 'VNBANK';
        }

        if (($partner == 'VNPAY_BANK_ONLINE' || $partner == 'VNPAY_BANK_ONLINE_INTERNATIONAL_CARD') && $order) {
            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';

            $sendData['returnUrl'] = site_url('update-bank-online?orderId='.$order->get_id());
            $sendData['customerIp'] = $_SERVER['REMOTE_ADDR'];
            $sendData['locale'] = 'vn';
            $res->result = [
                'response' => Config::PAYMENT_HUB_VNPAY_BANK_ONLINE_REQUEST_URL.'?'.http_build_query($sendData),
                'requestId' => 0, // in fact, at this moment we dont have request payment id, after return from payment, we gonna get and update request payment id
                'partner' => [
                    'logo' => '',
                    'name' => 'Vnpay Bank Online',
                    'rkPaymentCode' => '',
                ]
            ];
        } elseif ($partner == 'VNPT_EPAY_BANK_ONLINE') {
            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';

            $sendData['returnUrl'] = site_url('update-bank-online?orderId='.$order->get_id());
            $sendData['customerIp'] = $_SERVER['REMOTE_ADDR'];
            $sendData['locale'] = 'vn';
            $res->result = [
                'response' => Config::PAYMENT_HUB_VNPT_EPAY_BANK_ONLINE_IFRAME_URL.'?'.http_build_query($sendData),
                'requestId' => 0, // in fact, at this moment we dont have request payment id, after return from payment, we gonna get and update request payment id
                'partner' => [
                    'logo' => '',
                    'name' => 'VNPT ePay',
                    'rkPaymentCode' => '',
                ]
            ];
        } else {
            try {
                $requestPayment = $this->httpClient->request(
                    'post',
                    \GDelivery\Libs\Config::PAYMENT_HUB_API_BASE_URL.'gpos/request-payment',
                    [
                        \GuzzleHttp\RequestOptions::HEADERS => [
                            'Authorization' => 'Bearer '.\GDelivery\Libs\Config::PAYMENT_HUB_API_KEY
                        ],
                        \GuzzleHttp\RequestOptions::JSON => $sendData
                    ]
                );

                $jsonRes = \json_decode($requestPayment->getBody());
                if ($jsonRes) {
                    if ($jsonRes->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = $jsonRes->message;

                        // get base64 partnerResponse
                        $qrCode = new \SimpleSoftwareIO\QrCode\Generator();
                        $image = $qrCode->format('png')->size(300)->merge($jsonRes->result->partner->logo)->generate($jsonRes->result->partnerResponse);
                        $res->result = [
                            'response' => base64_encode($image),
                            'requestId' => $jsonRes->result->id,
                            'partner' => [
                                'logo' => $jsonRes->result->partner->logo,
                                'name' => $jsonRes->result->partner->name,
                                'rkPaymentCode' => $jsonRes->result->partner->rkPaymentCode,
                            ]
                        ];

                    } else {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Có lỗi khi xử lý dữ liệu yêu cầu thanh toán.';
                }
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi yêu cầu thanh toán. Guzzle Exception: '.$e->getMessage();
            } catch (\Exception $e) {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi yêu cầu thanh toán. Exception: '.$e->getMessage();
            }
        }

        return $res;
    }

    public function checkVoucher($voucherCode, $restaurantCode, $partnerId = 8)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $sendData = [
            'orderId' => uniqid(),
            'shiftDate' => date_i18n('Y-m-d'),
            'restaurantCode' => $restaurantCode,
            'partnerId' => $partnerId,
            'voucherCode' => $voucherCode
        ];

        $endPoint = 'voucher/check?returnHttpStatus=true';

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/voucher-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request check voucher, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $strRes = $doRequest->getBody()->getContents();
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = $jsonRes->messageCode;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api OTP: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $jsonRes = \json_decode($e->getResponse()->getBody()->getContents());
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to pase response body; Message: '.$e->getMessage();
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Request check voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request check voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end check voucher

    /**
     * @param     $voucherCode
     * @param     $restaurantCode
     * @param     $order
     * @param int $partnerId
     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function utilizeVoucher($voucherCode, $restaurantCode, $order, $partnerId = 8)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        //prepare guid for pos
        $strId = $order->get_id();
        if (\strlen($strId) < 8) {
            $len = 8 - \strlen($strId);
            for ($i = 1; $i <= $len; $i++) {
                $strId = '0'.$strId;
            }
        }

        // prepare restaurant code
        $sendData = [
            'orderId' => 'DELIVERY000000000000'.$strId,
            'shiftDate' => date_i18n('Y-m-d'),
            'restaurantCode' => $restaurantCode,
            'partnerId' => $partnerId,
            'voucherCode' => $voucherCode
        ];

        $endPoint = 'voucher/utilize?returnHttpStatus=true';

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/voucher-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request utilize voucher, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $strRes = $doRequest->getBody()->getContents();
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = $jsonRes->messageCode;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api utilize: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $jsonRes = \json_decode($e->getResponse()->getBody()->getContents());
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to pase response body; Message: '.$e->getMessage();
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Request utilize voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request utilize voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end utilize voucher

    /**
     * @param     $voucherCode
     * @param \WC_Order $order
     * @param int $partnerId
     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancelVoucher($voucherCode, $order, $partnerId = 8)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        //prepare guid for pos
        $strId = $order->get_id();
        if (\strlen($strId) < 8) {
            $len = 8 - \strlen($strId);
            for ($i = 1; $i <= $len; $i++) {
                $strId = '0'.$strId;
            }
        }

        // prepare restaurant code
        $restaurantObj = $order->get_meta('restaurant_in_tgs')->restaurant;
        $restaurantCode = $restaurantObj->code;

        $sendData = [
            'restaurantCode' => $restaurantCode,
            'partnerId' => $partnerId,
            'code' => $voucherCode
        ];

        $endPoint = 'voucher/cancel-utilize?returnHttpStatus=true';

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/voucher-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request cancel voucher, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));


        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $strRes = $doRequest->getBody()->getContents();
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = $jsonRes->messageCode;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api OTP: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $jsonRes = \json_decode($e->getResponse()->getBody()->getContents());
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to pase response body; Message: '.$e->getMessage();
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Response cancel voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Response cancel voucher, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end cancel utilize voucher

    public function createTransaction($params = [])
    {
        $res = new Result();
        $requestId = \uniqid();
        $startTime = microtime(true);

        $sendData = [
            "restaurantCode" => $params['restaurantCode'],
            "selectedVouchers" => isset($params['selectedVouchers']) ? $params['selectedVouchers'] : null,
            'tgsSelectedVoucherAmount' => isset($params['tgsSelectedVoucherAmount']) ? $params['tgsSelectedVoucherAmount'] : 0,
            "orderGuid" => isset($params['orderGuid']) ? $params['orderGuid'] : null,
            "shiftDate" => isset($params['shiftDate']) ? $params['shiftDate'] : date_i18n('Y-m-d H:i:s'),
            "clmCardNo" => isset($params['clmCardNo']) ? $params['clmCardNo'] : null,
            "clmCustomerNumber" => isset($params['clmCustomerNumber']) ? $params['clmCustomerNumber'] : null,
            "isTransactionInTGS" => 1,
            "selectedWalletAccounts" => isset($params['selectedWalletAccounts']) ? $params['selectedWalletAccounts'] : null
        ];

        $endPoint = \GDelivery\Libs\Config::PAYMENT_HUB_API_BASE_URL.'transaction';
        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/payment-hub/transaction-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request Create Transaction, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        try {
            $requestPayment = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.\GDelivery\Libs\Config::PAYMENT_HUB_API_KEY
                    ],
                    \GuzzleHttp\RequestOptions::JSON => $sendData
                ]
            );

            $jsonRes = \json_decode($requestPayment->getBody());
            if ($jsonRes) {
                if ($jsonRes->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $jsonRes->result;

                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi xử lý dữ liệu tạo giao dịch.';
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi tạo giao dịch. Guzzle Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi tạo giaod ịch. Exception: '.$e->getMessage();
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Response Create Transaction, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Response Create Transaction, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    }

    public function holdBalance($customerNumber, $amount, $options = [])
    {
        $endPoint = "biz-account/customer/{$customerNumber}/hold-balance?returnHttpStatus=false";
        $source = Config::PAYMENT_HUB_GBIZ_SOURCE;
        $sendData = [
            "amount" => $amount,
            "source" => $source,
        ];

        if (isset($options['referTransaction'])) {
            $sendData['referTransaction'] = $options['referTransaction'];
        }

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api hold balance : '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function releaseBalance($customerNumber, $holdId)
    {
        $endPoint = "biz-account/customer/release-on-hold-balance?returnHttpStatus=false";
        $sendData = [
            "holdId" => $holdId,
        ];

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api release balance : '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function listRating($orderGuid)
    {
        $res = new Result();
        $endPoint = "transaction/$orderGuid/rating";

        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint
            );

            if ($doRequest->getStatusCode() == 200) {
                $strRes = $doRequest->getBody()->getContents();
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = $jsonRes->messageCode;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Có lỗi khi lấy danh sách đánh giá - ' . $doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $jsonRes = \json_decode($e->getResponse()->getBody()->getContents());
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to pase response body; Message: '.$e->getMessage();
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        } catch (GuzzleException $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'GuzzleException: '.$e->getMessage();
        }

        return $res;
    }
}
