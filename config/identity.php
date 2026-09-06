<?php

return [
    /*
    | Canonical identity capability gates (DK-TS-002 D15).
    | Default OFF. Deploying this file must not switch authority.
    */
    'canonical_read' => filter_var(env('IDENTITY_CANONICAL_READ', false), FILTER_VALIDATE_BOOLEAN),
    'canonical_write' => filter_var(env('IDENTITY_CANONICAL_WRITE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Targeted identity-write freeze (Step 8 no-lost-update).
    | Default OFF so current production registration/profile remain available.
    */
    'identity_write_freeze' => filter_var(env('IDENTITY_WRITE_FREEZE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Durable EP identity-flow enable. Independent of ep_settings and table presence.
    | Default OFF / SAFE: admin new_payments_enabled cannot bypass this.
    */
    'ep_identity_flows' => filter_var(env('IDENTITY_EP_IDENTITY_FLOWS', false), FILTER_VALIDATE_BOOLEAN),
];
