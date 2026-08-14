<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('path:show', function () {
    $this->info('Putanja do projekta:');
    $this->line(base_path());
})->purpose('Prikazuje putanju do projekta');

// Cron / scheduler: koristimo delete-expired-documents.php u root-u + Plesk "Run a PHP script"
// (Schedule:: nije korišćen jer Plesk Scheduled Tasks ne pokreće php artisan schedule:run)

// NL-04 — kanonski redovni first_include ciklus (tehnički interval, nije BM pravilo).
// Legacy weekly command ostaje na disku do cutover cleanup-a; NE zakazivati paralelno.
// Produkcijski Plesk invoker se ovdje NE mijenja.
$newsletterHours = max(1, (int) config('newsletter.regular_interval_hours', 6));
Schedule::command('cultural-calendar:send-newsletter')
    ->cron('0 */'.$newsletterHours.' * * *')
    ->withoutOverlapping()
    ->timezone(config('app.timezone', 'Europe/Belgrade'));

// NL-05 — prioritetni flush (tehnički interval, nije BM pravilo).
// Produkcijski Plesk invoker se ovdje NE mijenja.
$priorityFlushMinutes = max(1, (int) config('newsletter.priority_flush_interval_minutes', 5));
Schedule::command('cultural-calendar:send-newsletter-priority')
    ->cron('*/'.$priorityFlushMinutes.' * * * *')
    ->withoutOverlapping()
    ->timezone(config('app.timezone', 'Europe/Belgrade'));

// PO-AUTO-02 + automatsko arhiviranje: periodična provjera kandidata (poslovno vrijeme isteka ≠ interval).
Schedule::command('cultural-calendar:process-event-lifecycle')
    ->everyFifteenMinutes()
    ->timezone(config('app.timezone', 'Europe/Belgrade'));
