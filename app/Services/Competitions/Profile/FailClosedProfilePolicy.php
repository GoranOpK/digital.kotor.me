<?php

namespace App\Services\Competitions\Profile;

final class FailClosedProfilePolicy extends CompetitionProfilePolicy
{
    public function __construct(private readonly ?string $rawType)
    {
    }

    public function key(): string
    {
        return $this->rawType === CompetitionProfileResolver::TYPE_OTHER
            ? CompetitionProfileResolver::TYPE_OTHER
            : '';
    }

    public function rawType(): ?string
    {
        return $this->rawType;
    }

    public function displayName(): string
    {
        return $this->key() === CompetitionProfileResolver::TYPE_OTHER
            ? 'Ostalo'
            : 'Nepoznat profil';
    }

    public function isFailClosed(): bool
    {
        return true;
    }

    public function labels(): array
    {
        return [];
    }
}
