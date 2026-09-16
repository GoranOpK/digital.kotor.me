<?php

namespace App\Support;

use App\Models\Commission;
use App\Models\Competition;

/**
 * Profilna konfiguracija Komisije. Ne čita status kataloga.
 */
final class CommissionProfileConfig
{
    public const MIXED_PROFILE_ASSIGNMENT_MESSAGE = 'Ista Komisija ne može biti dodijeljena Pozivima ženskog i omladinskog profila.';

    public const OMLADINSKO_INCOMPLETE_PUBLISH_WARNING = 'Komisija još nije formalno kompletirana. Administrativna provjera (M3) ne može početi dok sva tri mjesta nijesu popunjena.';

    public const INVALID_YOUTH_SEAT_MESSAGE = 'Komisija mladih može koristiti samo kanonska mjesta 1, 2 i 3.';

    public const DUPLICATE_SEAT_MESSAGE = 'Svako kanonsko mjesto Komisije može biti popunjeno samo jednom.';

    public const SESSION_QUORUM_MESSAGE = 'Prva sjednica može se potvrditi samo uz kvorum od najmanje dva prisutna člana.';

    public const SESSION_INCOMPLETE_COMMISSION_MESSAGE = 'Prva sjednica može se potvrditi tek kada su sva tri mjesta Komisije formalno popunjena.';

    public const SESSION_LOCKED_MESSAGE = 'Potvrđena sjednica i prisustvo ne mogu se mijenjati niti brisati.';

    public const SESSION_FIRST_EXISTS_MESSAGE = 'Poziv već ima prvu sjednicu Komisije.';

    public const SESSION_FIRST_CONFLICT_MESSAGE = 'Prva sjednica za ovaj Poziv već postoji.';

    public const SESSION_NOT_CHAIRMAN_MESSAGE = 'Samo predsjednik dodijeljene Komisije može evidentirati sjednicu i prisustvo.';

    private function __construct(
        public readonly ?string $type,
        public readonly bool $providesCommission,
        public readonly int $seatCount,
        /** @var list<int> */
        public readonly array $allowedSeats,
        public readonly ?int $firstSessionQuorum,
        public readonly int $allMembersRequiredCount,
        public readonly bool $usesExplicitCanonicalSeats,
        public readonly bool $usesMemberTypeCatalog,
    ) {}

    public static function for(?string $type): self
    {
        return match ($type) {
            'zensko' => new self(
                type: 'zensko',
                providesCommission: true,
                seatCount: 5,
                allowedSeats: [1, 2, 3, 4, 5],
                firstSessionQuorum: null,
                allMembersRequiredCount: 5,
                usesExplicitCanonicalSeats: false,
                usesMemberTypeCatalog: true,
            ),
            'omladinsko' => new self(
                type: 'omladinsko',
                providesCommission: true,
                seatCount: 3,
                allowedSeats: [1, 2, 3],
                firstSessionQuorum: 2,
                allMembersRequiredCount: 3,
                usesExplicitCanonicalSeats: true,
                usesMemberTypeCatalog: false,
            ),
            default => new self(
                type: $type,
                providesCommission: false,
                seatCount: 0,
                allowedSeats: [],
                firstSessionQuorum: null,
                allMembersRequiredCount: 0,
                usesExplicitCanonicalSeats: false,
                usesMemberTypeCatalog: false,
            ),
        };
    }

    public function allowsSeat(int $seat): bool
    {
        return in_array($seat, $this->allowedSeats, true);
    }

    /**
     * @param  iterable<int|string>  $competitionIds
     */
    public static function homogeneousCompetitionTypes(iterable $competitionIds): ?string
    {
        $ids = collect($competitionIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $knTypes = Competition::query()
            ->whereIn('id', $ids)
            ->pluck('type')
            ->filter(fn ($type) => in_array($type, ['zensko', 'omladinsko'], true))
            ->unique()
            ->values();

        return $knTypes->count() > 1 ? self::MIXED_PROFILE_ASSIGNMENT_MESSAGE : null;
    }

    public static function conflictWithAssignedCompetitions(
        Commission $commission,
        string $targetType,
        ?int $excludingCompetitionId = null
    ): ?string {
        if (! in_array($targetType, ['zensko', 'omladinsko'], true)) {
            return null;
        }

        $commission->loadMissing('competitions');

        foreach ($commission->competitions as $assigned) {
            if ($excludingCompetitionId !== null && (int) $assigned->id === $excludingCompetitionId) {
                continue;
            }
            if (! in_array($assigned->type, ['zensko', 'omladinsko'], true)) {
                continue;
            }
            if ($assigned->type !== $targetType) {
                return self::MIXED_PROFILE_ASSIGNMENT_MESSAGE;
            }
        }

        return null;
    }
}
