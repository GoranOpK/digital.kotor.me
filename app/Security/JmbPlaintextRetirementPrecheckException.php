<?php

namespace App\Security;

use RuntimeException;

final class JmbPlaintextRetirementPrecheckException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
    ) {
        parent::__construct('JMB plaintext retirement precheck failed.');
    }
}
