<?php

namespace Apxcde\LaravelMpesaB2c;

class Service
{
    public static object $config;

    public static function init($configs): void
    {
        $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.($_SERVER['SERVER_NAME'] ?? '');

        $defaults = [
            "env" => config('mpesa-b2c.env'),
            "type" => 4,
            "shortcode" => config('mpesa-b2c.shortcode'),
            "key" => config('mpesa-b2c.key'),
            "secret" => config('mpesa-b2c.secret'),
            "username" => config('mpesa-b2c.username'),
            "password" => config('mpesa-b2c.password'),
            "results_url" => config('mpesa-b2c.results_url'),
            "timeout_url" => config('mpesa-b2c.timeout_url'),
        ];

        foreach ($defaults as $key => $value) {
            if (isset($configs[$key])) {
                $defaults[$key] = $configs[$key];
            }
        }

        self::$config = (object) $defaults;
    }

    public static function formatPhoneNumber($phone_number)
    {
        $phone_number = (str_starts_with($phone_number, '+')) ?
            str_replace('+', '', $phone_number) :
            $phone_number;

        $phone_number = (str_starts_with($phone_number, '0')) ?
            preg_replace('/^0/', '254', $phone_number) :
            $phone_number;

        return (str_starts_with($phone_number, '7')) ? "254{$phone_number}" : $phone_number;
    }

    public static function get($endpoint, $credentials = null): bool|string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$credentials]);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        return curl_exec($curl);
    }

    public static function token(): string
    {
        $endpoint = (self::$config->env == 'live')
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode(self::$config->key.':'.self::$config->secret);
        $response = self::get($endpoint, $credentials);
        $result = json_decode($response);

        return $result->access_token ?? '';
    }

    public static function post($endpoint, $data = []): bool|string
    {
        $token = self::token();
        $curl = curl_init();
        $data_string = json_encode($data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type:application/json',
                'Authorization:Bearer '.$token,
            ]
        );

        return curl_exec($curl);
    }

    public static function reconcile(callable $callback = null): array
    {
        $response = json_decode(file_get_contents('php://input'), true);

        if (is_null($callback)) {
            return [
                'ResultCode' => 0,
                'ResultDesc' => 'Service request successful',
            ];
        } else {
            return call_user_func_array($callback, [$response])
                ? [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Service request successful',
                ]
                : [
                    'ResultCode' => 1,
                    'ResultDesc' => 'Service request failed',
                ];
        }
    }

    public static function balance($command = 'AccountBalance', $remarks = 'Balance Query', $callback = null)
    {
        $env = self::$config->env;
        $plaintext = self::$config->password;
        $publicKey = file_get_contents(__DIR__."/certs/{$env}/cert.cer");

        openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        $password = ($env == 'live') ? base64_encode($encrypted) : config('mpesa.generated_password');

        $payload = [
            'CommandID' => $command,
            'PartyA' => self::$config->shortcode,
            'IdentifierType' => self::$config->type,
            'Initiator' => self::$config->username,
            'SecurityCredential' => $password,
            'Remarks' => $remarks,
            'QueueTimeOutURL' => self::$config->timeout_url,
            'ResultURL' => self::$config->results_url,
        ];

        $response = self::post('/accountbalance/v1/query', $payload);
        $result = json_decode($response, true);

        return is_null($callback)
            ? $result
            : $callback($result);
    }
}
