<?php

$isProduction = env('APP_ENV', 'production') === 'production';

return [
    /*
    | Provider driver. Production must set this explicitly; empty = fail closed.
    | Local/testing default is fake. Bankart is not implemented (F6B blocked).
    */
    'gateway' => env('PAYMENT_GATEWAY', $isProduction ? null : 'fake'),

    /*
    | Adapter-level timeout placeholders. Core does not perform HTTP.
    | Future provider adapters may read these. Not Bankart SLA values.
    */
    'timeouts' => [
        'connect' => (int) env('PAYMENT_GATEWAY_CONNECT_TIMEOUT', 5),
        'request' => (int) env('PAYMENT_GATEWAY_REQUEST_TIMEOUT', 15),
    ],

    'fake' => [
        'enabled' => filter_var(
            env('PAYMENT_FAKE_ENABLED', $isProduction ? 'false' : 'true'),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],
];
