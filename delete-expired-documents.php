<?php
/**
 * Skript za brisanje isteklih dokumenata (expires_at < danas)
 *
 * Pokreće Artisan komandu documents:delete-expired.
 * Namena: Plesk Scheduled Tasks → Run a PHP script.
 *
 * Primer: dnevno u 02:00
 * - Script path: digital.kotor.me/delete-expired-documents.php (kroz Plesk dijalog)
 * - Cron: 0 2 * * *
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$argv = $argv ?? [];
$dryRun = isset($argv[1]) && $argv[1] === '--dry-run';

$status = $kernel->call('documents:delete-expired', [
    '--dry-run' => $dryRun,
]);

exit($status);
