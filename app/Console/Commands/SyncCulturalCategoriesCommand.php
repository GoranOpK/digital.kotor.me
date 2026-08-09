<?php

namespace App\Console\Commands;

use App\Services\CulturalCategory\CanonicalCulturalCategorySync;
use Illuminate\Console\Command;

/**
 * Idempotentno uspostavljanje CAT-14 kanonskih kategorija (TS7-PO-07).
 * Ne pokreće se iz DatabaseSeeder — samo eksplicitno.
 */
class SyncCulturalCategoriesCommand extends Command
{
    protected $signature = 'cultural-categories:sync
                            {--dry-run : Prikaži odluke bez upisa u bazu}
                            {--reactivate-inactive : Reaktiviraj postojeći inactive kanonski zapis (samo status)}';

    protected $description = 'Uspostavlja 14 kanonskih CulturalCategory zapisa (CAT-14), idempotentno.';

    public function handle(CanonicalCulturalCategorySync $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reactivateInactive = (bool) $this->option('reactivate-inactive');

        if ($dryRun) {
            $this->warn('DRY-RUN: nema upisa u bazu.');
        }

        $result = $sync->sync($dryRun, $reactivateInactive);

        $this->line('');
        $this->info('CAT-14 sync rezime');
        $this->line(sprintf('canonical total: %d', $result['canonical_total']));
        $this->line(sprintf('created: %d', count($result['created'])));
        $this->line(sprintf('skipped existing active: %d', count($result['skipped'])));
        $this->line(sprintf('reactivated: %d', count($result['reactivated'])));
        $this->line(sprintf('inactive conflicts: %d', count($result['inactive_conflicts'])));
        $this->line(sprintf('active duplicate conflicts: %d', count($result['duplicate_active_conflicts'])));
        $this->line(sprintf(
            'final canonical active coverage: %d/%d',
            $result['coverage'],
            $result['canonical_total']
        ));

        $this->printNameList('Would create / created', $result['created']);
        $this->printNameList('Skipped (existing active)', $result['skipped']);
        $this->printNameList('Would reactivate / reactivated', $result['reactivated']);
        $this->printNameList('inactive_conflict', $result['inactive_conflicts']);
        $this->printNameList('duplicate_active_conflict', $result['duplicate_active_conflicts']);

        if (! $result['complete']) {
            $this->newLine();
            $this->warn('CAT-14 blocker NIJE zatvoren: kanonski aktivni katalog nije potpun / bez konflikata.');
            if ($result['inactive_conflicts'] !== []) {
                $this->warn('Za inactive konflikte koristite --reactivate-inactive nakon pregleda, ili riješite ručno u UI.');
            }
            if ($result['duplicate_active_conflicts'] !== []) {
                $this->warn('Aktivni duplikati zahtijevaju ručno čišćenje — komanda ne briše ni ne spaja zapise.');
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('CAT-14 uspostavljen: 14/14 aktivnih kanonskih kategorija.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $names
     */
    private function printNameList(string $label, array $names): void
    {
        if ($names === []) {
            return;
        }

        $this->newLine();
        $this->line($label.':');
        foreach ($names as $name) {
            $this->line('  - '.$name);
        }
    }
}
