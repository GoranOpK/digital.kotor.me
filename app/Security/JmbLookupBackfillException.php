<?php

namespace App\Security;

use RuntimeException;

final class JmbLookupBackfillException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly string $scope = '',
        public readonly string $table = '',
        public readonly ?int $rowId = null,
    ) {
        if ($table !== '' && $rowId !== null) {
            parent::__construct("JMB lookup backfill failed. table={$table} id={$rowId} reason={$reason}");

            return;
        }

        parent::__construct($reason);
    }
}
