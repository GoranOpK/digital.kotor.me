<?php

namespace App\Mail;

use App\Models\Competition;

/**
 * Korisnički tekst imenovanja u Komisiju. Profil dolazi isključivo iz tipa Poziva.
 */
final class CommissionAppointmentMailCopy
{
    public const PROFILE_ZENSKO = 'zensko';

    public const PROFILE_OMLADINSKO = 'omladinsko';

    public const PROFILE_NEUTRAL = 'neutral';

    private function __construct(private readonly string $profileType)
    {
    }

    /**
     * @param  iterable<int|string|null>  $types
     */
    public static function fromCompetitionTypes(iterable $types): self
    {
        $unique = collect($types)
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->unique()
            ->values();

        if ($unique->count() === 1) {
            $type = $unique->first();
            if ($type === self::PROFILE_ZENSKO || $type === self::PROFILE_OMLADINSKO) {
                return new self($type);
            }
        }

        return new self(self::PROFILE_NEUTRAL);
    }

    public static function forAssignedCompetition(Competition $competition): self
    {
        return self::fromCompetitionTypes([$competition->type]);
    }

    public function isZensko(): bool
    {
        return $this->profileType === self::PROFILE_ZENSKO;
    }

    public function isOmladinsko(): bool
    {
        return $this->profileType === self::PROFILE_OMLADINSKO;
    }

    public function isNeutral(): bool
    {
        return $this->profileType === self::PROFILE_NEUTRAL;
    }

    public function memberAddedSubject(bool $substitute): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => $substitute
                ? 'Imenovanje za zamjenskog člana Komisije za podršku preduzetništvu mladih'
                : 'Imenovanje u Komisiju za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => $substitute
                ? 'Imenovanje za zamjenskog člana Komisije za podršku ženskom preduzetništvu'
                : 'Imenovanje u Komisiju za podršku ženskom preduzetništvu',
            default => $substitute
                ? 'Imenovanje za zamjenskog člana Komisije za sprovođenje konkursa'
                : 'Imenovanje u Komisiju za sprovođenje konkursa',
        };
    }

    public function assignedSubject(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'Imenovanje u Komisiju za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => 'Imenovanje u Komisiju za podršku ženskom preduzetništvu',
            default => 'Imenovanje u Komisiju za sprovođenje konkursa',
        };
    }

    public function htmlTitle(): string
    {
        return $this->assignedSubject();
    }

    public function memberAddedCommissionPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'Komisije za raspodjelu sredstava za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => 'Komisije za raspodjelu sredstava za podršku ženskom preduzetništvu',
            default => 'Komisije za sprovođenje konkursa',
        };
    }

    public function assignedCommissionPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => 'Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu',
            default => 'Komisije za sprovođenje konkursa',
        };
    }

    public function assignedCallPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'za sljedeći omladinski Poziv:',
            self::PROFILE_ZENSKO => 'za sljedeći konkurs:',
            default => 'za sljedeći Poziv:',
        };
    }

    public function loginSystemPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'sistem za podršku preduzetništvu mladih Opštine Kotor',
            self::PROFILE_ZENSKO => 'sistem za podršku ženskom preduzetništvu Opštine Kotor',
            default => 'sistem za sprovođenje konkursa Opštine Kotor',
        };
    }

    public function signatureSystemPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'Sistem za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => 'Sistem za podršku ženskom preduzetništvu',
            default => 'Sistem za sprovođenje konkursa',
        };
    }
}
