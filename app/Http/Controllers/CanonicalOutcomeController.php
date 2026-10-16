<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\CanonicalTieResolution;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Outcome\CanonicalOutcomeService;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\Outcome\CanonicalTieResolutionService;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Canonical MMSP third-session outcome screen (KN-SYNC-008). Orchestration only; every rule
 * lives in App\Services\Competitions\Outcome. Commission members only; nothing here is public.
 */
class CanonicalOutcomeController extends Controller
{
    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalOutcomeService $outcomes,
        private readonly CanonicalTieResolutionService $ties,
        private readonly CanonicalPredlogService $predlog,
    ) {
    }

    public function show(Competition $competition): View
    {
        $member = $this->memberOrAbort($competition);

        $rankingFormed = $this->governance->canonicalRankingFormed($competition);
        $rows = $rankingFormed ? $this->outcomes->rankedRows($competition) : [];
        $session = CommissionSession::query()
            ->where('competition_id', $competition->id)
            ->where('session_type', CommissionSession::TYPE_THIRD)
            ->where('canonical_purpose', CanonicalCommissionGovernance::PURPOSE_FINAL_DECISION)
            ->first();

        return view('evaluation.canonical-outcome', [
            'competition' => $competition,
            'member' => $member,
            'rankingFormed' => $rankingFormed,
            'sessionBlockReason' => $this->governance->finalDecisionSessionBlockReason($competition),
            'session' => $session,
            'rows' => $rows,
            'outcomes' => $this->outcomes->outcomesFor($competition),
            'resolutions' => CanonicalTieResolution::query()->where('competition_id', $competition->id)->with('votes')->orderBy('id')->get(),
            'finalRanking' => $this->outcomes->finalRankingFor($competition),
            'predlogBlockReason' => $this->predlog->readinessBlockReason($competition),
            'predlog' => $this->predlog->predlogFor($competition),
        ]);
    }

    public function storeDraft(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        $this->memberOrAbort($competition);
        $validated = $request->validate([
            'decision' => 'required|string|max:16',
            'amount' => 'nullable|string|max:32',
            'reason' => 'nullable|string|max:64',
            'justification' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:5000',
        ]);

        return $this->respond($this->outcomes->recordDraft($competition, Auth::user(), (int) $application->id, $validated), 'Nacrt ishoda je sačuvan.');
    }

    public function confirm(Competition $competition): RedirectResponse
    {
        $this->memberOrAbort($competition);

        return $this->respond($this->outcomes->confirm($competition, Auth::user()), 'Ishod je konačno potvrđen. Potrebni su potpisi sva 3 člana Komisije.');
    }

    public function sign(Competition $competition): RedirectResponse
    {
        $this->memberOrAbort($competition);

        return $this->respond($this->outcomes->sign($competition, Auth::user()), 'Konačna rang lista je potpisana.');
    }

    public function proposeTie(Request $request, Competition $competition): RedirectResponse
    {
        $this->memberOrAbort($competition);
        $validated = $request->validate([
            'shared_rank' => 'required|integer|min:1',
            'tied_application_ids' => 'required|array|min:2',
            'tied_application_ids.*' => 'integer',
            'selected_application_ids' => 'required|array|min:1',
            'selected_application_ids.*' => 'integer',
        ]);

        return $this->respond($this->ties->propose(
            $competition,
            Auth::user(),
            (int) $validated['shared_rank'],
            array_map('intval', $validated['tied_application_ids']),
            array_map('intval', $validated['selected_application_ids']),
        ), 'Prijedlog odluke o jednakom rangu je evidentiran.');
    }

    public function voteTie(Request $request, Competition $competition, CanonicalTieResolution $resolution): RedirectResponse
    {
        $this->memberOrAbort($competition);
        abort_unless((int) $resolution->competition_id === (int) $competition->id, 404);
        $validated = $request->validate(['in_favour' => 'required|boolean']);

        return $this->respond($this->ties->vote($resolution, Auth::user(), (bool) $validated['in_favour']), 'Glas je evidentiran.');
    }

    private function memberOrAbort(Competition $competition): CommissionMember
    {
        abort_unless($this->governance->isCanonicalCompetition($competition), 404);
        $member = $this->governance->activeMemberFor(Auth::user(), $competition);
        abort_if($member === null, 403, CanonicalCommissionGovernance::NOT_MEMBER_MESSAGE);

        return $member;
    }

    private function respond(ProcessWriteResult $result, string $success): RedirectResponse
    {
        $redirect = back();
        if (! $result->succeeded) {
            return $redirect->withErrors(['canonical_outcome' => $result->error]);
        }

        return $result->warning !== null
            ? $redirect->with('success', $success)->with('warning', $result->warning)
            : $redirect->with('success', $success);
    }
}
