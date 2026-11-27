<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationComplaint;
use App\Models\ApplicationCompletenessAssessment;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\ComplaintService;
use App\Services\Competitions\CompletenessAssessmentService;
use App\Support\MmspP3Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin canonical completeness/complaint HTTP surface. Orchestration only: authority
 * (current canonical Commission chair), deadlines, stage lock and immutability live in
 * CompletenessAssessmentService / ComplaintService / CanonicalCommissionGovernance.
 * Does not replace EvaluationController Women/Youth Obrazac 3 / Prigovor paths.
 */
class ApplicationProcessController extends Controller
{
    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CompletenessAssessmentService $assessments,
        private readonly ComplaintService $complaints,
    ) {
    }

    public function recordCompleteness(Request $request, Application $application): RedirectResponse
    {
        $user = $request->user();
        $blockReason = $this->governance->completenessRecordBlockReason($user, $application);
        if ($blockReason !== null) {
            abort(403, $blockReason);
        }

        if ($request->exists('criterion_documentation')) {
            $rules = ['notes' => 'nullable|string|max:5000'];
            foreach (MmspP3Form::CRITERIA as $criterion) {
                $rules[$criterion['field']] = 'required|in:da,ne';
            }
            $criteria = $request->validate($rules);
            $failed = [];
            $answers = [];
            foreach (MmspP3Form::CRITERIA as $key => $criterion) {
                $value = $criteria[$criterion['field']];
                $answers[$key] = $value;
                if ($value === 'ne') {
                    $failed[] = $criterion['fail_phrase'];
                }
            }
            $notes = trim((string) ($criteria['notes'] ?? ''));
            if ($failed !== [] && $notes === '') {
                return back()->withInput()->withErrors([
                    'notes' => CompletenessAssessmentService::REASON_REQUIRED_MESSAGE,
                ]);
            }
            $outcome = $failed === []
                ? ApplicationCompletenessAssessment::COMPLETE
                : ApplicationCompletenessAssessment::INCOMPLETE;
            if ($failed !== []) {
                $notes = 'Nije ispunjeno: '.implode('; ', $failed).($notes !== '' ? "\n".$notes : '');
            }
        } else {
            $validated = $request->validate([
                'outcome' => 'required|in:'.ApplicationCompletenessAssessment::COMPLETE.','.ApplicationCompletenessAssessment::INCOMPLETE,
                'notes' => 'nullable|string|max:5000',
            ]);
            $outcome = $validated['outcome'];
            $notes = $validated['notes'] ?? null;
            $answers = null;
        }

        $result = $this->assessments->record($application, $user, $outcome, $notes, $answers);
        if (! $result->succeeded) {
            return back()->withInput()->withErrors(['error' => $result->error]);
        }

        return back()->with('success', 'Potpunost je evidentirana.');
    }

    public function submitComplaint(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $result = $this->complaints->submit($application, $request->user(), $validated['body']);
        if (! $result->succeeded) {
            return back()->withInput()->withErrors(['error' => $result->error]);
        }

        return back()->with('success', 'Prigovor je evidentiran. Podneseni dosije nije mijenjan.');
    }

    public function decideComplaint(Request $request, Application $application, ApplicationComplaint $complaint): RedirectResponse
    {
        if ((int) $complaint->application_id !== (int) $application->id) {
            abort(404);
        }

        $user = $request->user();
        $blockReason = $this->governance->complaintDecisionBlockReason($user, $application);
        if ($blockReason !== null) {
            abort(403, $blockReason);
        }

        $validated = $request->validate([
            'decision' => 'required|in:'.ApplicationComplaint::ACCEPTED.','.ApplicationComplaint::REJECTED,
            'decision_notes' => 'required|string|max:5000',
        ], [
            'decision_notes.required' => ComplaintService::DECISION_NOTES_REQUIRED_MESSAGE,
        ]);

        $result = $this->complaints->decide($complaint, $user, $validated['decision'], $validated['decision_notes']);
        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error]);
        }

        $redirect = back()->with('success', 'Odluka o prigovoru je evidentirana.');
        if ($result->warning) {
            $redirect->with('warning', $result->warning);
        }

        return $redirect;
    }

    /**
     * Applicant: own canonical application only.
     */
    public function applicantStatus(Request $request, Application $application): RedirectResponse
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 403, 'Status provjere vidi samo podnosilac prijave.');
        abort_if($this->complaints->applicantStatus($application) === null, 404);

        // Kao kod ženskog i mladih: status, obavještenje i prigovor su dio
        // jedinstvenog korisničkog ekrana "Status prijave".
        return redirect()->to(route('applications.show', $application).'#application-review');
    }

    public function commissionIndex(Request $request, Competition $competition): View
    {
        $this->reviewAccessOrAbort($request, $competition);

        return view('evaluation.canonical-review-index', [
            'competition' => $competition,
            'rows' => $this->assessments->competitionReview($competition),
            'readOnly' => false,
        ]);
    }

    public function commissionShow(Request $request, Application $application): View
    {
        $application->loadMissing('competition');
        $this->reviewAccessOrAbort($request, $application->competition);

        return view('evaluation.canonical-review', array_merge(
            ['competition' => $application->competition, 'readOnly' => false],
            $this->assessments->reviewOverview($application, $request->user(), $this->complaints),
        ));
    }

    public function adminIndex(Competition $competition): View
    {
        abort_unless($this->governance->isCanonicalCompetition($competition), 403, CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);

        return view('evaluation.canonical-review-index', [
            'competition' => $competition,
            'rows' => $this->assessments->competitionReview($competition),
            'readOnly' => true,
        ]);
    }

    public function adminShow(Competition $competition, Application $application): View
    {
        abort_unless($this->governance->isCanonicalCompetition($competition), 403, CanonicalCommissionGovernance::NOT_CANONICAL_MESSAGE);
        abort_unless((int) $application->competition_id === (int) $competition->id, 404);

        return view('evaluation.canonical-review', array_merge(
            ['competition' => $competition, 'readOnly' => true],
            $this->assessments->reviewOverview($application, null, $this->complaints),
        ));
    }

    private function reviewAccessOrAbort(Request $request, ?Competition $competition): void
    {
        $blockReason = $this->governance->reviewAccessBlockReason($request->user(), $competition);
        if ($blockReason !== null) {
            abort(403, $blockReason);
        }
    }
}
