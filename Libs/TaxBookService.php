<?php

namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class TaxBookService
{
	private $tgsService;

	public function __construct()
	{
		$this->tgsService = new TGSService($_SESSION['customerAuthentication'] ?? null);
	}

	public function listTax()
	{
		$taxes = $this->tgsService->getTaxBook();

		if ($taxes->messageCode == Message::SUCCESS) {
			return $taxes->result;
		} else {
			return [];
		}
	}

	public function update($id, $data)
	{
		$res = new Result();
		$taxes = $this->tgsService->updateTax($id, $data);

		if ($taxes->messageCode == Message::SUCCESS) {
			$res->messageCode = Message::SUCCESS;
			$res->message = 'Cập nhật thông tin thành công';
			$res->result = $taxes->result;
		} else {
			$res->messageCode = Message::GENERAL_ERROR;
			$res->message = 'Cập nhật thông tin không thành công';
		}

		return $res;
	}

	public function save($data)
	{
		$res = new Result();
		$taxes = $this->tgsService->saveTax($data);

		if ($taxes->messageCode == Message::SUCCESS) {
			$res->messageCode = Message::SUCCESS;
			$res->message = 'Lưu thông tin thành công';
			$res->result = $taxes->result;
		} else {
			$res->messageCode = Message::GENERAL_ERROR;
			$res->message = 'Lưu thông tin không thành công';
		}

		return $res;
	}
} // end class

