<?php

return [
    /*
    | Canonical identity capability gates (DK-TS-002 D15 Step 2).
    | Default OFF. Runtime must not treat canonical identity as authoritative
    | while these remain false. Turning them ON is not authorized in Step 2.
    */
    'canonical_read' => filter_var(env('IDENTITY_CANONICAL_READ', false), FILTER_VALIDATE_BOOLEAN),
    'canonical_write' => filter_var(env('IDENTITY_CANONICAL_WRITE', false), FILTER_VALIDATE_BOOLEAN),
];
