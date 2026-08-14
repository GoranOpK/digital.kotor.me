<?php

return [
    /*
     * Technical regular-cycle cadence (TS-011 §16.2). Not a business rule.
     */
    'regular_interval_hours' => (int) env('NEWSLETTER_REGULAR_INTERVAL_HOURS', 6),

    'first_include_subject' => 'Kalendar kulture — novi događaji',

    'from' => [
        'address' => env('NEWSLETTER_FROM_ADDRESS', 'noreply@kotor.me'),
        'name' => env('NEWSLETTER_FROM_NAME', 'Kalendar kulture'),
    ],

    'cycle_lock_key' => 'newsletter-regular-cycle',
];
