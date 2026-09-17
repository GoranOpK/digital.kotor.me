<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationOralPresentation;
use App\Models\CommissionMember;
use App\Models\CommissionSession;
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

class ApplicationOralPresentationController extends Controller
{
    public function __construct(
        protected YouthSecondSessionGate $gate,
    ) {}

    public function edit(Competition $competition, Application $application): View
    {
        $chairman = $this->authorizeOralAccess($competition, $application);
        $session = $competition->secondCommissionSession();
        $oral = $application->oralPresentation;

        return view('commission-sessions.oral', [
            'competition' => $competition,
            'application' => $application,
            'session' => $session,
            'oral' => $oral,
            'chairman' => $chairman,
            'readonly' => $oral?->isCompleted() ?? false,
            'sessionConfirmed' => $session?->isConfirmed() ?? false,
            'eligible' => $this->gate->oralPresentationIsAllowed($application, $session),
        ]);
    }

    public function store(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        $chairman = $this->authorizeOralAccess($competition, $application);
        $payload = $this->validatedDraftPayload($request, requireHeldFields: false);

        try {
            DB::transaction(function () use ($competition, $application, $chairman, $payload) {
                $lockedApplication = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                $session = CommissionSession::query()
                    ->where('competition_id', $competition->id)
                    ->where('session_type', CommissionSession::TYPE_SECOND)
                    ->lockForUpdate()
                    ->first();

                if ($session === null) {
                    throw ValidationException::withMessages([
                        'oral' => CommissionProfileConfig::SESSION_SECOND_REQUIRED_FOR_ORAL_MESSAGE,
                    ]);
                }

                $this->assertOralMayBeDrafted($lockedApplication, $session);

                if ($lockedApplication->oralPresentation()->exists()) {
                    throw ValidationException::withMessages([
                        'oral' => CommissionProfileConfig::ORAL_EXISTS_MESSAGE,
                    ]);
                }

                $this->rejectHeldFieldsBeforeConfirm($session, $payload);

                ApplicationOralPresentation::create([
                    'commission_session_id' => $session->id,
                    'application_id' => $lockedApplication->id,
                    'scheduled_at' => $payload['scheduled_at'],
                    'held_at' => $session->isConfirmed() ? $payload['held_at'] : null,
                    'applicant_attended' => $session->isConfirmed() ? $payload['applicant_attended'] : null,
                    'notes' => $payload['notes'],
                    'completed_at' => null,
                    'recorded_by_user_id' => $chairman->user_id,
                ]);
            });
        } catch (UniqueConstraintViolationException|QueryException $e) {
            $this->throwOralConflictOrRethrow($e);
        }

        return redirect()
            ->route('commission-sessions.oral.edit', [$competition, $application])
            ->with('success', 'Nacrt usmenog predstavljanja je sačuvan.');
    }

    public function update(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        $chairman = $this->authorizeOralAccess($competition, $application);
        $payload = $this->validatedDraftPayload($request, requireHeldFields: false);

        DB::transaction(function () use ($competition, $application, $chairman, $payload) {
            $lockedApplication = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $session = CommissionSession::query()
                ->where('competition_id', $competition->id)
                ->where('session_type', CommissionSession::TYPE_SECOND)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                abort(404);
            }

            $oral = ApplicationOralPresentation::query()
                ->where('application_id', $lockedApplication->id)
                ->lockForUpdate()
                ->first();

            if ($oral === null) {
                abort(404);
            }

            if ($oral->isCompleted()) {
                throw ValidationException::withMessages([
                    'oral' => CommissionProfileConfig::ORAL_LOCKED_MESSAGE,
                ]);
            }

            $this->assertOralMayBeDrafted($lockedApplication, $session);
            $this->rejectHeldFieldsBeforeConfirm($session, $payload);

            $oral->update([
                'scheduled_at' => $payload['scheduled_at'],
                'held_at' => $session->isConfirmed() ? $payload['held_at'] : $oral->held_at,
                'applicant_attended' => $session->isConfirmed() ? $payload['applicant_attended'] : $oral->applicant_attended,
                'notes' => $payload['notes'],
                'recorded_by_user_id' => $chairman->user_id,
            ]);
        });

