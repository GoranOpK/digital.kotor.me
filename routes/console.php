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

// Registruj CheckImageMagick komandu
Artisan::command('imagemagick:check', function () {
    $command = new \App\Console\Commands\CheckImageMagick();
    return $command->handle();
})->purpose('Proverava da li je ImageMagick instaliran i dostupan');
