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

    /*
     * Technical priority flush (TS-011 §16.2). Not a business rule.
     * Aggregation window holds Promjena na čekanju before flush.
     * Safety check cadence is the scheduler interval.
     */
    'priority_aggregation_minutes' => (int) env('NEWSLETTER_PRIORITY_AGGREGATION_MINUTES', 15),

    'priority_flush_interval_minutes' => (int) env('NEWSLETTER_PRIORITY_FLUSH_INTERVAL_MINUTES', 5),

    'priority_subject' => 'Kalendar kulture — važna izmjena',

    'priority_cycle_lock_key' => 'newsletter-priority-cycle',
];
