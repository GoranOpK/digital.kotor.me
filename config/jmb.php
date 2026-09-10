<?php

return [
    /*
    | Dedicated reversible encryption for JMB/JMBG values.
    | Do not reuse APP_KEY / APP_PREVIOUS_KEYS. Application boot does not
    | require this key; the service fails closed when invoked without it.
    |
    | previous_keys: JSON object mapping key_id -> key, decrypt-only.
    | Example after v1 -> v2: {"v1":"base64:..."}
    */
    'encryption' => [
        'key' => env('JMB_ENCRYPTION_KEY'),
        'key_id' => env('JMB_ENCRYPTION_KEY_ID', 'v1'),
        'previous_keys' => env('JMB_ENCRYPTION_PREVIOUS_KEYS'),
        'cipher' => 'AES-256-GCM',
    ],

    /*
    | Dedicated HMAC lookup digest for future JMB uniqueness.
    | Do not reuse APP_KEY or JMB_ENCRYPTION_KEY. Application boot does not
    | require this key; digest() fails closed for a non-empty JMB without it.
    | key_id is operational/version tracking only and is not stored in the digest.
    */
    'lookup' => [
        'key' => env('JMB_LOOKUP_KEY'),
        'key_id' => env('JMB_LOOKUP_KEY_ID', 'v1'),
    ],

    /*
    | Phase B2 plaintext -> parallel encrypted column backfill.
    | Does not run automatically. Production execution is a separate PO-approved action.
    */
    'backfill' => [
        'chunk_default' => 100,
        'chunk_max' => 500,
    ],
];
