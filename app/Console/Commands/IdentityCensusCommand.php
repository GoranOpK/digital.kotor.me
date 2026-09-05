<?php

namespace App\Console\Commands;

use App\Identity\Census\IdentityCensusService;
use Illuminate\Console\Command;

class IdentityCensusCommand extends Command
{
    protected $signature = 'identity:census
                            {--aggregate= : Optional path for aggregate JSON}
                            {--rows= : Optional path for row diagnostic JSONL}';

    protected $description = 'Read-only identity census. Report only. Refuses the production environment.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('identity:census refuses execution in production.');

            return self::FAILURE;
        }

        $report = (new IdentityCensusService)->run();
        $aggregateJson = json_encode(
            $report->aggregateDocument(),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );

        $aggregatePath = $this->option('aggregate');
        if (is_string($aggregatePath) && $aggregatePath !== '') {
            file_put_contents($aggregatePath, $aggregateJson.PHP_EOL);
        } else {
            $this->output->writeln($aggregateJson);
        }

        $rowsPath = $this->option('rows');
        if (is_string($rowsPath) && $rowsPath !== '') {
            $handle = fopen($rowsPath, 'w');
            if ($handle === false) {
                $this->error('Unable to write row diagnostics.');

                return self::FAILURE;
            }

            foreach ($report->rows as $row) {
                fwrite(
                    $handle,
                    json_encode($row->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
                );
            }

            fclose($handle);
        }

        return self::SUCCESS;
    }
}
