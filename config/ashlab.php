<?php

declare(strict_types=1);

return [

    "payment_gateways" => [
        "remita" => [
            "testURL" => 'https://remitademo.net/remita/exapp/api/v1/send/api/',
            "liveURL" => 'https://login.remita.net/remita/exapp/api/v1/send/api/',
            "testUsername" => 'UHSU6ZIMAVXNZHXW',
            "testPassword" => 'K8JE73OFE508GMOW9VWLX5SLH5QG1PF2',
            "MerchantID" => '2547916',
            "ApiKey" => 'UHSU6ZIMAVXNZHXW',
        ],

        "flutterwave" => [
            "test_secret_key" => 'FLWSECK_TEST-3ad5bc1234e323c7dc8205ce70d1da09-X',
            "live_secret_key" => env('FLW_LIVE_KEY',''),
            "test_public_key" => 'FLWPUBK_TEST-4740846b2aab8ca30ede42674824c368-X',
            "live_public_key" => env('FLW_LIVE_KEY',''),
            "test_hashkey" =>'FLWSECK_TEST3195dcb9f7bf',
            "live_hashkey" => env('FLW_LIVE_KEY',''),
            'testUrl'=>'https://api.flutterwave.com/v3/payments',
            'liveUrl'=>'https://api.flutterwave.com/v3/payments',
            'redirect_url'=> env('BASE_URL'). "api/webhooks/flutterwave",
        ],

    ],

    "central_connection" => "mysql",

    "remita_rits" => [
        "username" => env('REMITA_RITS_USERNAME'),
        "password" => env('REMITA_RITS_PASSWORD'),
        "base_url" => env('REMITA_BASE_URL','https://remitademo.net/remita/exapp/api/v1/send/api/rpgsvc/v3/rpg'),
    ]

];
