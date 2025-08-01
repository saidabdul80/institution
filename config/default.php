<?php

return [
    'password'=>"password",

    /*
    |--------------------------------------------------------------------------
    | Pagination Length
    |--------------------------------------------------------------------------
    |
    | This value determines how many items are shown per page in paginated
    | views. Adjust this as needed for your application's pagination needs.
    |
    */
    'pagination_length' => 50,

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the SMS settings including the base URL and default parameters.
    |
    */
    'sms_base_url' => 'https://v3.api.termii.com',
    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'to' => '',
        'from' => 'GIRS',
        'sms' => 'Hi there, testing Giras',
        'type' => 'plain',
        'channel' => 'dnd'
    ],
    'aws'=>[
        'bucket'=>env('AWS_BUCKET')
    ],
    "seamfix"=>[
        "base_url"=> 'https://api.verified.africa/sfx-verify/v3/id-service',
        "user_id"=>env('SEAMFIX_USER_ID'),
        "nin_api_key"=>env('NIN_API_KEY'),
        "cac_api_key"=>env('CAC_API_KEY'),
        "bvn_api_key"=>env('BVN_API_KEY'),
        "account_api_key"=>env('ACCOUNT_API_KEY'),
    ],
    "jtb"=>[
        "user"=>env('JTB_USER'),
        "pass"=>env('JTB_PASS'),
        "base_url"=>env('JTB_BASE_URL'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Portal URLs
    |--------------------------------------------------------------------------
    |
    | Define URLs for different portals based on the application environment.
    |
    */
    'portal' => [
        'backend_base_url'=> env('APP_ENV') == 'local' ? 'https://localhost:8000':'https://giras-app.irs.gm.gov.ng',
        'backend'=> env('APP_ENV') == 'local' ? 'https://localhost:8000/api':'https://giras-app.irs.gm.gov.ng/api',
        'domain' => env('APP_ENV') == 'local' ? 'http://localhost:5173/' : 'https://giras.irs.gm.gov.ng',
        'individual' => env('APP_ENV') == 'local' ? 'http://localhost:5173//individual' : 'https://giras.irs.gm.gov.ng/individual',
        'corporate' => env('APP_ENV') == 'local' ? 'http://localhost:5173//corporate' : 'https://giras.irs.gm.gov.ng/corporate',
        'agent' => env('APP_ENV') == 'local' ? 'http://localhost:5173//agent' : 'https://giras.irs.gm.gov.ng/agent',
        'vendor' => env('APP_ENV') == 'local' ? 'http://localhost:5173//vendor' : 'https://giras.irs.gm.gov.ng/vendor',
        'staff' => env('APP_ENV') == 'local' ? 'http://localhost:5173//staff' : 'https://giras.irs.gm.gov.ng/staff',
    ],
    "title"=>"Gombe Internal Revenue (GIRS)",
    "email"=>"info@mail.ng",
    "etranzact" =>[
        "name"=>"etranzact",
        "base_url" => env('ETRANSACT_BASE_URL'),
        "api_key" => env('ETRANSACT_API_KEY'),
        "privateKey" => env('ETRANSACT_SECRET_KEY'),
        "service_code" => env('ETRANSACT_SERVICE_CODE'),

    ],
    'banks' => [
        'uba' => [
            'api_url' => env('UBA_API_URL'),
            'username' => env('UBA_API_USERNAME'),
            'password' => env('UBA_API_PASSWORD'),
        ]
    ]
];
