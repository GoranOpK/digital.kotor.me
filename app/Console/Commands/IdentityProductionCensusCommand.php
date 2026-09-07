<?php

namespace App\Console\Commands;

use App\Identity\Census\IdentityCensusProtectedOutputPath;
use App\Identity\Census\IdentityCensusRow;
use App\Identity\Census\IdentityCensusService;
use Illuminate\Console\Command;

class IdentityProductionCensusCommand extends Command
{
    protected $signature = 'identity:census-production
                            {--aggregate= : Required protected path for aggregate JSON}
                            {--rows= : Required protected path for streamed row JSONL}';

    protected $description = 'Production read-only identity census. Dedicated read-only connection. Report only.';

    public function handle(IdentityCensusService $service): int
    {
        if (! app()->environment('production')) {
            $this->error('identity:census-production requires the production environment.');

            return self::FAILURE;
        }

        if (config('identity.canonical_write') || config('identity.canonical_read')) {
            $this->error('identity:census-production refuses execution while canonical identity authority is enabled.');

            return self::FAILURE;
        }

        $connectionError = $this->dedicatedConnectionError();
        if ($connectionError !== null) {
            $this->error($connectionError);

            return self::FAILURE;
        }

        $aggregateOption = $this->option('aggregate');
        $rowsOption = $this->option('rows');
        if (! is_string($aggregateOption) || $aggregateOption === '' || ! is_string($rowsOption) || $rowsOption === '') {
            $this->error('identity:census-production requires --aggregate and --rows protected output paths.');

            return self::FAILURE;
        }

        try {
            $aggregatePath = IdentityCensusProtectedOutputPath::resolve($aggregateOption);
            $rowsPath = IdentityCensusProtectedOutputPath::resolve($rowsOption);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rowsHandle = fopen($rowsPath, 'w');
        if ($rowsHandle === false) {
            $this->error('Unable to open protected row output path.');

            return self::FAILURE;
        }

        try {
            $report = $service->runOnConnection(
                IdentityCensusService::READONLY_CONNECTION,
                static function (IdentityCensusRow $row) use ($rowsHandle): void {
                    fwrite(
                        $rowsHandle,
                        json_encode($row->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                    );
                }
            );
        } finally {
            fclose($rowsHandle);
        }

        $aggregateJson = json_encode(
            $report->aggregateDocument(),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
        file_put_contents($aggregatePath, $aggregateJson.PHP_EOL);

        $this->info('Read-only production census complete.');
        $this->line('row_count='.$report->metadata['row_count']);
        $this->line('aggregate='.$aggregatePath);
        $this->line('rows='.$rowsPath);

        return self::SUCCESS;
    }

    private function dedicatedConnectionError(): ?string
    {
        $name = IdentityCensusService::READONLY_CONNECTION;
        $config = config('database.connections.'.$name);
        if (! is_array($config)) {
            return 'Dedicated census read-only connection is not configured.';
        }

        foreach (['driver', 'host', 'database', 'username'] as $key) {
            $value = $config[$key] ?? null;
            if (! is_string($value) || trim($value) === '') {
                return 'Dedicated census read-only connection is incomplete.';
            }
        }

        return null;
    }
}
