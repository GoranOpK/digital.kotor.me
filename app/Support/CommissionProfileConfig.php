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
    public const PROFILE_ZENSKO = 'zensko';

    public const PROFILE_OMLADINSKO = 'omladinsko';

    public const PROFILE_CANONICAL = CompetitionProfileResolver::TYPE_CANONICAL;

    /** @var list<string> */
    public const SUPPORTED_STORED_PROFILES = [
        self::PROFILE_ZENSKO,
        self::PROFILE_OMLADINSKO,
        CompetitionProfileResolver::TYPE_CANONICAL,
    ];

    public const MIXED_PROFILE_ASSIGNMENT_MESSAGE = 'Ista Komisija ne može biti dodijeljena Pozivima ženskog i omladinskog profila.';

    public const UNKNOWN_COMPETITION_ASSIGNMENT_MESSAGE = 'Izabrani Poziv ne postoji ili ne pripada izabranom skupu.';

    public const COMPETITION_REQUIRED_MESSAGE = 'Morate izabrati najmanje jedan konkretan Poziv.';

    public const UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE = 'Komisija se može povezati samo sa Pozivima jednog podržanog profila.';

    public const PROFILE_REQUIRED_MESSAGE = 'Morate izabrati vrstu konkursa: žensko preduzetništvo, podrška preduzetništvu mladih ili podrška mikro, malim i srednjim preduzećima.';

    public const INCOMPLETE_ZENSKO_CREATE_MESSAGE = 'Komisija ženskog profila mora pri kreiranju imati svih 5 redovnih članova.';

    public const INCOMPLETE_OMLADINSKO_CREATE_MESSAGE = 'Komisija mladih mora pri kreiranju imati sva 3 redovna člana.';

    public const INCOMPLETE_CANONICAL_CREATE_MESSAGE = 'Komisija za podršku mikro, malim i srednjim preduzećima mora pri kreiranju imati sva 3 redovna člana.';

    public const PROFILE_CHANGE_REJECTED_MESSAGE = 'Profil postojeće Komisije nije moguće promijeniti. Kreirajte novu Komisiju za izabrani tip konkursa.';

    public const UNDETERMINED_COMMISSION_PROFILE_MESSAGE = 'Profil postojeće Komisije nije utvrđen. Redovno uređivanje i povezivanje nijesu dostupni dok se Komisija posebno ne klasifikuje.';

    public const UNDETERMINED_COMMISSION_SHOW_MESSAGE = 'Profil Komisije nije utvrđen. Komisiju nije moguće mijenjati niti povezivati sa Pozivom dok se profil kontrolisano ne odredi.';

    public const COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE = 'Poziv se može povezati samo sa Komisijom istog podržanog profila.';

    public const MIXED_COMPETITION_TYPES_MESSAGE = 'Komisija ne može obuhvatiti Pozive različitih tipova.';

    public const COMPETITION_ALREADY_ASSIGNED_MESSAGE = 'Poziv koji već ima Komisiju ne može se ponovo dodijeliti.';

    public const YOUTH_PRESIDENT_COUNT_MESSAGE = 'Komisija mladih mora imati tačno jednog predsjednika.';

    public const CANONICAL_PRESIDENT_COUNT_MESSAGE = 'Komisija za podršku mikro, malim i srednjim preduzećima mora imati tačno jednog predsjednika.';

    public const YOUTH_MEMBER_TYPE_REJECTED_MESSAGE = 'Komisija mladih ne koristi kategorije članova ženskog profila.';

    public const CANONICAL_MEMBER_TYPE_REJECTED_MESSAGE = 'Komisija za podršku mikro, malim i srednjim preduzećima ne koristi kategorije članova ženskog profila.';

    public const OMLADINSKO_INCOMPLETE_PUBLISH_WARNING = 'Komisija još nije formalno kompletirana. Administrativna provjera (M3) ne može početi dok sva tri mjesta nijesu popunjena.';

    public const INVALID_YOUTH_SEAT_MESSAGE = 'Komisija mladih može koristiti samo kanonska mjesta 1, 2 i 3.';

    public const INVALID_CANONICAL_SEAT_MESSAGE = 'Komisija za podršku mikro, malim i srednjim preduzećima može koristiti samo kanonska mjesta 1, 2 i 3.';

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

    public const SESSION_SECOND_INCOMPLETE_COMMISSION_MESSAGE = 'Ocjenjivanje i usmeno predstavljanje mogu početi tek kada su sva tri mjesta Komisije formalno popunjena.';

    public const SESSION_SECOND_EXISTS_MESSAGE = 'Poziv već ima drugu sjednicu Komisije.';

    public const SESSION_SECOND_CONFLICT_MESSAGE = 'Druga sjednica za ovaj Poziv već postoji.';

    public const SESSION_SECOND_NOT_YOUTH_MESSAGE = 'Usmeno predstavljanje dostupno je samo za omladinski Poziv.';

    public const SESSION_SECOND_REQUIRES_FIRST_MESSAGE = 'Druga sjednica može početi tek nakon potvrđene prve sjednice.';

    public const SESSION_SECOND_APPEAL_GATE_MESSAGE = 'Ocjenjivanje i usmeno predstavljanje ne mogu početi dok postoji otvoren rok za prigovor ili neriješen prigovor.';

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

    public const CANONICAL_LEGACY_FORM_MESSAGE = 'Članstvo kanonske MMSP Komisije se ne mijenja kroz ovaj obrazac. Evidentira se kroz kanonsko evidentiranje imenovanja.';

    public const CANONICAL_SESSION_ROUTE_MESSAGE = 'Kanonska MMSP Komisija koristi kanonsku politiku sjednica, ne omladinski tok prve/druge sjednice.';

    /** @var list<string> */
    public const COMMISSION_PROFILE_TYPES = [
        'zensko',
        'omladinsko',
        CompetitionProfileResolver::TYPE_CANONICAL,
    ];

    /**
     * Radio opcije zajedničke forme. Redoslijed je dio ugovora forme.
     *
     * @return array<string, string>
     */
    public static function sharedFormTypeLabels(): array
    {
        return [
            self::PROFILE_ZENSKO => 'Žensko preduzetništvo',
            self::PROFILE_OMLADINSKO => 'Podrška preduzetništvu mladih',
            self::PROFILE_CANONICAL => 'Podrška mikro, malim i srednjim preduzećima',
        ];
    }

    public static function usesThreeSeatSharedForm(?string $type): bool
    {
        return in_array($type, [self::PROFILE_OMLADINSKO, self::PROFILE_CANONICAL], true);
    }

    public static function incompleteCreateMessage(string $type): string
    {
        return match ($type) {
            self::PROFILE_OMLADINSKO => self::INCOMPLETE_OMLADINSKO_CREATE_MESSAGE,
            self::PROFILE_CANONICAL => self::INCOMPLETE_CANONICAL_CREATE_MESSAGE,
            default => self::INCOMPLETE_ZENSKO_CREATE_MESSAGE,
        };
    }

    public static function presidentCountMessage(string $type): string
    {
        return $type === self::PROFILE_CANONICAL
            ? self::CANONICAL_PRESIDENT_COUNT_MESSAGE
            : self::YOUTH_PRESIDENT_COUNT_MESSAGE;
    }

    public static function invalidSeatMessage(string $type): string
    {
        return $type === self::PROFILE_CANONICAL
            ? self::INVALID_CANONICAL_SEAT_MESSAGE
            : self::INVALID_YOUTH_SEAT_MESSAGE;
    }

    public static function memberTypeRejectedMessage(string $type): string
    {
        return $type === self::PROFILE_CANONICAL
            ? self::CANONICAL_MEMBER_TYPE_REJECTED_MESSAGE
            : self::YOUTH_MEMBER_TYPE_REJECTED_MESSAGE;
    }

    public static function membersMaxMessage(string $type): string
    {
        return match ($type) {
            self::PROFILE_OMLADINSKO => 'Komisija mladih može imati najviše 3 člana.',
            self::PROFILE_CANONICAL => 'Komisija za podršku mikro, malim i srednjim preduzećima može imati najviše 3 člana.',
            default => 'Komisija može imati najviše 5 članova.',
        };
    }

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
     * Kanonski tip forme iz već učitanih tipova Poziva.
     * Prihvata jedinstveni podržani profil, uključujući MMSP.
     *
     * @param  iterable<int|string|null>  $types
     * @return array{type: ?string, error: ?string}
     */
    public static function canonicalFormTypeFromTypes(iterable $types): array
    {
        $normalized = collect($types)->map(function ($type) {
            if (! is_string($type) || $type === '') {
                return null;
            }

            return $type;
        })->values();

        if ($normalized->contains(null)) {
            return ['type' => null, 'error' => self::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE];
        }

        $unique = $normalized->unique()->values();
        if ($unique->isEmpty()) {
            return ['type' => null, 'error' => self::COMPETITION_REQUIRED_MESSAGE];
        }

        if ($unique->count() !== 1) {
            $knTypes = $unique->filter(fn ($type) => in_array($type, self::COMMISSION_PROFILE_TYPES, true));
            if ($knTypes->count() > 1) {
                return ['type' => null, 'error' => self::mixedProfileMessage($knTypes->all())];
            }

            return ['type' => null, 'error' => self::MIXED_COMPETITION_TYPES_MESSAGE];
        }

        $type = $unique->first();
        if (! in_array($type, self::SUPPORTED_STORED_PROFILES, true)) {
            return ['type' => null, 'error' => self::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE];
        }

        return ['type' => $type, 'error' => null];
    }

    /**
     * Kanonski tip forme iz Poziva u bazi. Ne čita browser payload.
     *
     * @param  iterable<int|string|null>  $competitionIds
     * @return array{type: ?string, error: ?string}
     */
    public static function canonicalFormTypeFromCompetitionIds(iterable $competitionIds): array
    {
        $ids = collect($competitionIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return ['type' => null, 'error' => self::COMPETITION_REQUIRED_MESSAGE];
        }

        $competitions = Competition::query()
            ->whereIn('id', $ids)
            ->get(['id', 'type']);

        if ($competitions->count() !== $ids->count()) {
            return ['type' => null, 'error' => self::UNKNOWN_COMPETITION_ASSIGNMENT_MESSAGE];
        }

        return self::canonicalFormTypeFromTypes($competitions->pluck('type'));
    }

    /**
     * Profil forme iz sačuvanog `commissions.profile_type`.
     * Ne nagađa iz Poziva, broja mjesta, member_type ni request payload-a.
     *
     * @return array{type: ?string, error: ?string}
     */
    public static function storedCanonicalFormType(Commission $commission): array
    {
        $type = $commission->profile_type;
        if (! is_string($type) || ! in_array($type, self::SUPPORTED_STORED_PROFILES, true)) {
            return ['type' => null, 'error' => self::UNDETERMINED_COMMISSION_PROFILE_MESSAGE];
        }

        return ['type' => $type, 'error' => null];
    }

    /**
     * @return array{type: ?string, error: ?string}
     */
    public static function assertSupportedStoredProfile(mixed $type): array
    {
        if (! is_string($type) || $type === '') {
            return ['type' => null, 'error' => self::PROFILE_REQUIRED_MESSAGE];
        }

        if (! in_array($type, self::SUPPORTED_STORED_PROFILES, true)) {
            return ['type' => null, 'error' => self::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE];
        }

        return ['type' => $type, 'error' => null];
    }

    /**
     * @param  iterable<int|string|null>  $competitionIds
     */
    public static function selectedCompetitionsMatchProfile(iterable $competitionIds, string $profile): ?string
    {
        $ids = collect($competitionIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $hasCanonicalCall = Competition::query()
            ->whereIn('id', $ids)
            ->where('type', CompetitionProfileResolver::TYPE_CANONICAL)
            ->exists();
        if ($hasCanonicalCall && $profile !== CompetitionProfileResolver::TYPE_CANONICAL) {
            return self::CANONICAL_COMMISSION_REQUIRED_MESSAGE;
        }

        $resolved = self::canonicalFormTypeFromCompetitionIds($ids);
        if ($resolved['error']) {
            return $resolved['error'];
        }

        if ($resolved['type'] !== $profile) {
            return self::COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE;
        }

        return null;
    }

    public static function conflictWithAssignedCompetitions(
        Commission $commission,
        string $targetType,
        ?int $excludingCompetitionId = null
    ): ?string {
        unset($excludingCompetitionId);

        $canonical = CompetitionProfileResolver::TYPE_CANONICAL;
        $stored = $commission->assignedProfileType();

        if ($stored === $canonical) {
            return $targetType === $canonical ? null : self::CANONICAL_PROFILE_ASSIGNMENT_MESSAGE;
        }

        if ($targetType === $canonical) {
            return self::CANONICAL_COMMISSION_REQUIRED_MESSAGE;
        }

        if (! in_array($stored, self::SUPPORTED_STORED_PROFILES, true)) {
            return self::UNDETERMINED_COMMISSION_PROFILE_MESSAGE;
        }

        if (! in_array($targetType, self::SUPPORTED_STORED_PROFILES, true)) {
            return self::UNSUPPORTED_PROFILE_ASSIGNMENT_MESSAGE;
        }

        if ($stored !== $targetType) {
            return self::COMMISSION_COMPETITION_PROFILE_MISMATCH_MESSAGE;
        }

        return null;
    }

    /**
     * @param  iterable<int|string|null>  $competitionIds
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
