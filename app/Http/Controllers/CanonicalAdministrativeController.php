<?php

namespace App\Http\Controllers;

use App\Models\CanonicalAdministrativeEvent;
use App\Models\Competition;
use App\Services\Competitions\Commission\CanonicalCommissionGovernance;
use App\Services\Competitions\Decision\CanonicalAdministrativeGate;
use App\Services\Competitions\Decision\CanonicalCloseService;
use App\Services\Competitions\Decision\CanonicalFormalDecisionService;
use App\Services\Competitions\Decision\CanonicalResultPublicationService;
use App\Services\Competitions\Decision\CanonicalSecretariatHandoffService;
use App\Services\Competitions\Outcome\CanonicalPredlogService;
use App\Services\Competitions\ProcessWriteResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KN-SYNC-009 — thin orchestration of the canonical finish line:
 * Predlog finalization → Secretariat handoff → formal Odluka → signed copy → publication → close.
 * All gates live in the services.
 */
class CanonicalAdministrativeController extends Controller
{
    public function __construct(
        private readonly CanonicalCommissionGovernance $governance,
        private readonly CanonicalAdministrativeGate $gate,
        private readonly CanonicalPredlogService $predlogs,
        private readonly CanonicalSecretariatHandoffService $handoffs,
        private readonly CanonicalFormalDecisionService $decisions,
        private readonly CanonicalResultPublicationService $publication,
        private readonly CanonicalCloseService $closer,
    ) {
    }

    public function commission(Request $request, Competition $competition): View
    {
        $this->canonicalOrAbort($competition);
        if ($this->governance->activeMemberFor($request->user(), $competition) === null) {
            abort(403);
        }

        return $this->screen($competition, 'commission');
    }

    public function secretariat(Request $request, Competition $competition): View
    {
        $this->canonicalOrAbort($competition);
        if ($this->gate->secretariatBlockReason($request->user()) !== null) {
            abort(403);
        }

        return $this->screen($competition, 'secretariat');
    }

    public function finalizePredlog(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);

        return $this->respond($this->predlogs->finalize($competition, $request->user()), 'Predlog je finalizovan.');
    }

    public function handOff(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $data = $request->validate([
            'recipient' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->respond(
            $this->handoffs->handOff($competition, $request->user(), $data['recipient'], $data['reference'] ?? null, $data['note'] ?? null),
            'Predlog je predat Sekretarijatu.',
        );
    }

    public function recordDecision(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $data = $request->validate([
            'decision_number' => ['required', 'string', 'max:128'],
            'decision_date' => ['required', 'date_format:Y-m-d'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->respond(
            $this->decisions->record($competition, $request->user(), $data['decision_number'], $data['decision_date'], $data['issuer'] ?? null, $data['notes'] ?? null),
            'Formalna Odluka je evidentirana.',
        );
    }

    public function attachSignedCopy(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);
        $data = $request->validate([
            'signed_copy' => ['required', 'file', 'mimes:pdf', 'max:'.CanonicalFormalDecisionService::MAX_PDF_KB],
        ]);

        return $this->respond(
            $this->decisions->attachSignedCopy($competition, $request->user(), $data['signed_copy']),
            'Potpisani primjerak Odluke je priložen.',
        );
    }

    public function publish(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);

        $success = $competition->isCanonicalTestMode()
            ? 'TESTNA objava je evidentirana. Nije kreiran javni rezultat niti javno Obavještenje.'
            : 'Potpisani primjerak formalne Odluke je objavljen.';

        return $this->respond($this->publication->publish($competition, $request->user()), $success);
    }

    public function close(Request $request, Competition $competition): RedirectResponse
    {
        $this->canonicalOrAbort($competition);

        return $this->respond($this->closer->close($competition, $request->user()), 'Poziv je zatvoren i arhiviran.');
    }

    private function screen(Competition $competition, string $audience): View
    {
        return view('evaluation.canonical-administrative', [
            'competition' => $competition,
            'audience' => $audience,
            'predlog' => $this->predlogs->predlogFor($competition),
            'finalizeBlock' => $this->predlogs->finalizationBlockReason($competition),
            'handoff' => $this->handoffs->handoffFor($competition),
            'handoffBlock' => $this->handoffs->blockReason($competition),
            'decision' => $this->decisions->decisionFor($competition),
            'recordBlock' => $this->decisions->recordBlockReason($competition),
            'publishBlock' => $this->publication->blockReason($competition),
            'closeBlock' => $this->closer->blockReason($competition),
            'publicResult' => $this->publication->publicResult($competition),
            'testPublication' => $this->publication->testPublicationEvent($competition),
            'events' => CanonicalAdministrativeEvent::query()->where('competition_id', $competition->id)->orderBy('id')->get(),
        ]);
    }

    private function respond(ProcessWriteResult $result, string $success): RedirectResponse
    {
        return $result->succeeded
            ? back()->with('success', $success)
            : back()->withErrors(['error' => $result->error]);
    }

    private function canonicalOrAbort(Competition $competition): void
    {
        if (! $this->gate->isCanonical($competition)) {
            abort(404);
        }
    }
}
