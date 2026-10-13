<?php

namespace App\Services\Competitions\Decision;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\Competition;
use App\Models\User;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use Illuminate\Database\Eloquent\Model;

/**
 * KN-SYNC-009 — shared gates for the canonical administrative finish line.
 * Commission steps (Predlog finalization, handoff, close) belong to the valid Commission chair;
 * Secretariat steps (Odluka record, signed copy, publication) belong to konkurs_admin.
 */
final class CanonicalAdministrativeGate
{
    public const CLOSED_MESSAGE = 'Kanonski Poziv je zatvoren i arhiviran. Izmjene nijesu dozvoljene.';

    public const CHAIR_ONLY_MESSAGE = 'Ovu radnju izvršava predsjednik važeće kanonske Komisije.';

    public const SECRETARIAT_ONLY_MESSAGE = 'Ovu radnju izvršava nadležni Sekretarijat (administrator konkursa).';

    public const SECRETARIAT_ROLE = 'konkurs_admin';

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
    ) {
    }

    public function isCanonical(?Competition $competition): bool
    {
        return $this->governance->isCanonicalCompetition($competition);
    }

    public static function isClosed(?Competition $competition): bool
    {
        return in_array($competition?->status, ['closed', 'completed'], true);
    }

    public function baseBlockReason(?Competition $competition): ?string
    {
        if (! $this->isCanonical($competition)) {
            return CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE;
        }

        if (self::isClosed($competition)) {
            return self::CLOSED_MESSAGE;
        }

        return null;
    }

    public function chairBlockReason(User $actor, Competition $competition): ?string
    {
        $member = $this->governance->activeMemberFor($actor, $competition);
        if ($member === null) {
            return CanonicalCommissionGovernance::NOT_MEMBER_MESSAGE;
        }

        if ($member->canonical_seat !== CanonicalCommissionGovernance::ROLE_PREDSJEDNIK) {
            return self::CHAIR_ONLY_MESSAGE;
        }

        return $this->governance->memberActionBlockReason($member, $competition);
    }

    public function secretariatBlockReason(User $actor): ?string
    {
        return $actor->role?->name === self::SECRETARIAT_ROLE ? null : self::SECRETARIAT_ONLY_MESSAGE;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function audit(Competition $competition, string $action, User $actor, Model $subject, array $payload = []): CanonicalAdministrativeEvent
    {
        return CanonicalAdministrativeEvent::query()->create([
            'competition_id' => $competition->id,
            'action' => $action,
            'actor_user_id' => $actor->id,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'payload' => $payload === [] ? null : $payload,
        ]);
    }
}
