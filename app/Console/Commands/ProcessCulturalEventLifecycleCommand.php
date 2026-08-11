<?php

namespace App\Console\Commands;

use App\Services\CulturalEventDomain\EventLifecycleMaintenance;
use Illuminate\Console\Command;

/**
 * Tanak Artisan ulaz za PO-AUTO-02 + automatsko arhiviranje.
 * Poslovna pravila su u EventLifecycleMaintenance / Lifecycle domenu.
 */
class ProcessCulturalEventLifecycleCommand extends Command
{
    protected $signature = 'cultural-calendar:process-event-lifecycle';

    protected $description = 'Završava istekla Planirana Održavanja i arhivira podobne Događaje (Kalendar kulture).';

    public function handle(EventLifecycleMaintenance $maintenance): int
    {
        $result = $maintenance->process();

        $this->info(sprintf(
            'Završeno Održavanja: %d (preskočeno: %d). Arhivirano Događaja: %d (preskočeno: %d). Arhivirano Manifestacija: %d (preskočeno: %d).',
            $result['finished'],
            $result['skipped_finish'],
            $result['archived'],
            $result['skipped_archive'],
            $result['manifestation_archived'] ?? 0,
            $result['skipped_manifestation_archive'] ?? 0
        ));

        return self::SUCCESS;
    }
}
