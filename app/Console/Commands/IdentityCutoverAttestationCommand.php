<?php

namespace App\Console\Commands;

use App\Identity\Cutover\IdentityCutoverAttestation;
use Illuminate\Console\Command;

class IdentityCutoverAttestationCommand extends Command
{
    protected $signature = 'identity:cutover-attestation';

    protected $description = 'D15 Step 8 observational cutover attestation. Does not authorize cutover. Never sets cutover_ready.';

    public function handle(IdentityCutoverAttestation $attestation): int
    {
        $report = $attestation->report();

        $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        if ($report['cutover_ready'] === true || $report['population_boundary_protected'] === true) {
            $this->error('identity:cutover-attestation must not report cutover_ready or population_boundary_protected as true.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
