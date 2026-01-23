<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('path:show', function () {
    $this->info('Putanja do projekta:');
    $this->line(base_path());
})->purpose('Prikazuje putanju do projekta');

// Cron / scheduler: koristimo delete-expired-documents.php u root-u + Plesk "Run a PHP script"
// (Schedule:: nije korišćen jer Plesk Scheduled Tasks ne pokreće php artisan schedule:run)
