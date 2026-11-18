<?php

namespace App\Support;

use App\Models\CommissionMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hard delete of an MMSP member is allowed only while the row has no recorded
 * activity. Related scores, attendance, decisions and audit rows are left intact.
 */
final class MmspCommissionMembershipGuard
{
    /**
     * @var list<array{0: string, 1: string}>
     */
    private const LINKED_COLUMNS = [
        ['evaluation_scores', 'commission_member_id'],
        ['commission_session_attendances', 'commission_member_id'],
        ['commission_member_replacements', 'vacated_member_id'],
        ['commission_member_replacements', 'replacement_member_id'],
        ['commission_governance_conflicts', 'commission_member_id'],
        ['canonical_final_rankings', 'confirmed_by_member_id'],
        ['canonical_outcomes', 'recorded_by_member_id'],
        ['canonical_tie_resolutions', 'proposed_by_member_id'],
        ['canonical_tie_votes', 'commission_member_id'],
        ['canonical_final_ranking_signatures', 'commission_member_id'],
        ['application_prigovors', 'decided_by_commission_member_id'],
        ['application_eliminatory_checks', 'confirmed_by_commission_member_id'],
        ['application_prigovor_mapping_corrections', 'corrected_by_commission_member_id'],
        ['applications', 'bonuses_confirmed_by_commission_member_id'],
        ['competitions', 'youth_allocation_list_confirmed_by_commission_member_id'],
        ['youth_equal_score_rounds', 'created_by_member_id'],
        ['youth_equal_score_rounds', 'locked_by_member_id'],
        ['youth_equal_score_votes', 'commission_member_id'],
    ];

    public static function hardDeleteBlockReason(CommissionMember $member): ?string
    {
        if (self::memberCarriesRecordedActivity($member) || self::linkedRecordExists($member)) {
            return CommissionProfileConfig::CANONICAL_LEGACY_FORM_MESSAGE;
        }

        return null;
    }

    private static function memberCarriesRecordedActivity(CommissionMember $member): bool
    {
        if ($member->status !== 'active' || ! empty($member->is_substitute)) {
            return true;
        }

        foreach ([
            'appointment_reference',
            'appointed_at',
            'replaced_at',
            'replaced_by_member_id',
            'canonical_active_user_id',
            'canonical_active_seat',
            'canonical_active_seat_no',
            'represented_company_pib',
            'declarations_signed_at',
        ] as $attribute) {
            if ($member->{$attribute} !== null && $member->{$attribute} !== '') {
                return true;
            }
        }

        return trim((string) $member->confidentiality_declaration) !== ''
            || trim((string) $member->conflict_of_interest_declaration) !== '';
    }

    private static function linkedRecordExists(CommissionMember $member): bool
    {
        $id = (int) $member->id;

        if (CommissionMember::query()->where('replaced_by_member_id', $id)->exists()) {
            return true;
        }

        foreach (self::LINKED_COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            if (DB::table($table)->where($column, $id)->exists()) {
                return true;
            }
        }

        if (! Schema::hasTable('canonical_administrative_events')) {
            return false;
        }

        return DB::table('canonical_administrative_events')
            ->where('subject_type', 'CommissionMember')
            ->where('subject_id', $id)
            ->exists()
            || DB::table('canonical_administrative_events')
                ->where('payload->vacated_member_id', $id)
                ->orWhere('payload->replacement_member_id', $id)
                ->exists();
    }
}
