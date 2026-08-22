<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Payments\EpCanonicalCatalog;
use App\Services\Payments\EpCanonicalCatalogImporter;
use Illuminate\Console\Command;

/**
 * Explicit F11 17/41 catalog import. Not called from DatabaseSeeder or deploy.
 */
class ImportEpCanonicalCatalogCommand extends Command
{
    protected $signature = 'ep:import-canonical-catalog
                            {--actor-id= : User id written to ep_catalog_audits}
                            {--dry-run : Validate catalog definition without writing}';

    protected $description = 'Idempotentno učitava kanonskih 17 vrsta / 41 račun e-Plaćanja (neaktivno). Nije deploy hook.';

    public function handle(EpCanonicalCatalogImporter $importer): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('Refused: production import is not allowed.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            EpCanonicalCatalog::assertConsistent();
            $this->info('DRY-RUN: definition consistent (17 types / 41 accounts / #18 excluded). No writes.');

            return self::SUCCESS;
        }

        $actorId = (int) $this->option('actor-id');
        if ($actorId < 1) {
            $this->error('Required: --actor-id= of an existing user for catalog audit.');

            return self::FAILURE;
        }

        $actor = User::query()->find($actorId);
        if ($actor === null) {
            $this->error('Actor user not found.');

            return self::FAILURE;
        }

        $report = $importer->import($actor);

        $this->info('EP canonical catalog import');
        $this->line('types created: '.$report->typesCreated);
        $this->line('types skipped: '.$report->typesSkipped);
        $this->line('accounts created: '.$report->accountsCreated);
        $this->line('accounts skipped: '.$report->accountsSkipped);
        $this->line('type rules created: '.$report->typeRulesCreated);
        $this->line('account rules created: '.$report->accountRulesCreated);
        $this->line('conflicts: '.count($report->conflicts));

        foreach ($report->conflicts as $conflict) {
            $this->warn($conflict);
        }

        return $report->hasConflicts() ? self::FAILURE : self::SUCCESS;
    }
}
