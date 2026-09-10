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
    | Eloquent persist of a non-empty users.jmb or physical_person_identities.jmb
    | requires it. key_id is operational/version tracking only and is not stored
    | in the digest.
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

    /*
    | Plaintext retirement write-contract. Default false = current production:
    | plaintext remains populated; encrypted/lookup still synchronize.
    | When true: new logical JMB persists encrypted (+ lookup where required)
    | and plaintext is stored NULL. Null plaintext is not a logical clear.
    | Do not enable in production until a separately approved retirement step.
    */
    'plaintext_retirement' => [
        'enabled' => filter_var(env('JMB_PLAINTEXT_RETIREMENT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
