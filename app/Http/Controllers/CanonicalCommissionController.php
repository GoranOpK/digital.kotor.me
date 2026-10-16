<?php

namespace App\Http\Controllers;

use App\Models\CommissionMember;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionAdministrationService;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Commission\CanonicalCommissionMembershipService;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * KN-SYNC-012 — thin admin orchestration of canonical Commission governance for one canonical Poziv.
 */
class CanonicalCommissionController extends Controller
{
    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalCommissionAdministrationService $administration,
    ) {
    }

    public function show(Competition $competition): View
    {
        $this->canonicalOrAbort($competition);

        return view('admin.competitions.canonical-commission', [
            'competition' => $competition,
        ] + $this->administration->overview($competition));
    }

    public function establish(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'appointment_reference' => ['required', 'string', 'max:255'],
            'appointed_on' => ['required', 'date_format:Y-m-d'],
            'mandate_starts_on' => ['required', 'date_format:Y-m-d'],
        ]);

        return $this->respond(
            $this->administration->establish(
                $competition, $request->user(), $data['name'], $data['mandate_starts_on'],
                $data['appointment_reference'], $data['appointed_on'],
            ),
            'Kanonska Komisija je uspostavljena i dodijeljena Pozivu.',
        );
    }

    public function appoint(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'seat_no' => ['required', 'integer', 'between:1,3'],
            'role' => ['required', Rule::in(CanonicalCommissionGovernance::ROLES)],
            'external_category' => ['nullable', Rule::in(CanonicalCommissionGovernance::EXTERNAL_CATEGORIES)],
            'appointment_reference' => ['required', 'string', 'max:255'],
            'appointed_on' => ['required', 'date_format:Y-m-d'],
            'represented_company_pib' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->respond(
            $this->administration->appoint(
                $competition, $request->user(), $data['email'], (int) $data['seat_no'], $data['role'],
                $data['external_category'] ?? null, $data['appointment_reference'], $data['appointed_on'],
                $data['represented_company_pib'] ?? null,
            ),
            'Imenovanje člana je evidentirano.',
        );
    }

    public function recordDeclarations(Request $request, Competition $competition, CommissionMember $member): RedirectResponse
    {
        $this->scopedOrAbort($competition, $member);
        $data = $request->validate([
            'signed_on' => ['required', 'date_format:Y-m-d'],
            'confidentiality_signed' => ['accepted'],
            'conflict_of_interest_signed' => ['accepted'],
        ], [
            'confidentiality_signed.accepted' => 'Potvrdite da je član potpisao izjavu o povjerljivosti.',
            'conflict_of_interest_signed.accepted' => 'Potvrdite da je član potpisao izjavu o nepostojanju sukoba interesa.',
        ]);

        return $this->respond(
            $this->administration->recordDeclarations($competition, $member, $request->user(), $data['signed_on']),
            'Izjave člana su evidentirane.',
        );
    }

    public function vacate(Request $request, Competition $competition, CommissionMember $member): RedirectResponse
    {
        $this->scopedOrAbort($competition, $member);
        $data = $request->validate([
            'reason' => ['required', Rule::in(CanonicalCommissionMembershipService::VACANCY_REASONS)],
            'triggered_at' => ['required', 'date'],
        ]);

        return $this->respond(
            $this->administration->vacate($competition, $member, $request->user(), $data['reason'], $data['triggered_at']),
            'Upražnjenost mjesta je evidentirana.',
        );
    }

    public function replace(Request $request, Competition $competition, CommissionMember $member): RedirectResponse
    {
        $this->scopedOrAbort($competition, $member);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'appointment_reference' => ['required', 'string', 'max:255'],
            'appointed_on' => ['required', 'date_format:Y-m-d'],
            'external_category' => ['nullable', Rule::in(CanonicalCommissionGovernance::EXTERNAL_CATEGORIES)],
            'represented_company_pib' => ['nullable', 'string', 'max:32'],
        ]);

        return $this->respond(
            $this->administration->replace(
                $competition, $member, $request->user(), $data['email'], $data['appointment_reference'],
                $data['appointed_on'], $data['external_category'] ?? null, $data['represented_company_pib'] ?? null,
            ),
            'Zamjena člana je evidentirana.',
        );
    }

    public function refreshConflicts(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $result = $this->administration->refreshConflicts($competition, $request->user());

        return $this->respond($result, 'Provjera PIB sukoba je završena. Novih zapisa: '.(int) $result->recordId.'.');
    }

    private function respond(ProcessWriteResult $result, string $success): RedirectResponse
    {
        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error])->withInput();
        }

        $redirect = back()->with('success', $success);

        return $result->warning !== null ? $redirect->with('warning', $result->warning) : $redirect;
    }

    private function canonicalOrAbort(Competition $competition): void
    {
        if (! $this->governance->isCanonicalCompetition($competition)) {
            abort(403, CanonicalCommissionAdministrationService::NOT_CANONICAL_CALL_MESSAGE);
        }
    }

    private function scopedOrAbort(Competition $competition, CommissionMember $member): void
    {
        $this->canonicalOrAbort($competition);
        if ($competition->commission_id === null || (int) $member->commission_id !== (int) $competition->commission_id) {
            abort(403, CanonicalCommissionAdministrationService::MEMBER_SCOPE_MESSAGE);
        }
    }
}
