<?php

namespace App\Mail;

use App\Models\Commission;
use App\Models\Competition;
use App\Services\Competitions\Profile\CompetitionProfileResolver;

/**
 * Korisnički tekst imenovanja u Komisiju. Profil dolazi isključivo iz tipa Poziva
 * (kanonska Komisija je kanonska od uspostavljanja, nezavisno od dodijeljenih Poziva).
 */
final class CommissionAppointmentMailCopy
{
    public const PROFILE_ZENSKO = 'zensko';

    public const PROFILE_OMLADINSKO = 'omladinsko';

    public const PROFILE_CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    public const PROFILE_NEUTRAL = 'neutral';

    public const CANONICAL_COMMISSION_TITLE = 'Komisija za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima';

    private const CANONICAL_COMMISSION_GENITIVE = 'Komisije za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima';

    private const CANONICAL_APPOINTMENT_SUBJECT = 'Imenovanje u Komisiju za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima';

    private const CANONICAL_REPLACEMENT_SUBJECT = 'Imenovanje na upražnjeno mjesto u Komisiji za sprovođenje javnog konkursa za podršku preduzetnicima i mikro, malim i srednjim preduzećima';

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
            if (in_array($type, [self::PROFILE_ZENSKO, self::PROFILE_OMLADINSKO, self::PROFILE_CANONICAL], true)) {
                return new self($type);
            }
        }

        return new self(self::PROFILE_NEUTRAL);
    }

    public static function forAssignedCompetition(Competition $competition): self
    {
        return self::fromCompetitionTypes([$competition->type]);
    }

    public static function forCommission(Commission $commission): self
    {
        return $commission->isCanonicalProfile()
            ? new self(self::PROFILE_CANONICAL)
            : self::fromCompetitionTypes($commission->competitions()->pluck('type'));
    }

    public function isZensko(): bool
    {
        return $this->profileType === self::PROFILE_ZENSKO;
    }

    public function isOmladinsko(): bool
    {
        return $this->profileType === self::PROFILE_OMLADINSKO;
    }

    public function isCanonical(): bool
    {
        return $this->profileType === self::PROFILE_CANONICAL;
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
            self::PROFILE_CANONICAL => $substitute
                ? self::CANONICAL_REPLACEMENT_SUBJECT
                : self::CANONICAL_APPOINTMENT_SUBJECT,
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
            self::PROFILE_CANONICAL => self::CANONICAL_APPOINTMENT_SUBJECT,
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
            self::PROFILE_CANONICAL => self::CANONICAL_COMMISSION_GENITIVE,
            default => 'Komisije za sprovođenje konkursa',
        };
    }

    public function assignedCommissionPhrase(): string
    {
        return match ($this->profileType) {
            self::PROFILE_OMLADINSKO => 'Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku preduzetništvu mladih',
            self::PROFILE_ZENSKO => 'Komisije za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu',
            self::PROFILE_CANONICAL => self::CANONICAL_COMMISSION_GENITIVE,
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
