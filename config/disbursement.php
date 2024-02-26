<?php

return [
    'server' => [
        'end-point' => env('GATEWAY_ENDPOINT'),
    ],
    'source' => [
        'account_number' => env('SOURCE_ACCOUNT_NUMBER'),
        'sender' => [
            'customer_id' => env('SENDER_CUSTOMER_ID'),
            'address' => [
                "address1" => env('SENDER_ADDRESS_ADDRESS1'),
                "city" => env('SENDER_ADDRESS_CITY'),
                "country" => env('SENDER_ADDRESS_COUNTRY', 'PH'),
                "postal_code" => env('SENDER_ADDRESS_POSTAL_CODE'),
            ],
        ],
    ],
    'min' => env('MINIMUM_DISBURSEMENT', 1),
    'max' => env('MAXIMUM_DISBURSEMENT', 2),
    'settlement_rails' =>   explode(',', env('SETTLEMENT_RAILS', 'INSTAPAY,PESONET')),
];
