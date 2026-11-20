<?php

namespace App\Services\Competitions;

use RuntimeException;

class MmspTestPreviewRemovalException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
