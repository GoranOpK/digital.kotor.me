<?php
/**
 * Queue Worker Script za Plesk Scheduled Tasks
 * 
 * Ovaj fajl se pokreće kroz Plesk Scheduled Tasks.
 * Putanja se bira kroz dijalog box u Plesk-u, tako da nema problema sa putanjom.
 */

// Učitaj Laravel bootstrap
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Pokreni queue worker
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('queue:work', [
    '--tries' => 3,
    '--timeout' => 300,
    '--stop-when-empty' => true,
]);

exit($status);

