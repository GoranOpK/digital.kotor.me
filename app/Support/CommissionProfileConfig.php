<?php

namespace App\Support;

use App\Models\Commission;
use App\Models\Competition;
use App\Services\Competitions\Profile\CompetitionProfileResolver;

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

    public const SESSION_COMPETITION_TYPE_UNIQUE = 'commission_sessions_competition_type_unique';

    public const SESSION_SECOND_DEADLINE_DAYS = 7;

    public const SESSION_SECOND_QUORUM_MESSAGE = 'Druga sjednica može se potvrditi samo uz prisustvo sva 3 aktivna člana Komisije, uključujući predsjednika.';

    public const SESSION_SECOND_INCOMPLETE_COMMISSION_MESSAGE = 'Druga sjednica može se potvrditi tek kada su sva tri mjesta Komisije formalno popunjena.';

    public const SESSION_SECOND_EXISTS_MESSAGE = 'Poziv već ima drugu sjednicu Komisije.';

    public const SESSION_SECOND_CONFLICT_MESSAGE = 'Druga sjednica za ovaj Poziv već postoji.';

    public const SESSION_SECOND_NOT_YOUTH_MESSAGE = 'Druga sjednica i usmeno predstavljanje dostupni su samo za omladinski Poziv.';

    public const SESSION_SECOND_REQUIRES_FIRST_MESSAGE = 'Druga sjednica može početi tek nakon potvrđene prve sjednice.';

    public const SESSION_SECOND_APPEAL_GATE_MESSAGE = 'Druga sjednica ne može početi dok postoji otvoren rok za prigovor ili neriješen prigovor.';

    public const ORAL_EXISTS_MESSAGE = 'Prijava već ima evidenciju usmenog predstavljanja.';

    public const ORAL_CONFLICT_MESSAGE = 'Evidencija usmenog predstavljanja za ovu prijavu već postoji.';

    public const ORAL_LOCKED_MESSAGE = 'Završena evidencija usmenog predstavljanja ne može se mijenjati.';

    public const ORAL_SESSION_NOT_CONFIRMED_MESSAGE = 'Usmeno predstavljanje može se završiti tek nakon potvrđene druge sjednice sa sva 3 člana.';

    public const ORAL_NOT_ELIGIBLE_MESSAGE = 'Usmeno predstavljanje nije dozvoljeno za ovu prijavu.';

    public const ORAL_ATTENDANCE_REQUIRED_MESSAGE = 'Mora biti evidentirano da je podnosilac prisustvovao ili da nije prisustvovao.';

    public const ORAL_HELD_AT_REQUIRED_MESSAGE = 'Stvarni termin usmenog predstavljanja je obavezan kada je podnosilac prisustvovao.';

    public const ORAL_HELD_AT_BEFORE_CONFIRM_MESSAGE = 'Stvarni termin i prisustvo podnosioca mogu se evidentirati tek nakon potvrđene druge sjednice.';

    public const SESSION_SECOND_REQUIRED_FOR_ORAL_MESSAGE = 'Usmeno predstavljanje zahtijeva evidentiranu drugu sjednicu.';

    public const CANONICAL_PROFILE_ASSIGNMENT_MESSAGE = 'Komisija jednog profila ne može biti dodijeljena Pozivu drugog profila (ženski, omladinski, kanonski MMSP).';

    public const CANONICAL_COMMISSION_REQUIRED_MESSAGE = 'Kanonski MMSP Poziv može koristiti samo Komisiju evidentiranu kao kanonsku.';

    public const CANONICAL_LEGACY_FORM_MESSAGE = 'Kanonska MMSP Komisija se ne formira kroz ženski/omladinski obrazac. Evidentira se kroz kanonsko evidentiranje imenovanja.';

    public const CANONICAL_SESSION_ROUTE_MESSAGE = 'Kanonska MMSP Komisija koristi kanonsku politiku sjednica, ne omladinski tok prve/druge sjednice.';

    /** @var list<string> */
    public const COMMISSION_PROFILE_TYPES = [
        'zensko',
        'omladinsko',
        CompetitionProfileResolver::TYPE_CANONICAL,
    ];

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
        public readonly ?int $ordinaryQuorum = null,
        public readonly ?int $fullDecisionQuorum = null,
        public readonly bool $requiresSemanticSeats = false,
        public readonly bool $enforcesDeclarations = false,
        public readonly bool $allowsRemoteAttendance = false,
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
            CompetitionProfileResolver::TYPE_CANONICAL => new self(
                type: CompetitionProfileResolver::TYPE_CANONICAL,
                providesCommission: true,
                seatCount: 3,
                allowedSeats: [1, 2, 3],
                firstSessionQuorum: 2,
                allMembersRequiredCount: 3,
                usesExplicitCanonicalSeats: true,
                usesMemberTypeCatalog: false,
                ordinaryQuorum: 2,
                fullDecisionQuorum: 3,
                requiresSemanticSeats: true,
                enforcesDeclarations: true,
                allowsRemoteAttendance: true,
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
            ->filter(fn ($type) => in_array($type, self::COMMISSION_PROFILE_TYPES, true))
            ->unique()
            ->values();

        return $knTypes->count() > 1 ? self::mixedProfileMessage($knTypes->all()) : null;
    }

    public static function conflictWithAssignedCompetitions(
        Commission $commission,
        string $targetType,
        ?int $excludingCompetitionId = null
    ): ?string {
        $canonical = CompetitionProfileResolver::TYPE_CANONICAL;

        if ($commission->isCanonicalProfile() && $targetType !== $canonical) {
            return self::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE;
        }

        if ($targetType === $canonical && ! $commission->isCanonicalProfile()) {
            return self::CANONICAL_COMMISSION_REQUIRED_MESSAGE;
        }

        if (! in_array($targetType, self::COMMISSION_PROFILE_TYPES, true)) {
            return null;
        }

        $commission->loadMissing('competitions');

        foreach ($commission->competitions as $assigned) {
            if ($excludingCompetitionId !== null && (int) $assigned->id === $excludingCompetitionId) {
                continue;
            }
            if (! in_array($assigned->type, self::COMMISSION_PROFILE_TYPES, true)) {
                continue;
            }
            if ($assigned->type !== $targetType) {
                return self::mixedProfileMessage([$assigned->type, $targetType]);
            }
        }

        return null;
    }

    /**
     * Bulk assignment of competitions to an existing Commission (edit form).
     *
     * @param  iterable<int|string>  $competitionIds
     */
    public static function assignmentProfileError(Commission $commission, iterable $competitionIds): ?string
    {
        $ids = collect($competitionIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $canonical = CompetitionProfileResolver::TYPE_CANONICAL;

        foreach (Competition::query()->whereIn('id', $ids)->pluck('type')->unique() as $type) {
            if ($commission->isCanonicalProfile() && $type !== $canonical) {
                return self::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE;
            }
            if ($type === $canonical && ! $commission->isCanonicalProfile()) {
                return self::CANONICAL_COMMISSION_REQUIRED_MESSAGE;
            }
        }

        return null;
    }

    /**
     * @param  list<string|null>  $types
     */
    private static function mixedProfileMessage(array $types): string
    {
        $types = array_values(array_unique(array_filter($types)));
        sort($types);

        return $types === ['omladinsko', 'zensko']
            ? self::MIXED_PROFILE_ASSIGNMENT_MESSAGE
            : self::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE;
    }
}
