<?php
namespace GDelivery\Libs;

class EmailService {

    public function __construct()
    {

    }

    public function send($data)
    {
        // call function wp_mail
        return wp_mail($data['to'], $data['subject'], $data['message'], $data['headers']);
    }
}