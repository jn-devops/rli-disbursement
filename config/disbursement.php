<?php

return [
    'server' => [
        'end-point' => env('NETBANK_DISBURSEMENT_ENDPOINT'),
        'token-end-point' => env('NETBANK_TOKEN_ENDPOINT'),
    ],
    'client' => [
        'id' => env('NETBANK_CLIENT_ID', ''),
        'secret' => env('NETBANK_CLIENT_SECRET', '')
    ],
    'source' => [
        'account_number' => env('NETBANK_SOURCE_ACCOUNT_NUMBER',''),
        'sender' => [
            'customer_id' => env('NETBANK_SENDER_CUSTOMER_ID',''),
            'address' => [
                "address1" => env('NETBANK_SENDER_ADDRESS_ADDRESS1'),
                "city" => env('NETBANK_SENDER_ADDRESS_CITY'),
                "country" => env('NETBANK_SENDER_ADDRESS_COUNTRY', 'PH'),
                "postal_code" => env('NETBANK_SENDER_ADDRESS_POSTAL_CODE'),
            ],
        ],
    ],
    'min' => env('MINIMUM_DISBURSEMENT', 1),
    'max' => env('MAXIMUM_DISBURSEMENT', 2),
    'settlement_rails' =>   explode(',', env('SETTLEMENT_RAILS', 'INSTAPAY,PESONET')),
    'user' => [
        'system' => [
            'name' => env('SYSTEM_NAME', 'RLI DevOps'),
            'email' => env('SYSTEM_EMAIL', 'devops@joy-nostalg.com'),
            'password' => env('SYSTEM_PASSWORD', '#Password1'),
            'password_confirmation' => env('SYSTEM_PASSWORD', '#Password1'),
        ],
    ],
    'wallet' => [
        'initial_deposit' => env('INITIAL_DEPOSIT', 1000 * 1000 * 1000),
    ],
];
