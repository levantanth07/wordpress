<?php

namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPMailer\PHPMailer\Exception;

class Report
{
    private $httpClient;

    private $logger;

    public function __construct()
    {
        // Create httpClient
        $this->httpClient = new Client(
            [
                'base_uri' => Config::REPORT_API_BASE_URL,
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.Config::REPORT_API_KEY,
                ]
            ]
        );

        $this->logger = new Logger('report-api');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/report-api/report-'.date('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function saveOrder($data, $restaurant, $pickupAtRestaurant = null)
    {
        $requestId = \uniqid();
        $startTime = microtime(true);

        $res = new Result();

        // uri API
        $endPoint = "order/create";

        try {
            $dataReport = $this->dataRequest($data);
            // log send data
            $this->logger->info('Request Save Order; request id: '.$requestId.'; with data: '.\json_encode($dataReport));

            // Run httpClient API
            try {
                $doRequest = $this->httpClient->request(
                    'post',
                    $endPoint,
                    [
                        RequestOptions::JSON => $dataReport,
                        RequestOptions::TIMEOUT => 2
                    ]
                );

                $strResponse = $doRequest->getBody()->getContents();

                // logging
                $this->logger->info('Response Save Order; request id: '.$requestId.'; with data: '.$strResponse);

                if ($doRequest->getStatusCode() == 200) {
                        $res->messageCode = Message::SUCCESS;
                        $res->message = 'Thành công';
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi gọi api report; request id: '.$requestId.'; save: ' . $doRequest->getStatusCode();
                }
            } catch (GuzzleException $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'GuzzleException: ' . $e->getMessage();

                // logging
                $this->logger->info((microtime(true) - $startTime).'||||Response Save Order; request id: '.$requestId.'; exception: '.$e->getMessage());
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: ' . $e->getMessage();

                // logging
                $this->logger->info((microtime(true) - $startTime).'||||Response Save Order; request id: '.$requestId.'; exception: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: ' . $e->getMessage();

            // logging
            $this->logger->info((microtime(true) - $startTime).'||||Response Save Order; request id: '.$requestId.'; general exception: '.$e->getMessage());
        }

        return $res;
    }

    public function updateOrder($order)
    {
        $requestId = \uniqid();
        $startTime = microtime(true);
        $res = new Result();

        try {
            // uri API
            $endPoint = "order/update";

            $dataReport = $this->dataRequest($order);

            // logging
            $this->logger->info('Request Update Order; request id '.$requestId.'; with data: '.\json_encode($dataReport));
            $doRequest = $this->httpClient->request(
                'put',
                $endPoint,
                [
                    RequestOptions::JSON => $dataReport,
                    RequestOptions::TIMEOUT => 2
                ]
            );

            $strResponse = $doRequest->getBody()->getContents();

            // logging
            $this->logger->info((microtime(true) - $startTime).'||||Response Update Order; request id: '.$requestId.'; with data: '.$strResponse);

            if ($doRequest->getStatusCode() == 200) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = (microtime(true) - $startTime).'||||Lỗi khi gọi api report update: ' . $doRequest->getStatusCode();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = (microtime(true) - $startTime).'||||Exception: ' . $e->getMessage();

            // logging
            $this->logger->info((microtime(true) - $startTime).'||||Response Update Order; request id: '.$requestId.'; exception: '.$e->getMessage());
        }

        return $res;
    }

    public function export($typeReport, $param = [])
    {
        $res = new Result();

        // uri API
        $endPoint = "export/delivery/" . $typeReport;

        try {
            // Run httpClient API
            try {
                $doRequest = $this->httpClient->request(
                    'post',
                    $endPoint,
                    [
                        RequestOptions::FORM_PARAMS => $param
                    ]
                );
                $headerCsv = $doRequest->getHeader('content-disposition');
                $nameFileCsv = explode('=', $headerCsv[0]);

                if ($doRequest->getStatusCode() == 200) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $doRequest->getBody()->getContents();
                    $res->nameFileCsv = $nameFileCsv[1];
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Lỗi khi gọi api report export: ' . $doRequest->getStatusCode();
                }
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Exception: ' . $e->getMessage();
            }
        } catch (\Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Exception: ' . $e->getMessage();
        }

        return $res;
    }

    /**
     * @param \WC_Order $order
     * @param      $restaurant
     * @param null $pickupAtRestaurant
     *
     * @return array
     */
    private function dataRequest($order)
    {
        // params
        $params = [];
        $params['pos_transaction_id'] = $order->get_id();
        $params['order_time'] = $order->get_date_created()->date_i18n('Y-m-d H:i:s');
        $params['status'] = $order->get_status();

        // status time
        if ($order->get_status() == \GDelivery\Libs\Helper\Order::STATUS_TRANS_GOING) { // todo check business again, to define what status is shipment time: delivered or going
            $params['shipping_time'] = date_i18n("Y-m-d H:i:s"); //add data when status = trans going
        } elseif ($order->get_status() == \GDelivery\Libs\Helper\Order::STATUS_COMPLETED) {
            $params['complete_time'] = date_i18n("Y-m-d H:i:s"); //add data when status = completed and trans delivered
        } elseif ($order->get_status() == \GDelivery\Libs\Helper\Order::STATUS_CANCELLED) {
            $params['cancel_time'] = date_i18n("Y-m-d H:i:s"); //add data when status = cancelled and trans reject

            // cancel reason; due to just operator can cancel order, so reason is restaurant and operator note
            $params['reason_cancel'] = 'Restaurant note: '.$order->get_meta('restaurant_note').' |||| Operator note: '.$order->get_meta('operator_note');
        }

        // prepare payment -- todo later

        $payment_details = [];
        // push to params
        $params['payment_details'] = $payment_details; // array

        // prepare item
        $items = [];
        $arrayItems = [];
        if ( 0 < count( $order->get_items() ) ) {
            foreach ( $order->get_items() as $value ) {
                $productId = $value->get_data()['product_id'];
                $items['pt_item_id'] = $productId;
                $items['transaction_id'] = $order->get_id();
                $items['code'] = (isset($order->get_data()['variation_id']) && $order->get_data()['variation_id']) ? get_field('product_variation_rk_code', $order->get_data()['variation_id']) : get_field('product_rk_code', $productId);
                $items['name'] = $value->get_data()['name'];
                $items['quantity'] = $value->get_data()['quantity'];
                $items['price'] = get_field('_regular_price', $productId);
                $items['tax'] = (float) get_field('_regular_price', $productId) * Product::getTaxRateValue($productId);
                $items['paysum'] = $value->get_data()['total'];
                $items['parent_code'] = 1;
                array_push($arrayItems, $items);
            }
        }
        // push to params
        $params['items'] = $arrayItems; // array

        // discount if have -- todo later
        $discount_details = [];
        // push to param
        $params['discount_details'] = $discount_details; // array

        // vouchers if have -- todo later
        $selected_vouchers = [];
        // push to param
        $params['selected_vouchers'] = $selected_vouchers; // array

        // rating if have -- todo later
        $transaction_ratings = [];
        // push to param
        $params['transaction_ratings'] = $transaction_ratings; // array

        // customer info
        $params['clm_customer_name'] = $order->get_billing_first_name();
        $params['clm_customer_phone'] = $order->get_billing_phone();
        $params['clm_customer_email'] = $order->get_billing_email();
        $params['clm_customer_note'] = $order->get_customer_note();

        // process total
        $options = [];
        if ($order->get_meta('is_pickup_at_restaurant') == 1) {
            $options['pickupAtRestaurant'] = 1;
        }
        $totals = Helper::calculateOrderTotals($order, $options);
        $params['total_price'] = $params['sale'] = $totals->totalPrice;
        $params['total_discount'] = $totals->totalDiscount;
        $params['total_vat'] = $totals->totalTax;
        $params['total_bill_value'] = $totals->total;
        $params['total_pay_sum'] = $totals->totalPrice - $totals->totalDiscount;

        // shipping method
        if ($order->get_meta('is_pickup_at_restaurant') == 1) {
            $shippingMethod = 'Nhận tại nhà hàng';
        } else {
            $vendorTransport = $order->get_meta('vendor_transport');
            if ($vendorTransport == 'grab_express') {
                $shippingMethod = 'Grab giao';
            } else {
                $shippingMethod = 'Nhà hàng giao';
            }
        }
        // push to param
        $params['shipping_method'] = $shippingMethod;
        $params['shipping_fee'] = $order->get_meta('shipping_price');

        // delivery info
        $deliveryTime = explode('-', $order->get_meta('delivery_time'));
        $wantTimes = date_i18n('Y-m-d', strtotime(str_replace('/', '-', $order->get_meta('delivery_date')))) .' '. $deliveryTime[0];
        // push to param
        $params['want_time'] = $wantTimes;

        // logic for restaurant in report
        // restaurant_code is the first restaurant (received and process firstly)
        // restaurant_delivery_code is the last restaurant (after transfer and really process/delivery order)
        $restaurantHistories = $order->get_meta('restaurant_histories');
        if ($restaurantHistories) {
            if (\count($restaurantHistories) > 1) {
                // in case just have many restaurants in histories
                // first
                $firstRestaurant = $restaurantHistories[0];
                $params['restaurant_code'] = $firstRestaurant['restaurant']->restaurantCode;
                $params['restaurant_name'] = $firstRestaurant['restaurant']->name;

                // last
                $lastRestaurant = end($restaurantHistories);
                $params['restaurant_delivery_code'] = $lastRestaurant['restaurant']->restaurantCode;
                $params['restaurant_delivery_name'] = $lastRestaurant['restaurant']->name;
                $params['other_location_delivery_time'] = date_i18n('Y-m-d H:i:s');
            } else {
                // in case just have one restaurant in histories
                $firstRestaurant = $restaurantHistories[0];
                $params['restaurant_delivery_code'] = $params['restaurant_code'] = $firstRestaurant['restaurant']->restaurantCode;
                $params['restaurant_delivery_name'] = $params['restaurant_name'] = $firstRestaurant['restaurant']->name;
                $params['other_location_delivery_time'] = date_i18n('Y-m-d H:i:s');
            }
        } else {
            // in case order does not have restaurant activities, set to report the current restaurant of order
            $restaurant = $order->get_meta('restaurant_object'); // this is booking object
            $params['restaurant_code'] = $restaurant->code;
            $params['restaurant_name'] = $restaurant->name;
        }

        // delivery address
        $selectedAddress = $order->get_meta('customer_selected_address');
        $address = $selectedAddress->addressLine1.', '.$selectedAddress->wardName.', '.$selectedAddress->districtName.', '.$selectedAddress->provinceName;
        $params['delivery_location'] = $address;

        // status before cancelled - todo fix this stupid thing later; long term, save status activities
        if ($order->get_meta('care_status')) {
            $params['care_status'] = $order->get_meta('care_status');
        }

        // bill and check number
        $rkOrder = $order->get_meta('rkOrder');
        if ($rkOrder) {
            $params['check_number'] = $rkOrder->checkNumber;
            $params['bill_number'] = $rkOrder->billNumber;
            $params['order_guid'] = $rkOrder->guid;
        }

        // check payment online if have
        // todo this stuffs dont need process like there, payment also in payment detail, but this is ad-hoc for payment report
        if ($order->get_meta('payment_method') != 'COD') {
            $requestPayment = $order->get_meta('payment_request_object');
            if ($requestPayment && $order->get_meta('is_paid') == 1) {
                if ($requestPayment['partner']['rkPaymentCode'] == 991111) {
                    // vnpay
                    $params['vnpay_amount'] = $order->get_total('number');
                } elseif ($requestPayment['partner']['rkPaymentCode'] == 991112) {
                    // zalopay
                    $params['zalopay_amount'] = $order->get_total('number');
                } else {
                    // todo more payment method later
                }
            }
        }

        // utm for mkt
        $utmData = $order->get_meta('utm_data');
        if ($utmData) {
            $params['utm_source'] = isset($utmData->utmSource) ? $utmData->utmSource : '';
            $params['utm_medium'] = isset($utmData->utmMedium) ? $utmData->utmMedium : '';
            $params['utm_campaign'] = isset($utmData->utmCampaign) ? $utmData->utmCampaign : '';
            $params['utm_content'] = isset($utmData->utmContent) ? $utmData->utmContent : '';
            $params['utm_location'] = isset($utmData->utmLocation) ? $utmData->utmLocation : '';
            $params['utm_term'] = isset($utmData->utmTerm) ? $utmData->utmTerm : '';
        }

        // more params -- todo use them later

        // return data
        $dataReport = [];
        array_push($dataReport, $params);

        return $dataReport;
    }

} // end class
