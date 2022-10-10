<?php

namespace Apxcde\LaravelMpesaB2c;

class MpesaB2C extends Service
{
    public static function send($phone_number, int $amount, string $command, string $remarks, $occasion = '', $callback = null)
    {
        $env = parent::$config->env;
        $phone_number = ($env == "live") ? parent::formatPhoneNumber($phone_number) : "254708374149";
        $amount = ($amount / 100);

        $endpoint = ($env == 'live')
            ? 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $plaintext = parent::$config->password;
        $publicKey = file_get_contents(__DIR__."/certs/{$env}/cert.cer");

        openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        $password = ($env == "live") ? base64_encode($encrypted) : config('mpesa-b2c.generated_password');

        $curl_post_data = [
            "InitiatorName" => parent::$config->username,
            "SecurityCredential" => $password,
            "CommandID" => $command,
            "Amount" => $amount,
            "PartyA" => parent::$config->shortcode,
            "PartyB" => $phone_number,
            "Remarks" => $remarks,
            "QueueTimeOutURL" => parent::$config->timeout_url,
            "ResultURL" => parent::$config->results_url,
            "Occasion" => $occasion,
        ];

        $response = parent::post($endpoint, $curl_post_data);
        $result = json_decode($response, true);

        return is_null($callback)
            ? $result
            : \call_user_func_array($callback, [$result]);
    }
}
