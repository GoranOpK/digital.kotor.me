<?php

namespace App\Services\Competitions;

use RuntimeException;

class ArchivedTestCompetitionRemovalException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}
