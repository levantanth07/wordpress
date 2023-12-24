<?php

namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OrderService
{
	private $paymentHubService;

	public function __construct()
	{
		$this->paymentHubService = new PaymentHubService();
	}

	public static function getOrderDetail($orderId)
	{
		$gPos = new GPosService();
		$order = wc_get_order($orderId);

		return $gPos->orderDetailByGdeliveryOrder($order);
	}

	public function requestPayment($paymentMethod, $data = [])
	{
		$res = new Result();
		$orderId = $_COOKIE['orderId'];
		$selectedVouchers = OrderService::getSelectedVouchers();
		$order = wc_get_order($orderId);
		$processedVouchers = [];
		foreach ($selectedVouchers as $voucher) {
			if (!empty($voucher->utilizeTime)) {
				// Tiếp tục nếu voucher đã được utilize
				continue;
			}
			$doUtilize = $this->paymentHubService->utilizeVoucher(
				$voucher->code,
				$_COOKIE['restaurantCode'],
				$order
			);
			if ($doUtilize->messageCode == Message::SUCCESS) {
				$voucher->utilizeTime = date_i18n('Y-m-d H:i:s');
				if ($voucher->partnerId == 14) {
					$processedVouchers[] = $voucher;
					$listProcessingVouchers = get_option('processing_clm_vouchers', []);
					$listProcessingVouchers[] = $voucher->code;
					update_option('processing_clm_vouchers', $listProcessingVouchers);
				}
			} else {
				$res->messageCode = Message::GENERAL_ERROR;
				$res->message = 'Có lỗi khi sử dụng voucher: ' . $doUtilize->message;

				return $res;
			}
		}
		if (!empty($processedVouchers)) {
			// Update selected voucher nếu voucher đã được utilize
			$order->update_meta_data('selected_vouchers', $processedVouchers);
		}

		$tipping = OrderService::getTippingParams();
		$this->paymentHubService->doTipping($tipping);

		$rating = OrderService::getRatingParams();
		$this->paymentHubService->doRating($rating);

		$tax = OrderService::getTaxParams();
		$taxBookService = new TaxBookService();
		$taxBookService->save($tax);

		$partner = $paymentMethod;
		$amount = $data['amount'];
		$currentTipValue = $tipping['amount'] ?? 0;
		$getOrderDetail = OrderService::getOrderDetail($orderId);
		if ($getOrderDetail->messageCode == Message::SUCCESS) {
			$orderDetail = $getOrderDetail->result;
			if ($amount < $orderDetail->totalPaySum + $currentTipValue) {
				$res->messageCode = Message::GENERAL_ERROR;
				$res->message = 'Số tiền thanh toán không đúng';

				return $res;
			}

			if ($paymentMethod === 'GBIZ') {
				$userInfo = $_SESSION['customerInfo'];
				$doRequestPayment = $this->paymentHubService->paymentConfirm([
					'transactionId' => $_COOKIE['paymentHubTransactionId'],
					'customerNumber' => $userInfo->customerNumber,
					'isConfirmBizAccount' => true,
					'selectedWalletAccounts' => [
						'name' => 'BizAccount',
						'amount' => $amount,
					],
				]);
			} else {
				$doRequestPayment = $this->paymentHubService->requestPayment($partner, $amount);
			}

			if ($doRequestPayment->messageCode == Message::SUCCESS) {
				$res->messageCode = Message::SUCCESS;
				$res->message = 'Request payment thành công';
				$res->result = $doRequestPayment->result;
			} else {
				$res->messageCode = Message::GENERAL_ERROR;
				$res->message = $doRequestPayment->message;
			}
		} else {
			$res->messageCode = Message::GENERAL_ERROR;
			$res->message = 'Không lấy được chi tiết order';
		}

		return $res;
	}

	public function orderTotal($orderId)
	{
		$orderDetail = self::getOrderDetail($orderId)->result;
		$selectedVouchers = self::getSelectedVouchers();
		$totalDiscountBeforeTax = 0;
		$totalDiscountAfterTax = 0;
		if ($selectedVouchers) {
			foreach ($selectedVouchers as $selectedVoucher) {
				if ($selectedVoucher->type == 1) {
					$totalDiscountAfterTax += $selectedVoucher->denominationValue;
				} else {
					$totalDiscountBeforeTax += $selectedVoucher->denominationValue;
				}
			}
		}

		$currentTippingParams = self::getTippingParams();
		$currentTipValue = $currentTippingParams['amount'] ?? 0;
		$totalBeforeVAT = $orderDetail->totalPrice - $totalDiscountBeforeTax;
		$totalVAT = $orderDetail->totalVat;
		$totalNeedToPay = $totalBeforeVAT + $totalVAT + $currentTipValue - $totalDiscountAfterTax;

		$orderTotal = [
			'totalPrice' => $orderDetail->totalPrice,
			'totalDiscountBeforeTax' => $totalDiscountBeforeTax,
			'totalBeforeVAT' => $totalBeforeVAT,
			'totalVAT' => $totalVAT,
			'totalDiscountAfterTax' => $totalDiscountAfterTax,
			'totalNeedToPay' => $totalNeedToPay,
		];

		return $orderTotal;
	}

	public function checkPayment($requestId)
	{
		return $this->paymentHubService->checkPayment($requestId);
	}

	public static function setTippingParams($tippingParams)
	{
		$_SESSION[$_COOKIE['orderGuid']]['tippingParams'] = $tippingParams;
	}

	public static function getTippingParams()
	{
		return $_SESSION[$_COOKIE['orderGuid']]['tippingParams'] ?? [];
	}

	public static function setRatingParams($ratingParams)
	{
		$_SESSION[$_COOKIE['orderGuid']]['ratingParams'] = $ratingParams;
	}

	public static function getRatingParams()
	{
		return $_SESSION[$_COOKIE['orderGuid']]['ratingParams'] ?? [];
	}

	public static function setTaxParams($taxParams)
	{
		$_SESSION[$_COOKIE['orderGuid']]['taxParams'] = $taxParams;
	}

	public static function getTaxParams()
	{
		return $_SESSION[$_COOKIE['orderGuid']]['taxParams'] ?? [];
	}

	public static function setVoucherParams($voucherParams)
	{
		$_SESSION[$_COOKIE['orderGuid']]['voucherParams'] = $voucherParams;
	}

	public static function getVoucherParams()
	{
		return $_SESSION[$_COOKIE['orderGuid']]['voucherParams'] ?? [];
	}

	public static function setSelectedVouchers($vouchers)
	{
		$_SESSION[$_COOKIE['orderGuid']]['selectedVouchers'] = $vouchers;
	}

	public static function getSelectedVouchers()
	{
		if (isset($_SESSION[$_COOKIE['orderGuid']]['selectedVouchers'])) {
			return $_SESSION[$_COOKIE['orderGuid']]['selectedVouchers'];
		} else {
			return [];
		}
	}
} // end class

