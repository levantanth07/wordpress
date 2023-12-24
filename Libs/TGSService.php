<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TGSService {

    private static $SECRET_KEY = Config::OLD_TGS_PUBLIC_KEY;
    private static $IV_KEY = Config::OLD_TGS_IV_KEY;
    private static $ENCRYPT_METHOD = 'DES-CBC';

    private $authentication;

    private $httpClient;

    public static function encrypt($string) {
        $output = openssl_encrypt($string, self::$ENCRYPT_METHOD, self::$SECRET_KEY, 0, self::$IV_KEY);
        return $output;
    }

    public static function decrypt($string) {
        $output = openssl_decrypt($string, self::$ENCRYPT_METHOD, self::$SECRET_KEY, 0, self::$IV_KEY);
        return $output;
    }

    public function __construct($authentication = null)
    {
        $this->httpClient = new Client();

        if ($authentication) {
            $this->authentication = $authentication;
        }
    }

    public function requestOTP($cellphone, $method = 'sms', $forceResend = false)
    {
        $sendData = [
            'cellphone' => self::encrypt($cellphone),
            'method' => $method,
            'appToLogin' => Config::TGS_SMS_APP_TO_LOGIN,
            'smsBrandName' => Config::TGS_SMS_BRAND_NAME,
            'forceResend' => $forceResend
        ];

        $endPoint = Config::TGS_API_BASE_URL.'authentication/request-otp';

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION
                    ],
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
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function login($cellphone, $otp)
    {
        $sendData = [
            'cellphone' => $cellphone,
            'otp' => $otp
        ];

        $endPoint = Config::TGS_API_BASE_URL.'authentication/login';

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION
                    ],
                    RequestOptions::JSON => $sendData
                ]
            );


            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

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
                $res->message = 'Lỗi khi gọi api OTP: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getAddresses($authentication = null)
    {
        if ($authentication) {
            $this->authentication = $authentication;
        }

        $endPoint = Config::TGS_API_BASE_URL."customer/address";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = $jsonRes->messageCode;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function deleteAddress($addressId, $authentication)
    {
        if ($authentication) {
            $this->authentication = $authentication;
        }

        $endPoint = Config::TGS_API_BASE_URL."customer/address/{$addressId}";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'delete',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Đã xóa địa chỉ';
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getAddressInfo($addressId, $authentication = null)
    {
        if ($authentication) {
            $this->authentication = $authentication;
        }

        $endPoint = Config::TGS_API_BASE_URL."customer/address/{$addressId}";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $addressObj = $jsonRes->result;
                    if (!isset($jsonRes->address)) {
                        $addressObj->address = $addressObj->addressLine1;
                    }
                    $res->result = $addressObj;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function updateAddress(
        $addressId,
        $params = [],
        $authentication = null
    ) {

        if ($authentication) {
            $this->authentication = $authentication;
        }

        $endPoint = Config::TGS_API_BASE_URL."customer/address/{$addressId}";

        $sendData = [];
        if (isset($params['name'])) {
            $sendData['name'] = $params['name'];
        }
        if (isset($params['alias'])) {
            $sendData['alias'] = $params['alias'];
        }
        if (isset($params['phone'])) {
            $sendData['phone'] = $params['phone'];
        }
        if (isset($params['address'])) {
            $sendData['addressLine1'] = $params['address'];
        }
        if (isset($params['addressLine1'])) {
            $sendData['addressLine1'] = $params['addressLine1'];
        }
        if (isset($params['wardId'])) {
            $sendData['wardId'] = $params['wardId'];
        }
        if (isset($params['wardName'])) {
            $sendData['wardName'] = $params['wardName'];
        }
        if (isset($params['districtId'])) {
            $sendData['districtId'] = $params['districtId'];
        }
        if (isset($params['districtName'])) {
            $sendData['districtName'] = $params['districtName'];
        }
        if (isset($params['provinceId'])) {
            $sendData['provinceId'] = $params['provinceId'];
        }
        if (isset($params['provinceName'])) {
            $sendData['provinceName'] = $params['provinceName'];
        }
        if (isset($params['isDefault'])) {
            $sendData['isDefault'] = $params['isDefault'];
        }
        if (isset($params['longitude'])) {
            $sendData['longitude'] = $params['longitude'];
        }
        if (isset($params['latitude'])) {
            $sendData['latitude'] = $params['latitude'];
        }
        if (isset($params['googleMapPlaceId'])) {
            $sendData['googleMapPlaceId'] = $params['googleMapPlaceId'];
        }

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'put',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ],
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Đã cập nhật địa chỉ';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function addNewAddress($params = [], $authentication = null)
    {
        if ($authentication) {
            $this->authentication = $authentication;
        }

        $endPoint = Config::TGS_API_BASE_URL."customer/address";

        $sendData = [];
        if (isset($params['name'])) {
            $sendData['name'] = $params['name'];
        }
        if (isset($params['alias'])) {
            $sendData['alias'] = $params['alias'];
        }
        if (isset($params['phone'])) {
            $sendData['phone'] = $params['phone'];
        }
        if (isset($params['address'])) {
            $sendData['addressLine1'] = $params['address'];
        }
        if (isset($params['addressLine1'])) {
            $sendData['addressLine1'] = $params['addressLine1'];
        }
        if (isset($params['wardId'])) {
            $sendData['wardId'] = $params['wardId'];
        }
        if (isset($params['wardName'])) {
            $sendData['wardName'] = $params['wardName'];
        }
        if (isset($params['districtId'])) {
            $sendData['districtId'] = $params['districtId'];
        }
        if (isset($params['districtName'])) {
            $sendData['districtName'] = $params['districtName'];
        }
        if (isset($params['provinceId'])) {
            $sendData['provinceId'] = $params['provinceId'];
        }
        if (isset($params['provinceName'])) {
            $sendData['provinceName'] = $params['provinceName'];
        }
        if (isset($params['isDefault'])) {
            $sendData['isDefault'] = $params['isDefault'];
        }
        if (isset($params['longitude'])) {
            $sendData['longitude'] = $params['longitude'];
        }
        if (isset($params['latitude'])) {
            $sendData['latitude'] = $params['latitude'];
        }
        if (isset($params['googleMapPlaceId'])) {
            $sendData['googleMapPlaceId'] = $params['googleMapPlaceId'];
        }

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ],
                    RequestOptions::JSON => $sendData
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

                if ($jsonRes->messageCode == 1) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thêm mới thành công';
                    $res->result = $jsonRes->result;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getProvinces()
    {
        $endPoint = Config::TGS_API_BASE_URL."booking/province";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

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
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getProvinceDistricts($provinceId)
    {
        $endPoint = Config::TGS_API_BASE_URL."booking/province/{$provinceId}/districts";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

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
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function getProfileInfo($customerNumber, $authentication = null)
    {
        if ($authentication) {
            $temp = new \stdClass();
            $temp->token = $authentication;
            $this->authentication = $temp;
        }
        $endPoint = Config::TGS_API_BASE_URL."customer/{$customerNumber}/profile";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$this->authentication->token
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

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
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }

    public function customerWallet($authentication)
    {
        $endPoint = Config::TGS_API_BASE_URL."customer/wallet/gift-promotions";

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::HEADERS => [
                        'tgs-version' => Config::TGS_VERSION,
                        'Authorization' => 'Bearer '.$authentication
                    ]
                ]
            );

            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($doRequest->getBody());

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
                $res->message = 'Lỗi khi gọi api lấy địa chỉ: '.$doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        return $res;
    }
}