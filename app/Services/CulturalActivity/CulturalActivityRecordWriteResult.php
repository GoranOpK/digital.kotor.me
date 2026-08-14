<?php

namespace App\Services\CulturalActivity;

use App\Models\CulturalActivityRecord;

final class CulturalActivityRecordWriteResult
{
    public const INSERTED = 'inserted';

    public const DUPLICATE = 'duplicate';

    public const MISMATCH = 'mismatch';

    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?CulturalActivityRecord $record = null,
        public readonly ?string $error = null,
    ) {}

    public static function inserted(CulturalActivityRecord $record): self
    {
        return new self(self::INSERTED, $record);
    }

    public static function duplicate(CulturalActivityRecord $record): self
    {
        return new self(self::DUPLICATE, $record);
    }

    public static function mismatch(CulturalActivityRecord $record): self
    {
        return new self(self::MISMATCH, $record);
    }

    public static function failed(string $error): self
    {
        return new self(self::FAILED, null, $error);
    }

    public function wasInserted(): bool
    {
        return $this->status === self::INSERTED;
    }

    public function alreadyExists(): bool
    {
        return $this->status === self::DUPLICATE || $this->status === self::MISMATCH;
    }

    public function wasFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
