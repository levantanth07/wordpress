<?php
namespace GDelivery\Libs;

use Abstraction\Core\Helper;
use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class BookingService {

    private $httpClient;

    private $redis;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
    }

    public function getRestaurant($rkCode, $params = [])
    {
        $res = new Result();

        $keyCache = "cms:booking:restaurant:{$rkCode}:".http_build_query($params);
        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        $jsonMerchant = null;
        if ($getCache) {
            $jsonMerchant = \json_decode($getCache);
        } else {
            $endPoint = Config::BOOKING_API_BASE_URL."restaurant/{$rkCode}";

            try {
                $params['_key'] = Config::BOOKING_API_KEY;

                $doRequest = $this->httpClient->request(
                    'get',
                    $endPoint,
                    [
                        RequestOptions::QUERY => $params
                    ]
                );

                if ($doRequest->getStatusCode() == 200) {
                    $jsonRes = \json_decode($doRequest->getBody());

                    if ($jsonRes->messageCode == 1) {
                        $jsonMerchant = $jsonRes->result;

                        // set to cache
                        try {
                            $this->redis->set($keyCache, \json_encode($jsonRes->result));
                        } catch (\Exception $e) {

                        }
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi gọi api booking: '.$doRequest->getStatusCode();
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        }

        if ($jsonMerchant) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $jsonMerchant;
        }

        return $res;
    }

    public function getRestaurants()
    {
        $res = new Result();

        $keyCache = "booking:restaurants";
        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        $jsonRestaurants = null;
        if ($getCache) {
            $jsonRestaurants = \json_decode($getCache);
        } else {
            $endPoint = Config::BOOKING_API_BASE_URL."restaurant?_key=".Config::BOOKING_API_KEY;

            try {
                $doRequest = $this->httpClient->request('get', $endPoint);

                if ($doRequest->getStatusCode() == 200) {
                    $jsonRes = \json_decode($doRequest->getBody());

                    if ($jsonRes->messageCode == 1) {
                        $jsonRestaurants = $jsonRes->result;

                        // set to cache
                        try {
                            $this->redis->set($keyCache, \json_encode($jsonRes->result));
                        } catch (\Exception $e) {

                        }
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi gọi api booking: '.$doRequest->getStatusCode();
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        }

        if ($jsonRestaurants) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $jsonRestaurants;
        }

        return $res;
    }

    public function getBrands()
    {
        $endPoint = Config::BOOKING_API_BASE_URL."brand?_key=".Config::BOOKING_API_KEY;

        $keyCache = "booking:brands:".md5($endPoint);
        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        $res = new Result();

        $jsonBrands = null;
        if ($getCache) {
            $jsonBrands = \json_decode($getCache);
        } else {
            try {
                $doRequest = $this->httpClient->request(
                    'get',
                    $endPoint
                );

                if ($doRequest->getStatusCode() == 200) {
                    $jsonRes = \json_decode($doRequest->getBody());

                    if ($jsonRes->messageCode == 1) {
                        $jsonBrands = $jsonRes->result;

                        // set to cache
                        try {
                            $this->redis->set($keyCache, \json_encode($jsonRes->result));
                        } catch (\Exception $e) {

                        }
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi gọi api lấy thông tin thương hiệu: '.$doRequest->getStatusCode();
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: '.$e->getMessage();
            }
        }

        if ($jsonBrands) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $jsonBrands;
        }

        return $res;
    }

    public function getProvinces()
    {
        $res = new Result();

        $endPoint = Config::BOOKING_API_BASE_URL."province";

        $keyCache = "booking:provinces:".md5($endPoint);
        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        $jsonProvince = null;
        if ($getCache) {
            $jsonProvince = \json_decode($getCache);
        } else {
            try {
                $doRequest = $this->httpClient->request(
                    'get',
                    $endPoint,
                    [
                        RequestOptions::QUERY => [
                            '_key' => Config::BOOKING_API_KEY
                        ]
                    ]
                );

                if ($doRequest->getStatusCode() == 200) {
                    $jsonRes = \json_decode($doRequest->getBody());

                    if ($jsonRes->messageCode == 1) {
                        $jsonProvince = $jsonRes->result;
                        // set to cache
                        try {
                            $this->redis->set($keyCache, \json_encode($jsonProvince));
                        } catch (\Exception $e) {

                        }
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
        }

        if ($jsonProvince) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $jsonProvince;
        }

        return $res;
    }

    public function getProvince($id)
    {
        $res = new Result();

        $getListProvince = $this->getProvinces();

        if ($getListProvince->messageCode == Message::SUCCESS) {
            $temp = null;
            foreach ($getListProvince->result as $one) {
                if ($one->id == $id) {
                    $temp = $one;
                    break;
                }
            }

            if ($temp) {
                $res->messageCode = Message::SUCCESS;
                $res->message = $getListProvince->message;
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Not found';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = $getListProvince->message;
        }

        return $res;
    }

    public function getDistrict($id)
    {
        $res = new Result();

        $getListProvince = $this->getProvinces();

        if ($getListProvince->messageCode == Message::SUCCESS) {
            $temp = null;
            foreach ($getListProvince->result as $oneProvince) {
                foreach ($oneProvince->districts as $oneDistrict) {
                    if ($oneDistrict->id == $id) {
                        $temp = $oneDistrict;
                        break;
                    }
                }
            }

            if ($temp) {
                $res->messageCode = Message::SUCCESS;
                $res->message = $getListProvince->message;
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Not found';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = $getListProvince->message;
        }

        return $res;
    }

    public function getWard($id)
    {
        $res = new Result();

        $getListProvince = $this->getProvinces();

        if ($getListProvince->messageCode == Message::SUCCESS) {
            $temp = null;
            foreach ($getListProvince->result as $oneProvince) {
                foreach ($oneProvince->districts as $oneDistrict) {
                    foreach ($oneDistrict->wards as $oneWard) {
                        if ($oneWard->id == $id) {
                            $temp = $oneWard;
                            break;
                        }
                    }
                }
            }

            if ($temp) {
                $res->messageCode = Message::SUCCESS;
                $res->message = $getListProvince->message;
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Not found';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = $getListProvince->message;
        }

        return $res;
    }

    public function detectCurrentProvinceViaIP($ip)
    {
        $res = new Result();
        $keyCache = 'detect_IP:'.$ip;

        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        if ($getCache) {
            $location = \json_decode($getCache);
        } else {
            $location = \json_decode(file_get_contents('http://ip-api.com/json/'.$ip));
        }

        if ($location) {
            if (isset($location->city) && $location->city) {
                // set to cache
                try {
                    $this->redis->set($keyCache, \json_encode($location));
                } catch (\Exception $e) {

                }

                $currentCity = str_replace('city', '', strtolower($location->city));
                $currentCity = strtolower(str_replace(' ', '', $currentCity));
            } else {
                $currentCity = 'hanoi';
            }

            // list province
            $listProvinces = $this->getProvinces()->result;
            $currentProvince = null;
            foreach ($listProvinces as $province) {
                $provinceName = strtolower(str_replace(' ', '', Helper::convertUnsignedVietnamese($province->name)));

                if (strpos($provinceName, $currentCity) !== false) {
                    $currentProvince = $province;
                    break;
                }
            }

            if ($currentProvince) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $currentProvince;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Không xác định được địa chỉ';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Lỗi khi parse dữ liệu địa chỉ';
        }

        return $res;
    }

}