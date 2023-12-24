<?php


namespace GDelivery\Libs\Helper;


class Payment
{
    public static $paymentMethod = [
        'vnpay_qr' => 'VNPAY',
        'zalopay_qr' => 'ZaloPay',
        'momo_qr' => 'MOMO',
        'vnpay_bank_online' => 'ATM',
        'vinid_qr' => 'VinID',
        'shopeepay_qr' => 'ShopeePay',
        'gbiz' => 'G-Business',
        'codpay' => 'COD',
    ];
}