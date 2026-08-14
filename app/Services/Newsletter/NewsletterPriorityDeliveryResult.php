<?php

namespace App\Services\Newsletter;

final class NewsletterPriorityDeliveryResult
{
    public const SENT = 'sent';

    public const SKIPPED_EMPTY = 'skipped_empty';

    public const SKIPPED_INELIGIBLE = 'skipped_ineligible';

    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly int $changesDelivered = 0,
        public readonly ?string $error = null,
    ) {}

    public function wasSent(): bool
    {
        return $this->status === self::SENT;
    }

    public function wasFailed(): bool
    {
        return $this->status === self::FAILED;
    }

    public function wasSkippedEmpty(): bool
    {
        return $this->status === self::SKIPPED_EMPTY;
    }
}
