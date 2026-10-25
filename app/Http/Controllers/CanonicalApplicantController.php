<?php

namespace App\Http\Controllers;

use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use App\Identity\Runtime\IdentityUseGateException;
use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\Competition;
use App\Models\UserDocument;
use App\Services\Competitions\CanonicalApplicantFormService;
use App\Services\Competitions\CanonicalBeneficiaryApplications;
use App\Services\Competitions\CanonicalDocumentService;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CanonicalLegalForm;
use App\Support\CanonicalSubsidyType;
use App\Support\CompetitionProgramCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Thin canonical MMSP applicant surface: legal form → Obrazac P1a/P1b → (P2) → čl.14 prilozi.
 * Women/Youth never reach these pages; writes go through the canonical services.
 */
class CanonicalApplicantController extends Controller
{
    public function __construct(
        private readonly CanonicalApplicantFormService $forms,
        private readonly CanonicalDocumentService $documents,
    ) {
    }

    public function legalForm(Request $request, Competition $competition): View|RedirectResponse
    {
        if ($redirect = $this->entryRedirect($request, $competition)) {
            return $redirect;
        }

        // Izbor P1a/P1b se radi na stranici MMSP konkursa.
        return redirect()->route('competitions.show', $competition);
    }

    public function start(Request $request, Competition $competition): RedirectResponse
    {
        if ($redirect = $this->entryRedirect($request, $competition)) {
            return $redirect;
        }

        $legalForm = $request->input('legal_form');
        if (! CanonicalLegalForm::isValid($legalForm)) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['legal_form' => CanonicalApplicantFormService::LEGAL_FORM_REQUIRED_MESSAGE]);
        }

        // Za MMSP korisnik bira da li podnosi prijavu kao preduzetnik (P1a)
        // ili privredno društvo (P1b), nezavisno od tipa naloga na Digitalu.
        $result = $this->forms->start($competition, $request->user(), $legalForm);
        if (! $result->succeeded) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('applications.canonical-p1.edit', $result->recordId)
            ->with('success', 'Prijava je započeta. Popunite Obrazac P1.');
    }

    public function editP1(Request $request, Application $application): View
    {
        $this->authorizeOwnerOnCanonical($request, $application);

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        abort_unless($form && CanonicalLegalForm::isValid($form->legal_form), 404);

        return view('applications.canonical-p1', [
            'application' => $application,
            'form' => $form,
            'subsidyTypes' => CanonicalSubsidyType::labels(),
            'accuracyStatement' => CanonicalApplicantFormService::accuracyStatement($form->legal_form),
            'readOnly' => $form->isFrozen() || $application->isApplicantContentWriteLocked(),
        ]);
    }

    public function updateP1(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwnerOnCanonical($request, $application);

        $result = $this->forms->save($application, $request->user(), $request->only([
            'subsidy_type',
            'person_name',
            'company_name',
            'pib',
            'address',
            'activity_code',
            'employee_count',
            'bank_account',
            'phone',
            'email',
            'website',
            'registration_date',
            'accuracy_confirmed',
        ]));

        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error])->withInput();
        }

        return redirect()->route('dashboard')
            ->with('success', 'Prijava je sačuvana kao nacrt. U bilo kom trenutku je možete nastaviti iz sekcije "Moje prijave".');
    }

    public function documents(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwnerOnCanonical($request, $application);

        // Kao kod ženskog i mladih: dokumentacija se dodaje i pregleda na ekranu
        // "Status prijave", bez posebnog MMSP korisničkog ekrana.
        return redirect()->to(route('applications.show', $application).'#application-documents');
    }

    /**
     * Shared applicant entry gates: catalog availability, identity, open draft continuation, start block.
     */
    private function entryRedirect(Request $request, Competition $competition): ?RedirectResponse
    {
        if ($competition->type !== CompetitionProfileResolver::TYPE_CANONICAL) {
            abort(404);
        }

        if (! $competition->isPubliclyAccessibleForApplications()) {
            return redirect()->route('competitions.index')
                ->withErrors(['error' => CompetitionProgramCatalog::PROFILE_UNAVAILABLE_FOR_APPLICATIONS_MESSAGE]);
        }

        $user = $request->user();
        $identityResolver = app(CurrentIdentityResolver::class);
        if ($identityResolver->gatesSubjectFlows()) {
            try {
                $identityResolver->requireCurrentSubject($user);
            } catch (IdentityUseGateException $e) {
                if (app(ExistingSubjectIdentityEligibility::class)->isEligible($user)) {
                    app(ExistingSubjectIdentityReturnTo::class)->remember(
                        route('applications.canonical.legal-form', $competition, absolute: false)
                    );

                    return redirect()->route('identity.completion.create');
                }

                return redirect()->route('competitions.show', $competition)
                    ->withErrors(['error' => $e->getMessage()]);
            }
        }

        $block = $this->forms->startBlockReason($competition, $user);
        if ($block === CanonicalApplicantFormService::OPEN_DRAFT_EXISTS_MESSAGE) {
            $draft = app(CanonicalBeneficiaryApplications::class)->userApplicationForCompetition($competition, (int) $user->id);

            return redirect()->route('applications.show', $draft)
                ->with('info', 'Već imate započetu prijavu. Možete je nastaviti popunjavati.');
        }
        if ($block !== null) {
            return redirect()->route('competitions.show', $competition)->withErrors(['error' => $block]);
        }

        return null;
    }

    private function authorizeOwnerOnCanonical(Request $request, Application $application): void
    {
        if ((int) $application->user_id !== (int) $request->user()?->id) {
            abort(403, 'Nemate pristup ovoj prijavi.');
        }

        if (! $application->isCanonicalProfile()) {
            abort(404);
        }
    }
}
