<?php

/*
 * This file is part of the Laravel Paystack package.
 *
 * (c) Prosper Otemuyiwa <prosperotemuyiwa@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /**
     * Public Key From Paystack Dashboard
     *
     */
   
    'base_uri' => env('REMITA_BASE_URL2') ?? 'https://login.remita.net/remita/exapp/api/v1/send/api/echannelsvc/',

    'consumer_key' => env('REMITA_MERCHANT_ID'),




    'api_key' => env('REMITA_API_KEY'),
    'service_type' => env('REMITA_SERVICE_TYPE'),
    /**
     * Secret Key From Paystack Dashboard
     *
     */
    'api_base_url' => env('REMITA_BASE_URL'),

    'merchant_id' => env('REMITA_MERCHANT_ID'),
    'remita_verify'=> env('APP_ENV')=='local'?
                    "https://demo.remita.net/remita/exapp/api/v1/send/api/echannelsvc/".env('REMITA_MERCHANT_ID')."/{{rrr}}/{{apiHash}}/status.reg":
                    "https://login.remita.net/remita/exapp/api/v1/send/api/echannelsvc/"
];
