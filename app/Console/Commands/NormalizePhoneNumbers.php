<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\BusinessPlan;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class NormalizePhoneNumbers extends Command
{
    protected $signature = 'phones:normalize {--dry-run : Prikaži promjene bez upisa u bazu}';

    protected $description = 'Jednokratna normalizacija telefona (+382382... -> +382...) u business_plans, users i applications';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        $updated += $this->normalizeTable(
            BusinessPlan::query(),
            ['applicant_phone', 'company_phone'],
            'business_plans',
            $dryRun
        );

        $updated += $this->normalizeTable(
            User::query(),
            ['phone'],
            'users',
            $dryRun
        );

        $updated += $this->normalizeTable(
            Application::query(),
            ['physical_person_phone'],
            'applications',
            $dryRun
        );

        if ($dryRun) {
            $this->info("Dry-run završen. Pronađeno {$updated} polja za ispravku.");
        } else {
            $this->info("Normalizacija završena. Ažurirano {$updated} polja.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, string>  $columns
     */
    protected function normalizeTable($query, array $columns, string $label, bool $dryRun): int
    {
        $count = 0;

        $query->chunkById(200, function ($rows) use ($columns, $label, $dryRun, &$count) {
            foreach ($rows as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    $original = $row->{$column};
                    if ($original === null || $original === '') {
                        continue;
                    }

                    $normalized = PhoneNumber::normalize($original);
                    if ($normalized !== null && $normalized !== $original) {
                        $changes[$column] = $normalized;
                        $this->line("{$label} #{$row->id} {$column}: {$original} -> {$normalized}");
                    }
                }

                if ($changes === []) {
                    continue;
                }

                $count += count($changes);

                if (!$dryRun) {
                    $row->update($changes);
                }
            }
        });

        return $count;
    }
}
