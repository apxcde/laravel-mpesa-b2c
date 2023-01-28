# Laravel Package For Mpesa B2C

[![Latest Version on Packagist](https://img.shields.io/packagist/v/apxcde/laravel-mpesa-b2c.svg?style=flat-square)](https://packagist.org/packages/apxcde/laravel-mpesa-b2c)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/apxcde/laravel-mpesa-b2c/run-tests?label=tests)](https://github.com/apxcde/laravel-mpesa-b2c/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/apxcde/laravel-mpesa-b2c/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/apxcde/laravel-mpesa-b2c/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/apxcde/laravel-mpesa-b2c.svg?style=flat-square)](https://packagist.org/packages/apxcde/laravel-mpesa-b2c)

## Installation

You can install the package via composer:

```bash
composer require apxcde/laravel-mpesa-b2c
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-mpesa-b2c-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-mpesa-b2c-config"
```

This is the contents of the published config file:

```php
return [
    'env' => env('MPESA_ENV', 'sandbox'),

    'shortcode' => env('MPESA_SHORTCODE', '600980'),

    'key' => env('MPESA_KEY', 'IWBJnpHUSMqGLVU21qhxFOdfTOzGjH5a'),

    'secret' => env('MPESA_SECRET', 'uqUYObhDprZoWFnG'),

    'username' => env('MPESA_USERNAME', 'testapi'),

    'password' => env('MPESA_PASSWORD', 'Safaricom980!'),

    'results_url' => env('MPESA_RESULTS_URL', ''),

    'timeout_url' => env('MPESA_TIMEOUT_URL', ''),

    'generated_password' => env('MPESA_GENERATED_PASSWORD', 'UNPMpfrhSfSeqN566HAlAQYaIQMeLvpEPZ5SiUR5pJn4faGYBnye251wCLGR56B3uOtT39UmoSeHtFhIa3torjhkXsfESm5NvKhIIOnHKa5Ry3rzeVxL+ruZE2st80HCLsbsJUQmvJ8vbE+h+NamH4DJi7JFHrHAPJ06BPjZuQEYbd/Lei1q4sdmQg6c38ZAnPIrvvWWidqxWc+uspbjqC+Dcyy6o9uwkfCCYGkvLtA8n2FM8MZazh/wgVjBOSV/RMmnt/cZjqoAiUVTkW6FMac77w1ejhweN4khV9mhmZvjmfaFmYi54nXbLSOC8FvkyiJf8uecNSAyWb5G/IhpaQ=='),

    'party_public_name' => env('MPESA_PARTY_PUBLIC_NAME', '4'),
];
```

## Add Environment Variables

Set the variables below in your .env file.

```php
MPESA_ENV=live
MPESA_SHORTCODE=
MPESA_KEY=
MPESA_SECRET=
MPESA_USERNAME=""
MPESA_PASSWORD=""
MPESA_RESULTS_URL="https://app-name.com/api/result-url"
MPESA_TIMEOUT_URL="https://app-name.com/api/timeout-url"
MPESA_PARTY_PUBLIC_NAME=2
MPESA_COMPLETED_DATE=3
MPESA_UTILITY_AVAILABLE=4
MPESA_WORKING_AVAILABLE=5
MPESA_REGISTERED=6
MPESA_CHARGES_PAID_ACCOUNT=7
```

The Values below would probably remain the same on your environment file. These are used to determine the returning values from the 
MPESA API.

```php
MPESA_PARTY_PUBLIC_NAME=2
MPESA_COMPLETED_DATE=3
MPESA_UTILITY_AVAILABLE=4
MPESA_WORKING_AVAILABLE=5
MPESA_REGISTERED=6
MPESA_CHARGES_PAID_ACCOUNT=7
```

## Usage

```php
use Apxcde\LaravelMpesaB2c\MpesaB2C;
use Apxcde\LaravelMpesaB2c\Models\MpesaB2CTransaction;

MpesaB2C::init([]); // Initialize the Mpesa B2C API

MpesaB2C::send($phone_number, $amount, 'BusinessPayment', $remarks, null, function($response) use ($amount, $phone_number) { 
    if (array_key_exists('errorCode', $response)) {
        return [
            'state' => 'Failed',
            'error_code' => $response['errorCode'],
            'error_message' => $response['errorMessage']
        ];
    }
    
    if($response["ResponseCode"] != 0) { 
        return [ 'state' => 'Failed' ];
    }
    
    // You can save the transaction to your database here
    // You can modify the table by publishing the migration file and adding values like phone_number, account_number, etc
    // Saving the transaction to the database is important because MPESA responds to the results url (value in the .env file).
    MpesaB2CTransaction::create([
        'originator_conversation_id' => $response["OriginatorConversationID"],
        'conversation_id' => $response["ConversationID"],
        'description' => $response["ResponseDescription"],
        'transaction_amount' => $amount,
        'transaction_id' => $transaction->id,
    ]);
    
    return [
        'state' => 'Pending',
        'mpesa_transaction' => $mpesa_transaction,
        'description' => "Request to send ".money($amount)." to ". $phone_number . " received successfully \n"
    ];
});
```

In the controller of your results url, you can get the transaction by originator_conversation_id. Controller should look like this:

```php
use Apxcde\LaravelMpesaB2c\Models\MpesaB2CTransaction;
use Apxcde\LaravelMpesaB2c\MpesaB2C;

class MpesaController extends Controller
{
    public function resultsUrl(Request $request)
    {
        MpesaB2C::reconcile(function($request) {
            $Result = $request["Result"];
            $ResultCode = $Result["ResultCode"];
            $ResultDesc = $Result["ResultDesc"];
            $TransactionID = $Result["TransactionID"];
            $OriginatorConversationID = $Result["OriginatorConversationID"];
            
            $transaction = MpesaB2CTransaction::find($OriginatorConversationID);
            
            // Check if the transaction failed
            if($ResultCode != 0) {
                // Update the saved transaction
                $transaction->update([
                    'state' => 'Failed',
                    'description' => $ResultDesc,
                    'mpesa_transaction_id' => $TransactionID,
                ]);
                // Do something else here: Send an email, SMS, etc
                // Return so the function doesn't continue
                return null;
            }
            
            $ResultParameter = $Result["ResultParameters"]["ResultParameter"];
            $ReceiverPartyPublicName = $ResultParameter[config('mpesa-b2c.party_public_name')]["Value"];
            
            // Update the saved transaction because the B2C transaction was successful 
            $transaction->update([
                'state' => 'Accepted',
                'description' => $ResultDesc,
                'mpesa_transaction_id' => $TransactionID,
                'receiver_public_data' => $ReceiverPartyPublicName,
            ]);
            
            // You can do other things here: Send an email, SMS to the customer, etc
            // Also maybe update the customer's account in your database
        });
    }
}
```

```php

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [ApexCode](https://github.com/apxcde)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
