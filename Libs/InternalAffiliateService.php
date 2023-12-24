<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InternalAffiliateService {

    private $httpClient;
    private $logger;

    private function parseInternalAffiliateToVoucher($jsonObj, $restaurant = null)
    {
        $voucher = new \stdClass();

        // voucher type
        switch ($jsonObj->campaign->campaign_voucher_type->voucher_type->voucher_category->id) {
            case 1:
                // voucher tiền mặt
                $voucher->type = 1;
                $voucher->typeName = 'Tương đương thanh toán tiền';
                break;
            case 2:
                // giảm giá % bill
                $voucher->type = 4;
                $voucher->typeName = 'Giảm giá % trên bill';
                break;
            case 3:
                // giảm giá % trên món; tặng món là giảm 100% món
                $voucher->type = 5;
                $voucher->typeName = 'Giảm giá % trên món';
                break;
            case 4:
                // coupon
                $voucher->type = 2;
                $voucher->typeName = 'Coupon giảm giá số tiền trên order';
                break;
            case 5:
                // giảm giá số tiền trên món ăn
                $voucher->type = 3;
                $voucher->typeName = 'Giảm giá số tiền trên món';
                break;
            default:
        }

        // rk info; calculate denomination value
        $voucher->denominationValue = 0;
        if ($restaurant) {
            foreach ($jsonObj->campaign->custom_fields as $oneField) {
                $promotion = \json_decode($oneField->value);
                if (isset($promotion->sapCompanyCode) && $restaurant->sapCompanyCode == $promotion->sapCompanyCode) {
                    $voucher->rkPaymentCode = $promotion->code;

                    // set apply for item
                    if ($promotion->isCondition) {
                        $voucher->applyForRkItemCodes =$promotion->condition;
                    }

                    $voucher->denominationValue = (float) $promotion->value;
                    break; // just process once
                }
            }
        }

        $voucher->status = 2;
        $voucher->expiryTime = $jsonObj->campaign->end_date;
        $voucher->validFrom = $jsonObj->campaign->start_date;
        $voucher->partnerName = 'Internal Affiliate';
        $voucher->partnerId = 999; // partner id of GG Affiliate
        $voucher->name = 'Affiliate nội bộ';
        $voucher->descriptionCondition = '';
        $voucher->code = '';

        // set condition
        $arr = [];
        if (isset($jsonObj->campaign->conditions) && $jsonObj->campaign->conditions) {
            foreach ($jsonObj->campaign->conditions as $oneCondition) {
                $temp = new \stdClass();
                switch ($oneCondition->name) {
                    case 'min_transaction_value' :
                        $temp->key = 'min_bill_value';
                        $temp->value = (double) $oneCondition->value;
                        $temp->name = 'Giá trị bill tối thiểu';
                        break;
                    case 'parallel_apply_with_before_tax_campaign' :
                        $temp->key = 'parallel_apply_with_before_tax_campaign';
                        $temp->name = 'Cho phép áp dụng song song các chương trình voucher khác (trước thuế)';
                        $listCampaignIds = [];
                        foreach (\json_decode($oneCondition->value) as $oneCampaign) {
                            $listCampaignIds[] = $oneCampaign->campaign_id;
                        }
                        $temp->value = $listCampaignIds;
                        break;
                    case 'parallel_apply_with_after_tax_campaign' :
                        $temp->key = 'parallel_apply_with_after_tax_campaign';
                        $temp->name = 'Cho phép áp dụng song song các chương trình voucher khác (sau thuế)';
                        $listCampaignIds = [];
                        foreach (\json_decode($oneCondition->value) as $oneCampaign) {
                            $listCampaignIds[] = $oneCampaign->campaign_id;
                        }
                        $temp->value = $listCampaignIds;
                        break;
                    case 'allow_parallel_with_clm_campaign' :
                        $temp->key = 'allow_parallel_with_clm_campaign';
                        $temp->name = 'Cho phép áp dụng song song các chương trình CLM';
                        if ($oneCondition->value) {
                            $temp->value = true;
                        } else {
                            $temp->value = false;
                        }
                        break;
                    case 'allow_gain_clm_point' :
                        $temp->key = 'allow_gain_clm_point';
                        $temp->name = 'Được tích lũy lên hạng';
                        if ($oneCondition->value) {
                            $temp->value = true;
                        } else {
                            $temp->value = false;
                        }
                        break;
                    case 'allow_gain_clm_coin' :
                        $temp->key = 'allow_gain_clm_coin';
                        $temp->name = 'Được tích ví GPP';
                        if ($oneCondition->value) {
                            $temp->value = true;
                        } else {
                            $temp->value = false;
                        }
                        break;
                    case 'is_accumulated' :
                        $temp->key = 'is_accumulated';
                        $temp->name = 'Được lũy kế';
                        if ($oneCondition->value) {
                            $temp->value = true;
                        } else {
                            $temp->value = false;
                        }
                        break;
                    default:
                        $temp = null;
                }

                if ($temp) {
                    $arr[] = $temp;
                }
            }
        }

        // set condition
        $voucher->conditions = $arr;

        // campaign
        if (isset($jsonObj->campaign) && $jsonObj->campaign) {
            $tem = new \stdClass();
            $tem->id = $jsonObj->campaign->id;
            $tem->name = $jsonObj->campaign->name;

            $voucher->campaign = $tem;
        } else {
            $tem = new \stdClass();
            $tem->id = $jsonObj->campaign_id;
            $tem->name = '';

            $voucher->campaign = $tem;
        }

        return $voucher;
    }

    public function __construct()
    {
        $this->httpClient = new Client(
            [
                'base_uri' => \GDelivery\Libs\Config::INTERNAL_AFFILIATE_API_BASE_URL,
                'headers' => [
                    'X-Authorization' => \GDelivery\Libs\Config::INTERNAL_AFFILIATE_API_KEY
                ]
            ]
        );

        $this->logger = new Logger('internal-affiliate');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function checkReferralCode($code, $restaurant = null)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $sendData = [
            'agency_code' => $code
        ];

        $endPoint = 'get-campaign';

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/internal-affiliate/check-referral_code-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request check referral code, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'get',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            $strRes = $doRequest->getBody()->getContents();
            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->code == 200) {
                    $voucher = $this->parseInternalAffiliateToVoucher($jsonRes->data, $restaurant);
                    $voucher->code = $code;

                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                    $res->result = $voucher;

                    // set to cookie also
                    setcookie('ggg_internal_affiliate', $code, (time() + 86400), '/');
                } else {
                    $res->messageCode = $jsonRes->code;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api check agent code: '.$doRequest->getStatusCode();
            }

            $this->logger->info((microtime(true) - $startTime)."||||Request check referral code, RequestId: {$requestId}; Response: {$strRes}");
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $strRes = $e->getResponse()->getBody()->getContents();
                $jsonRes = \json_decode($strRes);
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to pase response body; Message: '.$e->getMessage();
                }
            } else {
                $strRes = 'Exception: '.$e->getMessage();
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $strRes;
            }

            $this->logger->info((microtime(true) - $startTime)."||||Request check referral code, RequestId: {$requestId}; Response: {$strRes}");
        } catch (\Exception $e) {
            $strRes = 'Exception: '.$e->getMessage();
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = $strRes;

            $this->logger->info((microtime(true) - $startTime)."||||Request check referral code, RequestId: {$requestId}; Response: {$strRes}");
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Request check referral code, RequestId: {$requestId}; Response Result: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request check referral code, RequestId: {$requestId}; Response Result: ".\json_encode($res));
        }

        return $res;
    } // end check voucher

    /**
     * @param \WC_Order $order
     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder($order, $affiliateCode)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $options = [];
        if ($order->get_meta('is_pickup_at_restaurant') == 1) {
            $options['pickupAtRestaurant'] = 1;
        }
        if (get_option('google_map_service_address') == 'goong_address') {
            $options['shippingVendor'] = 'restaurant';
        } else {
            $options['shippingVendor'] = 'grab_express';
        }
        $total = Helper::calculateOrderTotals($order, $options);
        $customerInfo = $order->get_meta('customer_info');

        $affOrderId = 'GDelivery_'.$order->get_ID();
        $sendData = [
            "order_code" => $affOrderId,
            "pre_tax_revenue" => ($total->totalPrice - $total->shipping->price),
            "status" => "new",
            "agency_code" => $affiliateCode,
            "reference_agency_code" => $affiliateCode,
            "g_people_card" => $customerInfo->gppCardNumber,
            "restaurant_code" => $order->get_meta('restaurant_code'),
            "name" => $customerInfo->fullName,
            "guest_number" => 1,
            "discount" => $total->totalDiscount,
            "check_number" => 1,
            "email" => $customerInfo->email
        ];

        $endPoint = 'orders';

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/internal-affiliate/order-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request create order, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'post',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            $strRes = $doRequest->getBody()->getContents();

            $this->logger->info("Request create order, RequestId: {$requestId}; Response API: ".$strRes);
            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($strRes);

                $temp = new \stdClass();
                $temp->referralCode = $affiliateCode;
                $temp->orderId = $affOrderId;

                $order->update_meta_data('ggg_internal_affiliate', $temp);
                $order->save();

                if ($jsonRes->code == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = "Ưu đãi không khả dụng ({$jsonRes->code} - {$jsonRes->message})";
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api create order: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $strRes = $e->getResponse()->getBody()->getContents();
                $this->logger->error("Request create order, RequestId: {$requestId}; Response API: ".$strRes);

                $jsonRes = \json_decode($strRes);
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi tạo order: ('.$jsonRes->code.' - '.$jsonRes->message.')';
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
            $this->logger->info((microtime(true) - $startTime)."||||Request create order, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request create order, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end check voucher

    /**
     * @param \WC_Order $order
     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateOrderStatus($order, $status)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        /*if (get_option('google_map_service_address') == 'goong_address') {
            $options['shippingVendor'] = 'restaurant';
        } else {
            $options['shippingVendor'] = 'grab_express';
        }*/

        $total = Helper::orderTotals($order);

        $sendData = [
            "status" => $status,
        ];

        $affOrderId = $order->get_meta('ggg_internal_affiliate')->orderId;
        $endPoint = "orders/{$affOrderId}";

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/internal-affiliate/order-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request update order, RequestId: {$requestId}; EndPoint: {$endPoint}; SendData: ".\json_encode($sendData));

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'put',
                $endPoint,
                [
                    RequestOptions::JSON => $sendData
                ]
            );

            $strRes = $doRequest->getBody()->getContents();

            $this->logger->info("Request update order, RequestId: {$requestId}; Response API: ".$strRes);
            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->code == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = $jsonRes->code;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api update order: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $strRes = $e->getResponse()->getBody()->getContents();
                $this->logger->error("Request update order, RequestId: {$requestId}; Response API: ".$strRes);

                $jsonRes = \json_decode($strRes);
                if ($jsonRes) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Exception: fail to parse response body; Message: '.$e->getMessage();
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
            $this->logger->info((microtime(true) - $startTime)."||||Request update order, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request update order, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end update order

    /**
     * @param \WC_Order $order
     *
     * @return Result
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteOrder($order)
    {
        $startTime = microtime(true);
        $requestId = uniqid();

        $affOrderId = $order->get_meta('ggg_internal_affiliate')->orderId;
        $endPoint = "orders/{$affOrderId}";

        // logging
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/internal-affiliate/order-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->info("Request delete order, RequestId: {$requestId}; EndPoint: {$endPoint};");

        $res = new Result();
        try {
            $doRequest = $this->httpClient->request(
                'delete',
                $endPoint
            );

            $strRes = $doRequest->getBody()->getContents();

            $this->logger->info("Request delete order, RequestId: {$requestId}; Response API: ".$strRes);

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            if ($doRequest->getStatusCode() == 200) {
                $jsonRes = \json_decode($strRes);
                if ($jsonRes->code == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = $jsonRes->message;
                } else {
                    $res->messageCode = $jsonRes->code;
                    $res->message = $jsonRes->message;
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi api delete order: '.$doRequest->getStatusCode();
            }
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                $strRes = $e->getResponse()->getBody()->getContents();
                $this->logger->error("Request delete order, RequestId: {$requestId}; Response API: ".$strRes);
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
            }
            $res->message = 'Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: '.$e->getMessage();
        }

        if ($res->messageCode == Message::SUCCESS || $res->messageCode == 200) {
            $this->logger->info((microtime(true) - $startTime)."||||Request delete order, RequestId: {$requestId}; Response: ".\json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTime)."||||Request delete order, RequestId: {$requestId}; Response: ".\json_encode($res));
        }

        return $res;
    } // end update order

}
