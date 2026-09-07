<?php

namespace App\Identity\Runtime;

use RuntimeException;

final class IdentityUseGateException extends RuntimeException
{
    public function __construct(
        public readonly string $access,
        string $message,
    ) {
        parent::__construct($message);
    }
}
