<?php
/**
 * Jednokratna normalizacija telefona (+382382... -> +382...).
 *
 * Pokreće Artisan komandu phones:normalize.
 * Namena: Plesk Scheduled Tasks → Run a PHP script (ako artisan direktno ne radi).
 *
 * Primjer:
 * - Script path: digital.kotor.me/normalize-phones.php
 * - Argument: --dry-run (opciono, samo pregled)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$argv = $argv ?? [];
$dryRun = in_array('--dry-run', $argv, true);

$status = $kernel->call('phones:normalize', [
    '--dry-run' => $dryRun,
]);

exit($status);
