<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * KN-SYNC-009 — append-only audit of canonical administrative steps (Predlog finalization,
 * Secretariat handoff, Odluka record, signed copy, publication, close). The existing official
 * decision lifecycle log is bound to a signed copy row and cannot hold the pre-copy steps.
 * KN-SYNC-012 — also holds canonical Commission governance records made from a Poziv.
 */
class CanonicalAdministrativeEvent extends Model
{
    public const COMMISSION_ESTABLISHED = 'commission_established';

    public const COMMISSION_APPOINTMENT_RECORDED = 'commission_appointment_recorded';

    public const COMMISSION_APPOINTMENT_BLOCKED = 'commission_appointment_blocked';

    public const COMMISSION_DECLARATIONS_RECORDED = 'commission_declarations_recorded';

    public const COMMISSION_MEMBER_VACATED = 'commission_member_vacated';

    public const COMMISSION_MEMBER_REPLACED = 'commission_member_replaced';

    public const COMMISSION_CONFLICTS_CHECKED = 'commission_conflicts_checked';

    public const COMMISSION_SESSION_ATTENDANCE_UPDATED = 'commission_session_attendance_updated';

    public const COMMISSION_SESSION_CONFIRMED = 'commission_session_confirmed';

    /** @var list<string> */
    public const COMMISSION_ACTIONS = [
        self::COMMISSION_ESTABLISHED,
        self::COMMISSION_APPOINTMENT_RECORDED,
        self::COMMISSION_APPOINTMENT_BLOCKED,
        self::COMMISSION_DECLARATIONS_RECORDED,
        self::COMMISSION_MEMBER_VACATED,
        self::COMMISSION_MEMBER_REPLACED,
        self::COMMISSION_CONFLICTS_CHECKED,
        self::COMMISSION_SESSION_ATTENDANCE_UPDATED,
        self::COMMISSION_SESSION_CONFIRMED,
    ];

    public const PREDLOG_FINALIZED = 'predlog_finalized';

    public const SECRETARIAT_HANDOFF = 'secretariat_handoff';

    public const FORMAL_DECISION_RECORDED = 'formal_decision_recorded';

    public const SIGNED_COPY_ATTACHED = 'signed_copy_attached';

    public const RESULT_PUBLISHED = 'result_published';

    public const RESULT_TEST_PUBLISHED = 'result_test_published';

    public const COMPETITION_CLOSED = 'competition_closed';

    public const TEST_APPLICATIONS_CLOSED = 'test_applications_closed';

    public const UPDATED_AT = null;

    protected $fillable = [
        'competition_id',
        'action',
        'actor_user_id',
        'subject_type',
        'subject_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Revizijski zapis se ne može mijenjati.'));
        static::deleting(fn () => throw new LogicException('Revizijski zapis se ne može brisati.'));
    }
}
