<?php

namespace App\Services\CulturalActivity;

use App\Exceptions\CulturalActivityRecordException;
use App\Models\CulturalActivityRecord;
use App\Models\User;

final class CulturalActivityActor
{
    private function __construct(
        public readonly string $type,
        public readonly ?int $userId,
    ) {}

    public static function user(User $user): self
    {
        if ($user->id === null) {
            throw new CulturalActivityRecordException('User actor zahtijeva sačuvan nalog.');
        }

        return new self(CulturalActivityRecord::ACTOR_USER, (int) $user->id);
    }

    public static function system(): self
    {
        return new self(CulturalActivityRecord::ACTOR_SYSTEM, null);
    }
}
