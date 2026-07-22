<?php

namespace App\Services;

final class PdfOptimizationResult
{
    public function __construct(
        public readonly int $originalSize,
        public readonly int $finalSize,
        public readonly bool $optimized,
        public readonly int $pageCount,
        public readonly string $tool,
        public readonly int $durationMs,
        public readonly ?string $error = null,
    ) {}

    public function ok(): bool
    {
        return $this->error === null;
    }
}
