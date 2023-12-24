<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Location {

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

        $this->logger = new Logger('payment-hub');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     *
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     *
     * @return Result
     */
    public function vincentyGreatCircleDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        $res = new Result();
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $angle * $earthRadius;

        return $res;
    }

    public function googleMapDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $unit = 'metric'
    ) {
        $res = new Result();

        $strApi = "https://maps.googleapis.com/maps/api/distancematrix/json";
        $strApi .= "?units={$unit}";
        $strApi .= "&origins={$latitudeFrom},{$longitudeFrom}";
        $strApi .= "&destinations={$latitudeTo},{$longitudeTo}";
        $strApi .= "&key=".Config::GOOGLE_MAP_API_KEY;

        $keyCache = 'googleMapDistance:'.md5($strApi);
        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        $distance = null;
        if ($getCache) {
            $distance = $getCache;
        } else {
            // get content
            $strRes = file_get_contents($strApi);

            $jsonRes = \json_decode($strRes);

            if ($jsonRes) {
                if ($jsonRes->status == 'OK') {
                    // get closest
                    if ($jsonRes->rows[0]->elements[0]->status == 'OK') {
                        $distance = $jsonRes->rows[0]->elements[0]->distance->value;

                        // set to cache
                        try {
                            $this->redis->set($keyCache, $distance);
                        } catch (\Exception $e) {

                        }
                    } else {
                        $res->messageCode = Message::GENERAL_ERROR;
                        $res->message = $jsonRes->error_message;
                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->error_message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to parse json distance';
            }
        }

        if ($distance !== null) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $distance;
        }
    } // end goolgle distance

    public function getGoogleMapAddress($address)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $res = new Result();
        // get long/lat from address
        $mapEndpoint = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($address).'&key='.Config::GOOGLE_MAP_API_KEY."&components=country:VN";
        $keyCache = 'googleMapAddress:'.md5($mapEndpoint);

        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/google-address/get-google-map-address-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request check time get address, RequestId: {$requestId};");

        $jsonAddress = null;
        if ($getCache) {
            $jsonAddress = \json_decode($getCache);
        } else {
            try {
                $getAddress = $this->httpClient->request(
                    'get',
                    $mapEndpoint
                );

                $jsonRes = \json_decode($getAddress->getBody()->getContents());

                if ($jsonRes->status == 'OK') {
                    $jsonAddress = $jsonRes->results;

                    // set to cache
                    try {
                        $this->redis->set($keyCache, \json_encode($jsonAddress));
                    } catch (\Exception $e) {

                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->error_message;
                    $this->logger->error((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $e->getMessage();
                $this->logger->error((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
            }
        }

        if($jsonAddress) {
            $arrAddress = [];
            foreach ($jsonAddress as $oneAddress) {
                $addressParams = explode(',', $oneAddress->formatted_address);

                $temp = new \stdClass();
                $temp->googleMapPlaceId =  $oneAddress->place_id;
                $temp->address =  $oneAddress->formatted_address;
                $temp->longitude = $oneAddress->geometry->location->lng;
                $temp->latitude = $oneAddress->geometry->location->lat;

                if (\count($addressParams) == 6) {
                    // in case: "Tầng 5 TTTM Hà Nội Centerpoint, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội, Vietnam"
                    $temp->addressLine1 = trim($addressParams[0]).', '.trim($addressParams[1]);
                    $temp->wardName = trim($addressParams[2]);
                    $temp->districtName = trim($addressParams[3]);
                    $temp->provinceName = trim($addressParams[4]);
                } elseif (\count($addressParams) == 5) {
                    // in case: "315 Trường Chinh, Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $temp->addressLine1 = trim($addressParams[0]);
                    $temp->wardName = trim($addressParams[1]);
                    $temp->districtName = trim($addressParams[2]);
                    $temp->provinceName = trim($addressParams[3]);
                } elseif (\count($addressParams) == 4) {
                    // in case: "Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $temp->addressLine1 = trim($addressParams[0]);
                    $temp->provinceName = trim($addressParams[2]);
                } else {
                    $temp->addressLine1 = '';
                    $temp->wardName = '';
                    $temp->districtName = '';
                    $temp->provinceName = '';
                }

                $arrAddress[] = $temp;
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $arrAddress;
            $this->logger->info((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    }

    /**
     * search goong address
     * @param $address
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoongAddress($address)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $res = new Result();
        // get long/lat from address
        $limit = !empty(Config::LIMIT_SEARCH_GOONG_ADDRESS) ? Config::LIMIT_SEARCH_GOONG_ADDRESS : 50;
        $mapEndpoint = 'https://rsapi.goong.io/Place/AutoComplete?input='.urlencode($address).'&limit='.$limit.'&api_key='.Config::GOONG_API_KEY;
        $keyCache = 'goongAddress:'.md5($mapEndpoint);

        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/goong-address/get-goong-map-address-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request check time get address, RequestId: {$requestId};");

        $jsonAddress = null;
        if ($getCache) {
            $jsonAddress = \json_decode($getCache);
        } else {
            try {
                $getAddress = $this->httpClient->request(
                    'get',
                    $mapEndpoint
                );

                $jsonRes = \json_decode($getAddress->getBody()->getContents());
                if ($jsonRes->status == 'OK') {
                    $jsonAddress = $jsonRes->predictions;

                    // set to cache
                    try {
                        $this->redis->set($keyCache, \json_encode($jsonAddress));
                    } catch (\Exception $e) {

                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->error_message;
                    $this->logger->error((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $e->getMessage();
                $this->logger->error((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
            }
        }

        if($jsonAddress) {
            $arrAddress = [];
            foreach ($jsonAddress as $oneAddress) {
                $addressParams = explode(',', $oneAddress->description);

                $temp = new \stdClass();
                $temp->googleMapPlaceId = $oneAddress->place_id;
                $temp->address =  $oneAddress->description;
                $temp->longitude = '';
                $temp->latitude = '';

                if (\count($addressParams) == 5) {
                    // in case: "CGV Hà Nội Centerpoint, Hà Nội Center Point, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội"
                    $temp->addressLine1 = trim($addressParams[0]).', '.trim($addressParams[1]);
                    $temp->wardName = trim($addressParams[2]);
                    $temp->districtName = trim($addressParams[3]);
                    $temp->provinceName = trim($addressParams[4]);
                } elseif (\count($addressParams) == 4) {
                    // in case: "315 Trường Chinh, Khương Mai, Thanh Xuân, Hà Nội"
                    $temp->addressLine1 = trim($addressParams[0]);
                    $temp->wardName = trim($addressParams[1]);
                    $temp->districtName = trim($addressParams[2]);
                    $temp->provinceName = trim($addressParams[3]);
                } elseif (\count($addressParams) == 3) {
                    // in case: "Khương Thượng, Đống Đa, Hà Nội"
                    $temp->addressLine1 = trim($addressParams[0]);
                    $temp->districtName = trim($addressParams[1]);
                    $temp->provinceName = trim($addressParams[2]);
                } else {
                    $temp->addressLine1 = '';
                    $temp->wardName = '';
                    $temp->districtName = '';
                    $temp->provinceName = '';
                }

                $arrAddress[] = $temp;
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $arrAddress;
            $this->logger->info((microtime(true) - $startTime)."||||Request check time get address, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    }

    /**
     * Get goong address detail
     * @param $placeId
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGoongAddressDeatailByPlaceId($placeId)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $res = new Result();
        // get long/lat from address
        $mapEndpoint = 'https://rsapi.goong.io//Place/Detail?place_id='.$placeId.'&api_key='.Config::GOONG_API_KEY;
        $keyCache = 'goongAddress:'.md5($mapEndpoint);

        try {
            $getCache = $this->redis->get($keyCache);
        } catch (\Exception $e) {
            $getCache = false;
        }

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/goong-address/get-goong-map-address-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request get address detail, RequestId: {$requestId};");

        $jsonAddress = null;
        if ($getCache) {
            $jsonAddress = \json_decode($getCache);
        } else {
            try {
                $getAddress = $this->httpClient->request(
                    'get',
                    $mapEndpoint
                );

                $jsonRes = \json_decode($getAddress->getBody()->getContents());
                if ($jsonRes->status == 'OK') {
                    $jsonAddress = $jsonRes->result;

                    // set to cache
                    try {
                        $this->redis->set($keyCache, \json_encode($jsonAddress));
                    } catch (\Exception $e) {

                    }
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->error_message;
                    $this->logger->error((microtime(true) - $startTime)."||||Request get address detail, RequestId: {$requestId}; Response: ".\json_encode($res));
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $e->getMessage();
                $this->logger->error((microtime(true) - $startTime)."||||Request get address detail, RequestId: {$requestId}; Response: ".\json_encode($res));
            }
        }

        if($jsonAddress) {
            $description = $jsonAddress->name . ', ' . $jsonAddress->formatted_address;
            $addressParams = explode(',', $description);

            $temp = new \stdClass();
            $temp->googleMapPlaceId =  $placeId;
            $temp->address =  $description;
            $temp->longitude = $jsonAddress->geometry->location->lng;
            $temp->latitude = $jsonAddress->geometry->location->lat;

            if (\count($addressParams) == 7) {
                // in case: "Tầng 1, Glow Skybar, President Place, 93 Nguyễn Du, Bến Nghé, Quận 1, Hồ Chí Minh"
                $temp->addressLine1 = trim($addressParams[0]).', '.trim($addressParams[1]).', '.trim($addressParams[2]).', '.trim($addressParams[3]);
                $temp->wardName = trim($addressParams[4]);
                $temp->districtName = trim($addressParams[5]);
                $temp->provinceName = trim($addressParams[6]);
            } elseif (\count($addressParams) == 6) {
                // in case: "Glow Skybar, President Place, 93 Nguyễn Du, Bến Nghé, Quận 1, Hồ Chí Minh"
                $temp->addressLine1 = trim($addressParams[0]).', '.trim($addressParams[1]).', '.trim($addressParams[2]);
                $temp->wardName = trim($addressParams[3]);
                $temp->districtName = trim($addressParams[4]);
                $temp->provinceName = trim($addressParams[5]);
            } elseif (\count($addressParams) == 5) {
                // in case: "CGV Hà Nội Centerpoint, Hà Nội Center Point, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội"
                $temp->addressLine1 = trim($addressParams[0]).', '.trim($addressParams[1]);
                $temp->wardName = trim($addressParams[2]);
                $temp->districtName = trim($addressParams[3]);
                $temp->provinceName = trim($addressParams[4]);
            } elseif (\count($addressParams) == 4) {
                // in case: "315 Trường Chinh, Khương Mai, Thanh Xuân, Hà Nội"
                $temp->addressLine1 = trim($addressParams[0]);
                $temp->wardName = trim($addressParams[1]);
                $temp->districtName = trim($addressParams[2]);
                $temp->provinceName = trim($addressParams[3]);
            } elseif (\count($addressParams) == 3) {
                // in case: "Khương Thượng, Đống Đa, Hà Nội"
                $temp->addressLine1 = trim($addressParams[0]);
                $temp->districtName = trim($addressParams[1]);
                $temp->provinceName = trim($addressParams[2]);
            } else {
                $temp->addressLine1 = '';
                $temp->wardName = '';
                $temp->districtName = '';
                $temp->provinceName = '';
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $temp;
            $this->logger->info((microtime(true) - $startTime)."||||Request get address detail, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    }

} // end class
