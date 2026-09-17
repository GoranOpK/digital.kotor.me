<?php

namespace App\Http\Controllers;

use App\Models\CommissionMember;
use App\Models\CommissionSession;
use App\Models\CommissionSessionAttendance;
use App\Models\Competition;
use App\Services\YouthSecondSessionGate;
use App\Support\CommissionProfileConfig;
use App\Support\NamedMysqlUniqueViolation;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommissionSessionController extends Controller
{
    public function __construct(
        protected YouthSecondSessionGate $gate,
    ) {}

    public function editFirst(Competition $competition): View
    {
        $chairman = $this->authorizeFirstSessionAccess($competition);
        $session = $competition->firstCommissionSession();
        $session?->load('attendances');
        $eligibleMembers = $this->eligibleMembers($competition);

        return view('commission-sessions.first', [
            'competition' => $competition,
            'session' => $session,
            'eligibleMembers' => $eligibleMembers,
            'chairman' => $chairman,
            'readonly' => $session?->isConfirmed() ?? false,
        ]);
    }

    public function storeFirst(Request $request, Competition $competition): RedirectResponse
    {
        $chairman = $this->authorizeFirstSessionAccess($competition);

        if ($competition->firstCommissionSession() !== null) {
            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_FIRST_EXISTS_MESSAGE,
            ]);
        }

        $payload = $this->validatedDraftPayload($request, $competition);

        try {
            DB::transaction(function () use ($competition, $chairman, $payload) {
                $session = CommissionSession::create([
                    'competition_id' => $competition->id,
                    'commission_id' => $competition->commission_id,
                    'session_type' => CommissionSession::TYPE_FIRST,
                    'held_at' => $payload['held_at'],
                    'completed_at' => null,
                    'recorded_by_user_id' => $chairman->user_id,
                    'notes' => $payload['notes'],
                ]);

                $this->syncAttendances($session, $competition, $payload['present_member_ids']);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_FIRST_CONFLICT_MESSAGE,
            ]);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_FIRST_CONFLICT_MESSAGE,
            ]);
        }

        return redirect()
            ->route('commission-sessions.first.edit', $competition)
            ->with('success', 'Nacrt prve sjednice je sačuvan.');
    }

    public function updateFirst(Request $request, Competition $competition): RedirectResponse
    {
        $chairman = $this->authorizeFirstSessionAccess($competition);
        $session = $this->requireDraftFirstSession($competition);
        $payload = $this->validatedDraftPayload($request, $competition);

        DB::transaction(function () use ($session, $competition, $chairman, $payload) {
            $session->update([
                'held_at' => $payload['held_at'],
                'notes' => $payload['notes'],
                'recorded_by_user_id' => $chairman->user_id,
            ]);

            $this->syncAttendances($session, $competition, $payload['present_member_ids']);
        });

        return redirect()
            ->route('commission-sessions.first.edit', $competition)
            ->with('success', 'Nacrt prve sjednice je ažuriran.');
    }

    public function confirmFirst(Competition $competition): RedirectResponse
    {
        $this->authorizeFirstSessionAccess($competition);

        DB::transaction(function () use ($competition) {
            $session = CommissionSession::query()
                ->where('competition_id', $competition->id)
                ->where('session_type', CommissionSession::TYPE_FIRST)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                abort(404);
            }

            if ($session->isConfirmed()) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_LOCKED_MESSAGE,
                ]);
            }

            $lockedCompetition = Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $session->competition_id !== (int) $lockedCompetition->id
                || (int) $session->commission_id !== (int) $lockedCompetition->commission_id) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_INCOMPLETE_COMMISSION_MESSAGE,
                ]);
            }

            $this->authorizeFirstSessionAccess($lockedCompetition);

            $lockedCompetition->unsetRelation('commission');
            $lockedCompetition->load(['commission.activeMembers']);

            if (! $lockedCompetition->hasCompleteValidCommission()) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_INCOMPLETE_COMMISSION_MESSAGE,
                ]);
            }

            $session->unsetRelation('attendances');
            $session->load(['attendances.member']);

            $quorum = CommissionProfileConfig::for($lockedCompetition->type)->firstSessionQuorum ?? 2;
            if ($session->validPresentCount($lockedCompetition) < $quorum) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_QUORUM_MESSAGE,
                ]);
            }

            $session->update(['completed_at' => now()]);
        });

        return redirect()
            ->route('commission-sessions.first.edit', $competition)
            ->with('success', 'Prva sjednica je potvrđena.');
    }

    public function editSecond(Competition $competition): View
    {
        $chairman = $this->authorizeSecondSessionAccess($competition);
        $session = $competition->secondCommissionSession();
        $session?->load('attendances');
        $eligibleMembers = $this->eligibleMembers($competition);
        $first = $competition->firstCommissionSession();
        $deadlineAt = $first?->held_at?->copy()->addDays(CommissionProfileConfig::SESSION_SECOND_DEADLINE_DAYS);

        return view('commission-sessions.second', [
            'competition' => $competition,
            'session' => $session,
            'eligibleMembers' => $eligibleMembers,
            'chairman' => $chairman,
            'readonly' => $session?->isConfirmed() ?? false,
            'firstSession' => $first,
            'secondDeadlineAt' => $deadlineAt,
            'secondDeadlineOverdue' => $deadlineAt !== null && now()->gt($deadlineAt),
            'gateMessage' => $this->gate->secondSessionBlockMessage($competition),
            'eligibleApplications' => $this->gate->eligibleOralApplications($competition, $session),
        ]);
    }

    public function storeSecond(Request $request, Competition $competition): RedirectResponse
    {
        $chairman = $this->authorizeSecondSessionAccess($competition);
        $this->assertSecondSessionMayStart($competition);

        if ($competition->secondCommissionSession() !== null) {
            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_SECOND_EXISTS_MESSAGE,
            ]);
        }

        $payload = $this->validatedDraftPayload($request, $competition);

        try {
            DB::transaction(function () use ($competition, $chairman, $payload) {
                Competition::query()->whereKey($competition->id)->lockForUpdate()->firstOrFail();

                $session = CommissionSession::create([
                    'competition_id' => $competition->id,
                    'commission_id' => $competition->commission_id,
                    'session_type' => CommissionSession::TYPE_SECOND,
                    'held_at' => $payload['held_at'],
                    'completed_at' => null,
                    'recorded_by_user_id' => $chairman->user_id,
                    'notes' => $payload['notes'],
                ]);

                $this->syncAttendances($session, $competition, $payload['present_member_ids']);
            });
        } catch (UniqueConstraintViolationException|QueryException $e) {
            $this->throwSecondSessionConflictOrRethrow($e);
        }

        return redirect()
            ->route('commission-sessions.second.edit', $competition)
            ->with('success', 'Nacrt druge sjednice je sačuvan.');
    }

    public function updateSecond(Request $request, Competition $competition): RedirectResponse
    {
        $chairman = $this->authorizeSecondSessionAccess($competition);
        $this->assertSecondSessionMayStart($competition);
        $session = $this->requireDraftSecondSession($competition);
        $payload = $this->validatedDraftPayload($request, $competition);

        DB::transaction(function () use ($session, $competition, $chairman, $payload) {
            $locked = CommissionSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isConfirmed()) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_LOCKED_MESSAGE,
                ]);
            }

            $locked->update([
                'held_at' => $payload['held_at'],
                'notes' => $payload['notes'],
                'recorded_by_user_id' => $chairman->user_id,
            ]);

            $this->syncAttendances($locked, $competition, $payload['present_member_ids']);
        });

        return redirect()
            ->route('commission-sessions.second.edit', $competition)
            ->with('success', 'Nacrt druge sjednice je ažuriran.');
    }

    public function confirmSecond(Competition $competition): RedirectResponse
    {
        $this->authorizeSecondSessionAccess($competition);

        DB::transaction(function () use ($competition) {
            $session = CommissionSession::query()
                ->where('competition_id', $competition->id)
                ->where('session_type', CommissionSession::TYPE_SECOND)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                abort(404);
            }

            if ($session->isConfirmed()) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_LOCKED_MESSAGE,
                ]);
            }

            $lockedCompetition = Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeSecondSessionAccess($lockedCompetition);
            $this->assertSecondSessionMayStart($lockedCompetition);

            if ((int) $session->competition_id !== (int) $lockedCompetition->id
                || (int) $session->commission_id !== (int) $lockedCompetition->commission_id) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_SECOND_INCOMPLETE_COMMISSION_MESSAGE,
                ]);
            }

            $lockedCompetition->unsetRelation('commission');
            $lockedCompetition->load(['commission.activeMembers']);

            if (! $lockedCompetition->hasCompleteValidCommission()) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_SECOND_INCOMPLETE_COMMISSION_MESSAGE,
                ]);
            }

            $session->unsetRelation('attendances');
            $session->load(['attendances.member']);

            if (! $session->meetsSecondSessionAttendance($lockedCompetition)) {
                throw ValidationException::withMessages([
                    'session' => CommissionProfileConfig::SESSION_SECOND_QUORUM_MESSAGE,
                ]);
            }

            $session->update(['completed_at' => now()]);
        });

        return redirect()
            ->route('commission-sessions.second.edit', $competition)
            ->with('success', 'Druga sjednica je potvrđena.');
    }

    protected function authorizeFirstSessionAccess(Competition $competition): CommissionMember
    {
        $user = Auth::user();
        $member = CommissionMember::activeForCompetition($user->id, $competition);

        if (! $member || $member->position !== 'predsjednik') {
            abort(403, CommissionProfileConfig::SESSION_NOT_CHAIRMAN_MESSAGE);
        }

        if ((int) $member->commission_id !== (int) $competition->commission_id) {
            abort(403, CommissionProfileConfig::SESSION_NOT_CHAIRMAN_MESSAGE);
        }

        if (! in_array($competition->status, ['closed', 'completed'], true)
            && ! $competition->isApplicationDeadlinePassed()) {
            abort(403, 'Evidencija sjednice dostupna je tek nakon isteka roka za prijave.');
        }

        return $member;
    }

    protected function authorizeSecondSessionAccess(Competition $competition): CommissionMember
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE);
        }

        return $this->authorizeFirstSessionAccess($competition);
    }

    protected function assertSecondSessionMayStart(Competition $competition): void
    {
        $message = $this->gate->secondSessionBlockMessage($competition);
        if ($message !== null) {
            throw ValidationException::withMessages([
                'session' => $message,
            ]);
        }
    }

    protected function requireDraftSecondSession(Competition $competition): CommissionSession
    {
        $session = $competition->secondCommissionSession();
        if ($session === null) {
            abort(404);
        }
        if ($session->isConfirmed()) {
            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_LOCKED_MESSAGE,
            ]);
        }

        return $session;
    }

    protected function throwSecondSessionConflictOrRethrow(QueryException $e): never
    {
        if (! NamedMysqlUniqueViolation::matches($e, CommissionProfileConfig::SESSION_COMPETITION_TYPE_UNIQUE)) {
            throw $e;
        }

        throw ValidationException::withMessages([
            'session' => CommissionProfileConfig::SESSION_SECOND_CONFLICT_MESSAGE,
        ]);
    }

    protected function requireDraftFirstSession(Competition $competition): CommissionSession
    {
        $session = $competition->firstCommissionSession();
        if ($session === null) {
            abort(404);
        }
        if ($session->isConfirmed()) {
            throw ValidationException::withMessages([
                'session' => CommissionProfileConfig::SESSION_LOCKED_MESSAGE,
            ]);
        }

        return $session;
    }

    /**
     * @return array{held_at: string, notes: ?string, present_member_ids: list<int>}
     */
    protected function validatedDraftPayload(Request $request, Competition $competition): array
    {
        $validated = $request->validate([
            'held_at' => 'required|date',
            'notes' => 'nullable|string',
            'present_member_ids' => 'nullable|array',
            'present_member_ids.*' => 'integer',
        ]);

        $presentIds = collect($validated['present_member_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $eligibleIds = $this->eligibleMembers($competition)->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($presentIds as $memberId) {
            if (! in_array($memberId, $eligibleIds, true)) {
                throw ValidationException::withMessages([
                    'present_member_ids' => 'Prisustvo može obuhvatiti samo aktivne članove dodijeljene Komisije na dozvoljenim mjestima.',
                ]);
            }
        }

        return [
            'held_at' => $validated['held_at'],
            'notes' => $validated['notes'] ?? null,
            'present_member_ids' => $presentIds->all(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, CommissionMember>
     */
    protected function eligibleMembers(Competition $competition)
    {
        $config = CommissionProfileConfig::for($competition->type);
        $commission = $competition->commission;
        if (! $commission) {
            return collect();
        }

        return $commission->activeMembers()
            ->orderBy('id')
            ->get()
            ->filter(function (CommissionMember $member) use ($config, $commission) {
                if ((int) $member->commission_id !== (int) $commission->id) {
                    return false;
                }
                $seat = $member->canonicalSeatNumber();

                return $seat !== null && $config->allowsSeat($seat);
            })
            ->values();
    }

    /**
     * @param  list<int>  $presentMemberIds
     */
    protected function syncAttendances(CommissionSession $session, Competition $competition, array $presentMemberIds): void
    {
        $session->attendances()->delete();

        foreach ($this->eligibleMembers($competition) as $member) {
            CommissionSessionAttendance::create([
                'commission_session_id' => $session->id,
                'commission_member_id' => $member->id,
                'present' => in_array($member->id, $presentMemberIds, true),
            ]);
        }
    }
}