        return redirect()
            ->route('commission-sessions.oral.edit', [$competition, $application])
            ->with('success', 'Nacrt usmenog predstavljanja je ažuriran.');
    }

    public function complete(Request $request, Competition $competition, Application $application): RedirectResponse
    {
        $chairman = $this->authorizeOralAccess($competition, $application);
        $payload = $this->validatedCompletePayload($request);

        DB::transaction(function () use ($competition, $application, $chairman, $payload) {
            $lockedApplication = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            $session = CommissionSession::query()
                ->where('competition_id', $competition->id)
                ->where('session_type', CommissionSession::TYPE_SECOND)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                abort(404);
            }

            $session->unsetRelation('attendances');
            $session->load(['attendances.member']);

            if (! $session->isConfirmed() || ! $session->meetsSecondSessionAttendance($competition)) {
                throw ValidationException::withMessages([
                    'oral' => CommissionProfileConfig::ORAL_SESSION_NOT_CONFIRMED_MESSAGE,
                ]);
            }

            $this->assertOralMayBeDrafted($lockedApplication, $session);

            $oral = ApplicationOralPresentation::query()
                ->where('application_id', $lockedApplication->id)
                ->lockForUpdate()
                ->first();

            if ($oral === null) {
                throw ValidationException::withMessages([
                    'oral' => CommissionProfileConfig::ORAL_NOT_ELIGIBLE_MESSAGE,
                ]);
            }

            if ($oral->isCompleted()) {
                throw ValidationException::withMessages([
                    'oral' => CommissionProfileConfig::ORAL_LOCKED_MESSAGE,
                ]);
            }

            $attended = $payload['applicant_attended'];
            $heldAt = $payload['held_at'] ?? $oral->held_at;
            if ($attended === true && $heldAt === null) {
                throw ValidationException::withMessages([
                    'held_at' => CommissionProfileConfig::ORAL_HELD_AT_REQUIRED_MESSAGE,
                ]);
            }

            $oral->update([
                'scheduled_at' => $payload['scheduled_at'] ?? $oral->scheduled_at,
                'held_at' => $attended === true ? $heldAt : ($payload['held_at'] ?? $oral->held_at),
                'applicant_attended' => $attended,
                'notes' => $payload['notes'] ?? $oral->notes,
                'completed_at' => now(),
                'recorded_by_user_id' => $chairman->user_id,
            ]);
        });

        return redirect()
            ->route('commission-sessions.oral.edit', [$competition, $application])
            ->with('success', 'Evidencija usmenog predstavljanja je završena.');
    }

    protected function authorizeOralAccess(Competition $competition, Application $application): CommissionMember
    {
        if (! $competition->isOmladinskoProfile()) {
            abort(403, CommissionProfileConfig::SESSION_SECOND_NOT_YOUTH_MESSAGE);
        }

        if ((int) $application->competition_id !== (int) $competition->id) {
            abort(404);
        }

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

    protected function assertOralMayBeDrafted(Application $application, CommissionSession $session): void
    {
        $message = $this->gate->oralPresentationBlockMessage($application, $session);
        if ($message !== null) {
            throw ValidationException::withMessages([
                'oral' => $message,
            ]);
        }
    }

    /**
     * @param  array{scheduled_at: ?string, held_at: ?string, applicant_attended: ?bool, notes: ?string}  $payload
     */
    protected function rejectHeldFieldsBeforeConfirm(CommissionSession $session, array $payload): void
    {
        if ($session->isConfirmed()) {
            return;
        }

        if ($payload['held_at'] !== null || $payload['applicant_attended'] !== null) {
            throw ValidationException::withMessages([
                'held_at' => CommissionProfileConfig::ORAL_HELD_AT_BEFORE_CONFIRM_MESSAGE,
            ]);
        }
    }

    /**
     * @return array{scheduled_at: ?string, held_at: ?string, applicant_attended: ?bool, notes: ?string}
     */
    protected function validatedDraftPayload(Request $request, bool $requireHeldFields): array
    {
        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'held_at' => $requireHeldFields ? 'required|date' : 'nullable|date',
            'applicant_attended' => 'nullable|in:0,1,true,false',
            'notes' => 'nullable|string',
        ]);

        return [
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'held_at' => $validated['held_at'] ?? null,
            'applicant_attended' => array_key_exists('applicant_attended', $validated) && $validated['applicant_attended'] !== null
                ? filter_var($validated['applicant_attended'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return array{scheduled_at: ?string, held_at: ?string, applicant_attended: bool, notes: ?string}
     */
    protected function validatedCompletePayload(Request $request): array
    {
        $validated = $request->validate([
            'scheduled_at' => 'nullable|date',
            'held_at' => 'nullable|date',
            'applicant_attended' => 'required|in:0,1,true,false',
            'notes' => 'nullable|string',
        ]);

        $attended = filter_var($validated['applicant_attended'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($attended === null) {
            throw ValidationException::withMessages([
                'applicant_attended' => CommissionProfileConfig::ORAL_ATTENDANCE_REQUIRED_MESSAGE,
            ]);
        }

        if ($attended === true && empty($validated['held_at'])) {
            throw ValidationException::withMessages([
                'held_at' => CommissionProfileConfig::ORAL_HELD_AT_REQUIRED_MESSAGE,
            ]);
        }

        return [
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'held_at' => $validated['held_at'] ?? null,
            'applicant_attended' => $attended,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    protected function throwOralConflictOrRethrow(QueryException $e): never
    {
        if (! NamedMysqlUniqueViolation::matches($e, ApplicationOralPresentation::APPLICATION_UNIQUE_INDEX)) {
            throw $e;
        }

        throw ValidationException::withMessages([
            'oral' => CommissionProfileConfig::ORAL_CONFLICT_MESSAGE,
        ]);
    }
}
