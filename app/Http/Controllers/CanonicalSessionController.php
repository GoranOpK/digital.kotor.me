<?php

namespace App\Http\Controllers;

use App\Models\CommissionSession;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Commission\CanonicalCommissionSessionService;
use App\Services\Competitions\ProcessWriteResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Canonical MMSP Commission sessions (ordinary, interview, final decision). Orchestration only;
 * authority (chair of the current canonical Commission), quorum, timing and locking live in
 * CanonicalCommissionSessionService / CanonicalCommissionGovernance.
 */
class CanonicalSessionController extends Controller
{
    public const HELD_IN_FUTURE_MESSAGE = 'Sjednica se evidentira nakon održavanja; datum i vrijeme ne mogu biti u budućnosti.';

    private const ABSENT = 'absent';

    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalCommissionSessionService $sessions,
    ) {
    }

    public function show(Competition $competition): View
    {
        $this->canonicalOrAbort($competition);
        abort_if($this->governance->activeMemberFor(Auth::user(), $competition) === null, 403, CanonicalCommissionGovernance::NOT_MEMBER_MESSAGE);

        return view('evaluation.canonical-sessions', array_merge(
            ['competition' => $competition, 'readOnly' => false],
            $this->sessions->overview($competition, Auth::user()),
        ));
    }

    public function adminShow(Competition $competition): View
    {
        $this->canonicalOrAbort($competition);

        return view('evaluation.canonical-sessions', array_merge(
            ['competition' => $competition, 'readOnly' => true],
            $this->sessions->overview($competition, null),
        ));
    }

    public function record(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $validated = $request->validate([
            'purpose' => ['required', 'string', Rule::in(array_keys(CanonicalCommissionGovernance::PURPOSE_SESSION_TYPES))],
            'held_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ] + $this->attendanceRules());

        $heldAt = CarbonImmutable::parse($validated['held_at'], $this->governance->policy()->publicationTimezone());
        if ($heldAt->greaterThan(CarbonImmutable::now())) {
            return back()->withInput()->withErrors(['canonical_session' => self::HELD_IN_FUTURE_MESSAGE]);
        }

        return $this->respond($this->sessions->record(
            $competition,
            Auth::user(),
            $validated['purpose'],
            $heldAt,
            $this->attendance($validated),
            $validated['notes'] ?? null,
        ), 'Sjednica je evidentirana. Potvrdom se zaključava.');
    }

    public function updateAttendance(Request $request, Competition $competition, CommissionSession $session): RedirectResponse
    {
        $this->scopedOrAbort($competition, $session);
        $validated = $request->validate($this->attendanceRules());

        return $this->respond(
            $this->sessions->updateAttendance($session, Auth::user(), $this->attendance($validated)),
            'Prisustvo je ažurirano.',
        );
    }

    public function confirm(Competition $competition, CommissionSession $session): RedirectResponse
    {
        $this->scopedOrAbort($competition, $session);

        return $this->respond($this->sessions->confirm($session, Auth::user()), 'Sjednica je potvrđena i zaključana.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceRules(): array
    {
        return [
            'attendance' => 'required|array|min:1',
            'attendance.*' => ['required', 'string', Rule::in([...CanonicalCommissionGovernance::ATTENDANCE_MODES, self::ABSENT])],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, string|null>
     */
    private function attendance(array $validated): array
    {
        $attendance = [];
        foreach ($validated['attendance'] as $memberId => $mode) {
            $attendance[(int) $memberId] = $mode === self::ABSENT ? null : $mode;
        }

        return $attendance;
    }

    private function canonicalOrAbort(Competition $competition): void
    {
        abort_unless($this->governance->isCanonicalCompetition($competition), 403, CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
    }

    private function scopedOrAbort(Competition $competition, CommissionSession $session): void
    {
        $this->canonicalOrAbort($competition);
        abort_unless((int) $session->competition_id === (int) $competition->id && $session->canonical_purpose !== null, 404);
    }

    private function respond(ProcessWriteResult $result, string $success): RedirectResponse
    {
        if (! $result->succeeded) {
            return back()->withInput()->withErrors(['canonical_session' => $result->error]);
        }

        $redirect = back()->with('success', $success);

        return $result->warning !== null ? $redirect->with('warning', $result->warning) : $redirect;
    }
}
