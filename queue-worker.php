<?php

/**
 * Queue Worker Script za Plesk Scheduled Tasks
 *
 * Pokreće se jednom u minutu. Worker ostaje aktivan do ~55s i
 * provjerava queue približno svake sekunde (--sleep=1).
 * Non-blocking flock sprečava preklapanje dvije instance.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$lockDir = __DIR__.'/storage/framework';
if (! is_dir($lockDir) && ! @mkdir($lockDir, 0755, true) && ! is_dir($lockDir)) {
    fwrite(STDERR, "Queue worker: lock directory unavailable.\n");
    exit(1);
}

$lockPath = $lockDir.'/queue-worker.lock';
$lockHandle = @fopen($lockPath, 'c');

if ($lockHandle === false) {
    fwrite(STDERR, "Queue worker: could not open lock file.\n");
    exit(1);
}

if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fclose($lockHandle);
    exit(0);
}

$status = 0;

try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    $status = $kernel->call('queue:work', [
        '--sleep' => 1,
        '--tries' => 3,
        '--timeout' => 300,
        '--max-time' => 55,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'Queue worker error: '.$e->getMessage()."\n");
    $status = 1;
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}

exit((int) $status);
