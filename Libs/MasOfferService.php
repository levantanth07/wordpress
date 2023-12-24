<?php

namespace GDelivery\Libs;

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class MasOfferService
{
    private $httpClient;
    private $logger;

    public function __construct()
    {
        $this->httpClient = new Client(
            [
                'base_uri' => \GDelivery\Libs\Config::MAS_OFFER_API_BASE_URL,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]
        );

        $this->logger = new Logger('mas-offer');
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/mas-offer/mas-offer-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
    }

    public function transaction($order, $status = 0)
    {
        $res = new Result();

        try {

            $totals = \GDelivery\Libs\Helper\Helper::orderTotals(
                $order
            );
            // todo for nghi.bui: sửa thêm ở createOrder, gọi api tạo transaction với status = 0 (pending)
            // khi Hủy: nhà hàng hủy, khách hàng hủy, vận đơn hủy.... gọi sang với status = -1
            // khi nhà hàng chuyển trạng thái hoàn thành, gọi sang với status 1
            $offerId = \GDelivery\Libs\Config::MAS_OFFER_ID;
            $products = [
                'name' => 'G-Delivery_' . $order->get_id(),
                'sku' => 'GGG_SKU',
                'price' => $totals->totalPriceWithoutShipping - $totals->totalDiscount - $totals->totalCashVoucher,
                'category_id' => 1,
                'status_code' => $status, // -1 - Hủy; 0 - pending; 1 - hoàn thành
                'quantity' => 1,
            ];

            $sendData = [
                'offer_id' => $offerId,
                'signature' => \GDelivery\Libs\Config::MAS_OFFER_SIGNATURE,
                'transaction_id' => $order->get_id(),
                'transaction_time' => strtotime($order->get_date_created()) * 1000,
                'traffic_id' => $order->get_meta('mo_utm_data')->trafficId,
                'products' => [$products]
            ];

            $this->logger->info("Request Create MasOffer Order: OrderId: {$order->get_id()};  DebugInfo: ".\json_encode($sendData));

            $response = $this->httpClient->request(
                'post',
                $offerId.'/transactions',
                [
                    \GuzzleHttp\RequestOptions::JSON => $sendData
                ]
            );

            $jsonRes = \json_decode($response->getBody());

            if ($jsonRes && isset($jsonRes->meta->status_code)) {
                if ($jsonRes->meta->status_code == 1) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = $jsonRes->meta->status;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = $jsonRes->meta->external_message . '' . $jsonRes->meta->internal_message;
                    $res->result = isset($jsonRes->meta->trace_id) ? : $jsonRes->meta->trace_id;
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Có lỗi xãy ra khi kết nối tới MasOffer';
            }

        } catch (RequestException $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi tạo đơn trên masOffer. Guzzle Exception: '.$e->getMessage();
        } catch (\Exception $e) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi khi tạo đơn trên masOffer. Exception: '.$e->getMessage();
        }

        $this->logger->info("Request Create MasOffer Order: OrderId: {$order->get_id()};  Response: ".\json_encode($res));

        return $res;
    }
}