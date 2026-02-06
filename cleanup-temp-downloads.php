<?php
/**
 * Čišći privremene MEGA download fajlove (temp_downloads) starije od 5 minuta.
 * Pokreće Artisan komandu documents:cleanup-temp-downloads.
 * Namena: Plesk Scheduled Tasks → Run a PHP script (svakih 5–10 minuta).
 *
 * Cron: preporučeno svakih 5 min. V. PLESK_DELETE_EXPIRED_CRON.md.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$argv = $argv ?? [];
$dryRun = in_array('--dry-run', $argv, true);

$status = $kernel->call('documents:cleanup-temp-downloads', [
    '--minutes' => 5,
    '--dry-run' => $dryRun,
]);

exit($status);
