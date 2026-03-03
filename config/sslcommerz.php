<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSLCommerz Store Credentials
    |--------------------------------------------------------------------------
    */
    'store_id'   => env('SSLCZ_STORE_ID', ''),
    'store_pass' => env('SSLCZ_STORE_PASSWD', ''),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    | true  = sandbox (testing)
    | false = live (production)
    */
    'testmode'   => env('SSLCZ_TESTMODE', true),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'apiDomain'      => env('SSLCZ_TESTMODE', true)
        ? 'https://sandbox.sslcommerz.com'
        : 'https://securepay.sslcommerz.com',

    'apiUrl'         => '/gwprocess/v4/api.php',
    'validationUrl'  => '/validator/api/validationserverAPI.php',

    /*
    |--------------------------------------------------------------------------
    | Success / Fail / Cancel URLs
    |--------------------------------------------------------------------------
    */
    'success_url' => env('APP_URL') . '/billing/sslcommerz/success',
    'fail_url'    => env('APP_URL') . '/billing/sslcommerz/fail',
    'cancel_url'  => env('APP_URL') . '/billing/sslcommerz/cancel',

    /*
    |--------------------------------------------------------------------------
    | IPN (Instant Payment Notification)
    |--------------------------------------------------------------------------
    */
    'ipn_url'     => env('APP_URL') . '/billing/sslcommerz/ipn',
];