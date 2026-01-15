<?php
/**
 * Skript za ažuriranje stvarnog iskorišćenog prostora za korisnike
 * 
 * Pokreće se preko Plesk Scheduled Tasks:
 * /opt/plesk/php/8.3/bin/php /path/to/recalculate-storage.php
 * 
 * Opciono, može se proslediti user_id kao argument:
 * /opt/plesk/php/8.3/bin/php /path/to/recalculate-storage.php 7
 */

// Učitaj Laravel bootstrap
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Proveri da li je prosleđen user_id kao argument
$userId = isset($argv[1]) ? (int) $argv[1] : null;

$documentProcessor = app(\App\Services\DocumentProcessor::class);

if ($userId) {
    // Ažuriraj samo određenog korisnika
    echo "Ažuriranje prostora za korisnika ID: {$userId}\n";
    $result = $documentProcessor->recalculateUserStorage($userId);
    
    if ($result['updated']) {
        $oldMB = round($result['stored_size'] / 1024 / 1024, 2);
        $newMB = round($result['actual_size'] / 1024 / 1024, 2);
        echo "✓ Prostor ažuriran: {$oldMB} MB → {$newMB} MB\n";
    } else {
        $sizeMB = round($result['actual_size'] / 1024 / 1024, 2);
        echo "✓ Prostor je već tačan: {$sizeMB} MB\n";
    }
} else {
    // Ažuriraj sve korisnike
    echo "Ažuriranje prostora za sve korisnike...\n";
    $users = \App\Models\User::all();
    $updated = 0;
    $total = $users->count();
    
    foreach ($users as $user) {
        $result = $documentProcessor->recalculateUserStorage($user->id);
        if ($result['updated']) {
            $updated++;
            $oldMB = round($result['stored_size'] / 1024 / 1024, 2);
            $newMB = round($result['actual_size'] / 1024 / 1024, 2);
            echo "  Korisnik {$user->id} ({$user->email}): {$oldMB} MB → {$newMB} MB\n";
        }
    }
    
    echo "✓ Ažurirano {$updated} od {$total} korisnika\n";
}

echo "Završeno.\n";
