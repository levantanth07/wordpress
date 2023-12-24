<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Category;
use GDelivery\Libs\Helper\Helper;
use GDelivery\Libs\Helper\Product;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class GPosService
{
    private $httpClient;

    private $logger;

    private $paymentHubService;

    private function addDiscount($params = [])
    {
        $requestId = \uniqid('', false);
        $startTimeRequest = microtime(true);

        $gPosIp = isset($params['gPosIp']) ? $params['gPosIp'] : '';
        $gPosPort = isset($params['gPosPort']) ? $params['gPosPort'] : '';
        $orderGuid = isset($params['orderGuid']) ? $params['orderGuid'] : '';
        $discountCode = isset($params['discountCode']) ? $params['discountCode'] : '';

        $res = new Result();
        if ($gPosIp && $gPosPort && $orderGuid && $discountCode) {

            $endpoint = "http://{$gPosIp}:{$gPosPort}/ggg/api/v2.0/order/addDiscount";

            try {
                $data = [
                    'guid' => '{'.$orderGuid.'}',
                    'rk-payment-code' => $discountCode,
                    'note' => "Add discount từ GDelivery.vn",
                ];

                if (isset($params['cardCode'])) {
                    $data['card-number'] = $params['cardCode'];
                }
                if (isset($params['cardCode'])) {
                    $data['card-holder'] = $params['customerName'];
                }
                if (isset($params['interfaceCode'])) {
                    $data['interface-code'] = $params['interfaceCode'];
                }

                $this->logger->info("Request Add Discount to GPOS; Request id {$requestId}; Endpoint: {$endpoint}; Data: ".\json_encode($data));

                $doIt = $this->httpClient->request(
                    'post',
                    $endpoint,
                    [
                        RequestOptions::JSON => $data,
                        RequestOptions::TIMEOUT => 30
                    ]
                );

                if ($doIt->getStatusCode() == 200) {
                    $strResponse = $doIt->getBody()->getContents();
                    $jsonRes = \json_decode($strResponse);
                    if ($jsonRes) {
                        if ($jsonRes->code == 200) {
                            $res->messageCode = Message::SUCCESS;
                            $res->message = 'Success';
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
                    $res->message = 'Fail to call GPos: Status: '.$doIt->getStatusCode();
                }

                $this->logger->info("Response Add Discount to GPOS; Request id {$requestId}; RESPONSE: {$doIt->getBody()}");
            } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = "Fail to call GPos. Guzzle: {$guzzleException->getMessage()}";
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call GPos: Exception: '.$e->getMessage();
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Need to define GPos and order info';
        }

        if ($res->messageCode === Message::SUCCESS) {
            $this->logger->info((microtime(true) - $startTimeRequest) . '||||Response Add Discount: RESPONSE: ' . \json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTimeRequest) . '||||Response Add Discount: RESPONSE: ' . \json_encode($res));
        }

        return $res;
    }

    private function addPrepay($params = [])
    {
        $requestId = \uniqid('', false);
        $startTimeRequest = microtime(true);

        $gPosIp = isset($params['gPosIp']) ? $params['gPosIp'] : '';
        $gPosPort = isset($params['gPosPort']) ? $params['gPosPort'] : '';
        $orderGuid = isset($params['orderGuid']) ? $params['orderGuid'] : '';
        $paymentCode = isset($params['paymentCode']) ? $params['paymentCode'] : '';
        $amount = isset($params['amount']) ? $params['amount'] : '';

        $res = new Result();
        if ($gPosIp && $gPosPort && $orderGuid && $paymentCode) {

            $endpoint = "http://{$gPosIp}:{$gPosPort}/ggg/api/v2.0/order/addPrepay";

            try {
                $data = [
                    'guid' => '{'.$orderGuid.'}',
                    'rk-payment-code' => $paymentCode,
                    'amount' => (float) $amount,
                    'note' => "Add Prepay từ Gdelivery.vn"
                ];

                $this->logger->info("Request Add Prepay to GPOS; Request id {$requestId}; Endpoint: {$endpoint}; Data: ".\json_encode($data));

                $doIt = $this->httpClient->request(
                    'post',
                    $endpoint,
                    [
                        RequestOptions::JSON => $data,
                        RequestOptions::TIMEOUT => 30
                    ]
                );

                if ($doIt->getStatusCode() == 200) {
                    $strResponse = $doIt->getBody()->getContents();
                    $jsonRes = \json_decode($strResponse);
                    if ($jsonRes) {
                        if ($jsonRes->code == 200) {
                            $res->messageCode = Message::SUCCESS;
                            $res->message = 'Success';
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
                    $res->message = 'Fail to call GPos: Status: '.$doIt->getStatusCode();
                }

                $this->logger->info("Response Add Prepay to GPOS; Request id {$requestId}; RESPONSE: {$doIt->getBody()}");
            } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = "Fail to call GPos. Guzzle: {$guzzleException->getMessage()}";
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to call GPos: Exception: '.$e->getMessage();
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Need to define GPos; RKPaymentCode and order info';
        }

        if ($res->messageCode === Message::SUCCESS) {
            $this->logger->info((microtime(true) - $startTimeRequest) . '||||Response Add Prepay: RESPONSE: ' . \json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTimeRequest) . '||||Response Add Prepay: RESPONSE: ' . \json_encode($res));
        }

        return $res;
    }

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->logger = new Logger('gpos-service');
        $this->logger->pushHandler(new StreamHandler(ABSPATH.'/logs/gpos/gpos-service-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $this->logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->paymentHubService = new PaymentHubService();
    }

    private function makeComboData(&$productItems)
    {
        $posCombo = $listCombo = [];
        /** @var \WC_Order_Item $oneItem */
        foreach ($productItems as $key => $oneItem) {
            $comboData = $oneItem->get_meta('comboData');
            if (empty($comboData)) {
                continue;
            }

            $comboLineItemId = $oneItem->get_meta('lineItemId');
            $listCombo[$comboLineItemId] = $oneItem;
            unset($productItems[$key]);

            if (!isset($comboData['items']) || empty($comboData['items'])) {
                continue;
            }
            $comboQuantity = $oneItem->get_quantity();
            $code = get_field('product_rk_code', $oneItem->get_data()['product_id']);
            $posComboItem = [
                'code' => $code,
                'quantity' => $comboQuantity,
                'components' => []
            ];
            foreach ($comboData['items'] as $combo) {
                foreach ($combo['productItems'] as $productItem) {
                    $code = get_field('product_rk_code', $productItem['productId']);
                    $posComboItem['components'][] = [
                        'code' => $code,
                        'quantity' => $productItem['quantity'],
                        'productType' => 'child-on-combo',
                        'note' => null,
                    ];
                }
            }
            $posCombo[] = $posComboItem;
        }
        if (empty($listCombo)) {
            return [];
        }
        /** @var \WC_Order_Item $oneItem */
        foreach ($productItems as $key => $oneItem) {
            if (in_array($oneItem->get_meta('parentLineItemId'), array_keys($listCombo))) { // Is combo child
                unset($productItems[$key]);
            }
        }
        return $posCombo;
    }

    /**
     * @param \WC_Order $order
     *
     * @return Result
     */
    public function createOrder($order)
    {
        $requestId = \uniqid('', false);
        $startTimeRequest = microtime(true);

        // prepare voucher
        $selectedVouchers = $order->get_meta('selected_vouchers');
        $listCashVouchers = [];
        $listDiscountVouchers = [];
        $listDiscountOnItemVouchers = [];
        $discountItemCodes = [];
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $oneVoucher) {
                if ($oneVoucher->type == 1) {
                    $listCashVouchers[] = $oneVoucher;
                } elseif ($oneVoucher->type == 3 || $oneVoucher->type == 5) {
                    $listDiscountOnItemVouchers[] = $oneVoucher;
                    $discountItemCodes = array_merge($discountItemCodes, $oneVoucher->selectedForRkItem);
                } else {
                    $listDiscountVouchers[] = $oneVoucher;
                }
            }
        }

        $res = new Result();
        $getRestaurant = Helper::getMerchant($order->get_meta('merchant_id'));
        if ($getRestaurant->messageCode == Message::SUCCESS) {
            $restaurant = $getRestaurant->result;

            // customer info
            $customerInfo = $order->get_meta('customer_info');

            // payment method
            $paymentMethod = $order->get_meta('payment_method');

            // customer invoice
            $customerInvoice = $order->get_meta('customer_invoice');

            // order total
            $orderTotals = \GDelivery\Libs\Helper\Helper::orderTotals($order);

            // pos
            $gPosIp = $restaurant->restaurant->gposIp;
            $gPosPort = $restaurant->restaurant->gposPort;
            if ($restaurant->restaurant->sapCompanyCode == '1000') {
                $discountGPPCode = Config::PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HN;
                $guestTypeCode = Config::POS_GUEST_TYPE;
            } elseif ($restaurant->restaurant->sapCompanyCode == '2000') {
                $discountGPPCode = Config::PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HCM;
                $guestTypeCode = Config::POS_GUEST_TYPE_HCM;
            } elseif ($restaurant->restaurant->sapCompanyCode == '2100') {
                $discountGPPCode = Config::PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HCM_SBU;
                $guestTypeCode = Config::POS_GUEST_TYPE_HCM;
            } else {
                $discountGPPCode = 0;
                $guestTypeCode = 0;
            }

            if ($gPosIp && $gPosPort) {
                $endpoint = "http://{$gPosIp}:{$gPosPort}/ggg/api/v2.0/order/createAndSubmitOrder";

                // todo for hoang.daoduy: hardcode logic để check xem, order này là sản phẩm bình thường hay là trường hợp icook HCM
                // nếu là icook HCM, sử dụng rkOrderCategoryCodeForIcook
                $listOrder = $order->get_items();
                $orderCategoryCode = '';
                if ($listOrder) {
                    $firstProduct = reset($listOrder);
                    $firstProductId = $firstProduct->get_data()['product_id'];
                    $isProductIcookHCM = Product::isProductIcookHCM($firstProductId, $restaurant);

                    if ($isProductIcookHCM) {
                        $orderCategoryCode = $restaurant->rkOrderCategoryCodeForIcook;
                    } else {
                        $orderCategoryCode = $restaurant->rkOrderCategoryCode;
                    }
                }

                $data = [
                    'order-category-code' => $orderCategoryCode,
                    'table-code' => $restaurant->rkTableCode,
                    'waiter-code' => $restaurant->rkWaiterCode,
                    'note' =>  'Create order from GDelivery.vn',
                    'request-id-from-delivery' => $order->get_id(),
                    'guest-type-code' => (string) $guestTypeCode,
                    "take-tax" => (isset($customerInvoice['info']) && $customerInvoice['info'] ? true : false),
                    "company-name" => ($customerInvoice['name'] ?? ''),
                    "tax-code" => ($customerInvoice['number'] ?? ''),
                    "address" => ($customerInvoice['address'] ?? ''),
                    "email" => ($customerInvoice['email'] ?? ''),
                    "phone-number" => ($customerInvoice['telephone'] ?? ''),
                ];

                // items
                $items = [];
                $listProducts = $order->get_items();
                if (!empty($posCombo = $this->makeComboData($listProducts))) {
                    $data['combos'] = $posCombo;
                }
                $categoryId = $order->get_meta('current_product_category_id');
                $brandId = get_field('product_category_brand_id', 'product_cat_' . $categoryId);
                $isProductIcook = false;
                if ($brandId == Config::BRAND_IDS['icook']) {
                    $isProductIcook = true;
                }
                foreach ($listProducts as $item) {
                    if ((isset($item->get_data()['variation_id']) && $item->get_data()['variation_id'])) {
                        $code = get_field('product_variation_rk_code', $item->get_data()['variation_id']);
                    } else {
                        $code = get_field('product_rk_code', $item->get_data()['product_id']);
                    }
                    if (in_array($code, $discountItemCodes)) {
                        $quantity = $item->get_quantity() - 1;
                        if ($quantity >= 1) {
                            // todo need to check if apply discount for multiple item
                            $items[] = [
                                'code' => $code,
                                'quantity' => $quantity
                            ];
                        }
                    } else {
                        $items[] = [
                            'code' => $code,
                            'quantity' => $item->get_quantity()
                        ];
                    }
                }

                // item shipping fee
                if ($order->get_shipping_total('number')) {
                    $shippingFeeQuantity = (int) $order->get_meta('shipping_price');
                    if ($restaurant->restaurant->regionName == 'Ha Noi') {
                        if ($isProductIcook) {
                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_ICOOK;
                        } else {
                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE;
                        }
                    } else {
                        if ($isProductIcook) {
                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_ICOOK_HCM;
                        } else {
                            $code = Config::POS_SHIPPING_FEE_ITEM_CODE_HCM;
                        }
                    }

                    $items[] = [
                        'code' => $code,
                        'quantity' => $shippingFeeQuantity
                    ];
                }

                // set to send data
                $data['dishs'] = $items;

                // discount item
                $discountDishes = [];
                foreach ($listDiscountOnItemVouchers as $one) {
                    if (isset($one->selectedForRkItem) && is_array($one->selectedForRkItem) && !empty($one->selectedForRkItem)) {
                        foreach ($one->selectedForRkItem as $rkCode) {
                            $discountDishes[] = [
                                'code' => $rkCode,
                                'quantity' => 1, // todo need to check if apply discount for multiple item
                                'discount-code' => $one->rkPaymentCode
                            ];
                        }
                    }
                }
                if ($discountDishes) {
                    $data['dishs-discount'] = $discountDishes;
                }

                try {
                    $this->logger->info("Request Create Order to GPOS: RequestId: {$requestId}; Endpoint: {$endpoint}; SendData: ".\json_encode($data));

                    $doIt = $this->httpClient->request(
                        'post',
                        $endpoint,
                        [
                            RequestOptions::JSON => $data,
                            RequestOptions::TIMEOUT => 30
                        ]
                    );

                    $strResponse = $doIt->getBody()->getContents();

                    $this->logger->info("RESPONSE Create Order to GPOS: RequestId: {$requestId}; RESPONSE: {$strResponse};");
                    if ($doIt->getStatusCode() == 200) {
                        $jsonRes = \json_decode($strResponse);
                        if ($jsonRes) {
                            if ($jsonRes->code == 200) {
                                // save order
                                $currentRkOrder = $order->get_meta('rkOrder');
                                $rkOrder = $currentRkOrder ?: new \stdClass();
                                $rkOrder->guid = preg_replace('/{|}/', '', $jsonRes->data->guid);
                                $order->update_meta_data('rkOrder', $rkOrder);
                                $order->save();
                                $returnMessage = 'Thành công, vui lòng tiếp tục thao tác trên POS.';

                                // process selected voucher
                                $processedSelectedVouchers = [];
                                // selected voucher GPP for TGS
                                $selectedVoucherGPP = [];
                                $voucherGPPAmount = 0;
                                // add discount
                                foreach ($listDiscountVouchers as $oneVoucherDiscount) {
                                    $temp = clone $oneVoucherDiscount;
                                    if ($temp->partnerId != 14) {
                                        $addDiscount = $this->addDiscount(
                                            [
                                                'gPosIp' => $gPosIp,
                                                'gPosPort' => $gPosPort,
                                                'orderGuid' => $rkOrder->guid,
                                                'discountCode' => $oneVoucherDiscount->rkPaymentCode
                                            ]
                                        );

                                        if ($addDiscount->messageCode != Message::SUCCESS) {
                                            $returnMessage .= "|||| Sử dụng voucher {$oneVoucherDiscount->code} LỖI: {$addDiscount->message}";
                                            $temp->processOnPos = false;
                                        } else {
                                            $temp->processOnPos = true;
                                        }
                                    } else {
                                        $voucherGPPAmount += $oneVoucherDiscount->denominationValue;
                                        $selectedVoucherGPP[] = [
                                            'code' => $oneVoucherDiscount->code,
                                            'partnerId' => $oneVoucherDiscount->partnerId,
                                            'amount' => $oneVoucherDiscount->denominationValue
                                        ];
                                        $temp->processOnPos = true;
                                    }

                                    $processedSelectedVouchers[] = $temp;
                                } // end foreach list discount voucher

                                // init transaction on payment hub
                                $createPaymentHubTxParams = [
                                    'restaurantCode' => $restaurant->restaurant->code,
                                    'orderGuid' => $rkOrder->guid,
                                    'selectedVouchers' => $selectedVoucherGPP,
                                    'tgsSelectedVoucherAmount' => $voucherGPPAmount,
                                    'clmCustomerNumber' => $customerInfo->customerNumber,
                                    'clmCardNo' => $customerInfo->gppMagneticNumber
                                ];

                                // in case TGS, no-need to confirm payment
                                // we add selected vouchers and wallets in transaction when create tx in payment hub
                                if ($paymentMethod == Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME) {
                                    $createPaymentHubTxParams['selectedWalletAccounts'] = [
                                        [
                                            'name' => Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME,
                                            'amount' => $orderTotals->totalPaySum
                                        ]
                                    ];
                                } elseif ($voucherGPPAmount && $selectedVouchers) {
                                    $createPaymentHubTxParams['tgsSelectedVoucherAmount'] = $voucherGPPAmount;
                                    $createPaymentHubTxParams['selectedVouchers'] = $selectedVoucherGPP;
                                }

                                $createPaymentHubTx = $this->paymentHubService->createTransaction($createPaymentHubTxParams);
                                if ($createPaymentHubTx->messageCode == Message::SUCCESS) {
                                    $order->update_meta_data('paymenthub_transaction_id', $createPaymentHubTx->result->id);

                                    // process coupon GPP and indicate customer clm except yutang
                                    if ($restaurant->restaurant->brandId != 32) {

                                        // trigger add discount GPP with farcard interface to pos
                                        $addDiscount = $this->addDiscount(
                                            [
                                                'gPosIp' => $gPosIp,
                                                'gPosPort' => $gPosPort,
                                                'orderGuid' => $rkOrder->guid,
                                                'discountCode' => $discountGPPCode,
                                                'interfaceCode' => Config::PAYMENT_HUB_FARCARD_INTERFACE_CODE,
                                                'cardCode' => $customerInfo->gppMagneticNumber,
                                                'customerName' => $customerInfo->fullName
                                            ]
                                        );
                                    }
                                }

                                // add prepay
                                // vouchers
                                foreach ($listCashVouchers as $oneVoucherCash) {
                                    $temp = clone $oneVoucherCash;
                                    $addPrepay = $this->addPrepay(
                                        [
                                            'gPosIp' => $gPosIp,
                                            'gPosPort' => $gPosPort,
                                            'orderGuid' => $rkOrder->guid,
                                            'paymentCode' => $oneVoucherCash->rkPaymentCode,
                                            'amount' => $oneVoucherCash->denominationValue
                                        ]
                                    );

                                    if ($addPrepay->messageCode != Message::SUCCESS) {
                                        $returnMessage .= "|||| Sử dụng voucher {$oneVoucherCash->code} LỖI: {$addPrepay->message}";
                                        $temp->processOnPos = false;
                                    } else {
                                        $temp->processOnPos = true;
                                    }

                                    $processedSelectedVouchers[] = $temp;
                                }

                                // update status for discount on item voucher
                                foreach ($listDiscountOnItemVouchers as $one) {
                                    $tem = clone $one;
                                    $tem->processOnPos = true;
                                    $processedSelectedVouchers[] = $tem;
                                }

                                // save selected voucher
                                $order->update_meta_data('selected_vouchers', $processedSelectedVouchers);

                                // payment
                                $paymentMethod = $order->get_meta('payment_method');
                                if ($paymentMethod != 'COD') {
                                    $paySum = $order->get_meta('total_pay_sum');
                                    if (!$paySum) {
                                        $paySum = $orderTotals->totalPaySum;
                                    }
                                    $rkPaymentCode = Helper::rkPaymentCode($paymentMethod);

                                    $addPrepay = $this->addPrepay(
                                        [
                                            'gPosIp' => $gPosIp,
                                            'gPosPort' => $gPosPort,
                                            'orderGuid' => $rkOrder->guid,
                                            'paymentCode' => $rkPaymentCode,
                                            'amount' => $paySum
                                        ]
                                    );

                                    if ($addPrepay->messageCode != Message::SUCCESS) {
                                        $returnMessage .= "|||| Thanh toán {$paymentMethod} LỖI: {$addPrepay->message}";
                                    }

                                    // update payment request
                                    $paymentRequestId = $order->get_meta('payment_request_id');
                                    $this->paymentHubService->updateRequestPayment($paymentRequestId, $rkOrder->guid);
                                }

                                // save order once more time
                                $order->save();

                                // return
                                $res->messageCode = Message::SUCCESS;
                                $res->message = $returnMessage;
                                $res->result = $jsonRes->data->guid;
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
                        $res->message = 'Fail to call GPos: Status: '.$doIt->getStatusCode();
                    }

                    $this->logger->info("Request Create Order to GPOS; Request id {$requestId}; RESPONSE: {$strResponse}");
                } catch (\GuzzleHttp\Exception\GuzzleException $guzzleException) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = "Fail to call GPos. Guzzle: {$guzzleException->getMessage()}";
                } catch (\Exception $e) {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to call GPos: Exception: '.$e->getMessage();
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Need to define GPos IP';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Lỗi khi lấy thông tin nhà hàng';
        }

        if ($res->messageCode === Message::SUCCESS) {
            $this->logger->info((microtime(true) - $startTimeRequest) . "||||RESPONSE Create Order to GPOS: Request id {$requestId}; RESPONSE: " . \json_encode($res));
        } else {
            $this->logger->error((microtime(true) - $startTimeRequest) . "||||RESPONSE Create Order to GPOS: Request id {$requestId}; RESPONSE: " . \json_encode($res));
        }

        return $res;
    }



} // end class
