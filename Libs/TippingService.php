<?php

namespace GDelivery\Libs;

use Abstraction\Object\Message;

class TippingService
{
	public static function getTipsByCurrency($currency = 'VND')
	{
		$paymentHubService = new PaymentHubService();
		$getTips = $paymentHubService->listTipping($currency);

		if ($getTips->messageCode == Message::SUCCESS) {
			return $getTips->result;
		}

		return [];
	}
} // end class

