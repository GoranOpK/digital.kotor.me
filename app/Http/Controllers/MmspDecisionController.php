<?php

namespace App\Http\Controllers;

use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\MmspDecisionDocument;
use App\Models\User;
use App\Services\Competitions\Decision\MmspDecisionCloseService;
use App\Services\Competitions\MmspDecisionDocumentStore;
use App\Services\Competitions\MmspDecisionDocumentStoreException;
use App\Services\Competitions\MmspDecisionGenerator;
use App\Services\Competitions\MmspDecisionProposalGate;
use App\Services\Competitions\MmspDecisionSessionDates;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * MMSP stranica odluke i predsjedničkog zatvaranja.
 * Ne dijeli omladinski tok i ne mijenja hasChairmanCompletedDecisions().
 */
class MmspDecisionController extends Controller
{
    public function show(Competition $competition): View
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            abort(403, MmspDecisionProposalGate::NOT_MMSP_MESSAGE);
        }

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403, 'Niste član komisije tog Poziva.');
        }

        $member = CommissionMember::activeForCompetition($user->id, $competition);
        if ($member === null) {
            abort(403, 'Niste član komisije tog Poziva.');
        }

        if (! $competition->isApplicationDeadlinePassed() && ! in_array($competition->status, ['closed', 'completed'], true)) {
            abort(403, 'Rok za prijave još nije istekao.');
        }

        $gate = app(MmspDecisionProposalGate::class);
        $canGenerate = $gate->denyReason($competition, $user) === null;
        $isChairman = $member->canonical_seat === \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
            && $member->position === 'predsjednik';
        $rankingFinal = $gate->rankingIsFinal($competition);
        $existingCloseConditions = $isChairman
            && ! in_array($competition->status, ['closed', 'completed'], true)
            && $rankingFinal
            && $competition->deadline
            && now()->isAfter($competition->deadline);
        $validDocument = null;
        if ($isChairman) {
            try {
                $validDocument = app(MmspDecisionDocumentStore::class)->latestValidDocument($competition);
            } catch (MmspDecisionDocumentStoreException) {
                $validDocument = null;
            }
        }

        return view('evaluation.mmsp-decision', [
            'competition' => $competition,
            'canGenerateMmspDecision' => $canGenerate,
            'canDownloadMmspDecision' => $isChairman && $validDocument !== null,
            'mmspDecisionDownloadVersion' => $validDocument?->getKey(),
            'canCloseMmspCompetition' => $existingCloseConditions && $validDocument !== null,
            'mmspCloseBlockedByDocument' => $existingCloseConditions && $validDocument === null,
            'mmspCloseDocumentMessage' => MmspDecisionDocumentStore::CLOSE_BLOCKED,
            'mmspDecisionDates' => $canGenerate
                ? app(MmspDecisionSessionDates::class)->forCompetition($competition)
                : ['first' => null, 'second' => null, 'third' => null],
        ]);
    }

    public function generate(
        Request $request,
        Competition $competition,
        MmspDecisionProposalGate $gate,
        MmspDecisionGenerator $generator,
    ): RedirectResponse {
        $user = Auth::user();
        $reason = $gate->denyReason($competition, $user instanceof User ? $user : null);
        if ($reason !== null || ! $user instanceof User) {
            abort(403, $reason ?? MmspDecisionProposalGate::CHAIRMAN_ONLY_MESSAGE);
        }

        $recordedFirst = app(MmspDecisionSessionDates::class)->forCompetition($competition)['first'] ?? null;
        if (! is_string($recordedFirst) || $recordedFirst === '') {
            return back()->withErrors([
                'first_session_date' => 'Datum prve sjednice još nije evidentiran završetkom administrativne provjere.',
            ])->withInput();
        }

        $validated = $request->validate([
            'second_session_date' => ['required', 'date_format:Y-m-d'],
            'third_session_date' => ['required', 'date_format:Y-m-d'],
        ], [
            'second_session_date.required' => 'Unesite datum druge sjednice.',
            'third_session_date.required' => 'Unesite datum treće sjednice.',
            'second_session_date.date_format' => 'Datum druge sjednice nije ispravan.',
            'third_session_date.date_format' => 'Datum treće sjednice nije ispravan.',
        ]);
        $validated['first_session_date'] = $recordedFirst;

        try {
            $document = $generator->generate($competition, $user, $validated, now());
        } catch (HttpException|ValidationException $exception) {
            throw $exception;
        } catch (MmspDecisionDocumentStoreException $exception) {
            return back()->withErrors([
                'decision' => $this->publicMessage($exception->getMessage()),
            ])->withInput();
        } catch (Throwable) {
            return back()->withErrors([
                'decision' => 'Odluka nije generisana i nije trajno sačuvana.',
            ])->withInput();
        }

        return redirect()
            ->route('evaluation.mmsp-decision', $competition)
            ->with('success', $this->generatedMessage($document));
    }

    public function download(Competition $competition, MmspDecisionDocumentStore $store): Response
    {
        $user = Auth::user();
        if (! $user instanceof User || $this->notChairman($competition, $user)) {
            abort(403, 'Samo aktivni predsjednik Komisije može preuzeti odluku.');
        }

        try {
            $document = $store->latestValidDocument($competition);
        } catch (MmspDecisionDocumentStoreException $exception) {
            if ($exception->getMessage() === MmspDecisionDocumentStore::HASH_MISMATCH) {
                abort(409, MmspDecisionDocumentStore::HASH_MISMATCH);
            }

            abort(404);
        }

        if ($document === null) {
            abort(404);
        }

        try {
            $absolute = $store->verifiedDownloadPath($competition, $document);
        } catch (MmspDecisionDocumentStoreException $exception) {
            if ($exception->getMessage() === MmspDecisionDocumentStore::HASH_MISMATCH) {
                abort(409, MmspDecisionDocumentStore::HASH_MISMATCH);
            }

            abort(404);
        }

        $response = response()->download($absolute, 'Odluka-o-dodjeli-subvencija-MMSP-'.$competition->year.'-'.$competition->id.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    private function generatedMessage(MmspDecisionDocument $document): string
    {
        $generatedAt = $document->generated_at?->timezone((string) config('app.timezone'))->format('d.m.Y. H:i:s');

        return 'Nova Word odluka je uspješno generisana i sačuvana. Vrijeme generisanja: '.$generatedAt.'.';
    }

    public function close(Competition $competition, MmspDecisionCloseService $close): RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403, MmspDecisionProposalGate::CHAIRMAN_ONLY_MESSAGE);
        }

        $result = $close->close($competition, $user);
        if (! $result->succeeded) {
            return redirect()->back()->withErrors(['error' => $result->error]);
        }

        return redirect()->back()->with('success', 'Konkurs je uspješno zatvoren. Status konkursa je Završen.');
    }

    private function notChairman(Competition $competition, User $user): bool
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL || ! $competition->commission_id) {
            return true;
        }

        $member = CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id);

        return $member === null
            || $member->canonical_seat !== \App\Services\Competitions\Commission\CanonicalCommissionGovernance::ROLE_PREDSJEDNIK
            || $member->position !== 'predsjednik'
            || $member->status !== 'active';
    }

    private function publicMessage(string $message): string
    {
        $allowed = [
            MmspDecisionDocumentStore::DISK_UNAVAILABLE,
            MmspDecisionDocumentStore::UNREADABLE,
            MmspDecisionDocumentStore::EMPTY_DOCUMENT,
            MmspDecisionDocumentStore::NOT_MMSP,
            MmspDecisionDocumentStore::NOT_CHAIRMAN,
            MmspDecisionDocumentStore::WRITE_FAILED,
            MmspDecisionDocumentStore::NOT_OVERWRITTEN,
            MmspDecisionDocumentStore::HASH_MISMATCH,
            MmspDecisionDocumentStore::NOT_STORED,
        ];

        return in_array($message, $allowed, true)
            ? $message
            : 'Odluka nije generisana i nije trajno sačuvana.';
    }
}
