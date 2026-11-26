<?php

namespace App\Http\Controllers;

use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityReturnTo;
use App\Identity\Runtime\IdentityUseGateException;
use App\Models\Application;
use App\Models\CanonicalApplicantForm;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\User;
use App\Models\UserDocument;
use App\Security\JmbEncryptedReadException;
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

        return redirect()->route('competitions.show', $competition);
    }

    public function start(Request $request, Competition $competition): RedirectResponse
    {
        if ($redirect = $this->entryRedirect($request, $competition)) {
            return $redirect;
        }

        $result = $this->forms->start($competition, $request->user(), $request->input('legal_form'));
        if (! $result->succeeded) {
            return redirect()->route('competitions.show', $competition)
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('applications.canonical-p1.edit', $result->recordId)
            ->with('success', 'Prijava je započeta. Popunite Obrazac P1.');
    }

    public function editP1(Request $request, Application $application): View|RedirectResponse
    {
        $readOnlyVisitor = $this->authorizeCanonicalFormRead($request, $application);

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        abort_unless($form && CanonicalLegalForm::isValid($form->legal_form), 404);
        $readOnly = $readOnlyVisitor || $form->isFrozen() || $application->isApplicantContentWriteLocked();
        if (! $readOnly) {
            abort_unless($this->forms->legalFormForUser($request->user()) === $form->legal_form, 403);
        }

        $jmbgForForm = null;
        $verifiedJmbg = null;
        $snapshotJmbg = null;
        $jmbgBlocked = false;
        $jmbgMustMatchPrefill = false;
        $jmbgEmptyOk = true;
        if ($form->isEntrepreneur() && ! $readOnlyVisitor) {
            try {
                $accountJmb = app(CurrentIdentityResolver::class)->viewFor($request->user())->jmb;
                if (is_string($accountJmb) && preg_match('/^[0-9]{13}$/', trim($accountJmb)) === 1) {
                    $verifiedJmbg = trim($accountJmb);
                }
                if ($application->hasApplicantJmbgSnapshot()) {
                    $snapshotJmbg = $application->applicantJmbgForRead();
                    $jmbgForForm = $snapshotJmbg;
                } else {
                    $jmbgForForm = $accountJmb;
                }
                $displayedJmbg = old('applicant_jmbg', $jmbgForForm);
                $displayedJmbg = is_scalar($displayedJmbg) ? trim((string) $displayedJmbg) : '';
                $snapshotUnusable = is_string($snapshotJmbg) && preg_match('/^[0-9]{13}$/', $snapshotJmbg) !== 1;
                $snapshotMismatch = is_string($verifiedJmbg) && is_string($snapshotJmbg) && $snapshotJmbg !== $verifiedJmbg;
                $displayedMatchesVerified = is_string($verifiedJmbg) && $displayedJmbg === $verifiedJmbg;
                $jmbgBlocked = $snapshotUnusable || $snapshotMismatch || (is_string($verifiedJmbg) && ! $displayedMatchesVerified);
                $jmbgMustMatchPrefill = $displayedMatchesVerified && ! $snapshotUnusable && ! $snapshotMismatch;
                $jmbgEmptyOk = ! $snapshotUnusable && ! $snapshotMismatch;
            } catch (JmbEncryptedReadException) {
                return redirect()->route('applications.show', $application)
                    ->withErrors(['error' => JmbEncryptedReadException::USER_MESSAGE]);
            }
        } elseif ($form->isEntrepreneur() && $application->hasApplicantJmbgSnapshot()) {
            $jmbgForForm = $application->applicantJmbgForRead();
        }

        return view('applications.canonical-p1', [
            'application' => $application,
            'form' => $form,
            'subsidyTypes' => CanonicalSubsidyType::labels(),
            'accuracyStatement' => CanonicalApplicantFormService::accuracyStatement($form->legal_form),
            'readOnly' => $readOnly,
            'jmbgForForm' => $jmbgForForm,
            'jmbgBlocked' => $jmbgBlocked,
            'jmbgMustMatchPrefill' => $jmbgMustMatchPrefill,
            'jmbgEmptyOk' => $jmbgEmptyOk,
            'p2SubsidyType' => $application->canonicalInvestmentPlan?->subsidy_type,
            'p1ReadyForPlan' => $this->forms->finalBlockReason($application) === null,
            'p1RequiredFields' => array_keys($this->forms->requiredFields($form->legal_form)),
        ]);
    }

    public function updateP1(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwnerOnCanonical($request, $application);

        $form = CanonicalApplicantForm::query()->where('application_id', $application->id)->first();
        abort_unless($form && $this->forms->legalFormForUser($request->user()) === $form->legal_form, 403);

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
            'applicant_jmbg',
        ]));

        if (! $result->succeeded) {
            return back()->withErrors(['error' => $result->error])->withInput();
        }

        $saved = $application->fresh();
        $savedForm = CanonicalApplicantForm::query()->where('application_id', $saved->id)->first();
        if ($savedForm && $request->boolean('continue_to_plan')) {
            $block = $this->forms->finalBlockReason($saved);
            if ($block !== null) {
                return redirect()
                    ->route('applications.canonical-p1.edit', $saved)
                    ->withErrors(['error' => $block]);
            }

            return redirect()
                ->route('applications.canonical-p2.edit', $saved)
                ->with('success', 'Obrazac '.$savedForm->formCode().' je sačuvan. Nastavite sa Planom ulaganja (Obrazac P2).');
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

    /**
     * Owner keeps the existing write path. A commission member or administrator may open
     * the saved MMSP form read-only after the application is finally submitted.
     */
    private function authorizeCanonicalFormRead(Request $request, Application $application): bool
    {
        $application->loadMissing(['competition', 'finalClaim']);
        if (! $application->isCanonicalProfile()) {
            abort(404);
        }

        $user = $request->user();
        if ($user instanceof User && (int) $application->user_id === (int) $user->id) {
            return false;
        }

        if ($user instanceof User && $this->commissionOrAdminMayView($application, $user)) {
            return true;
        }

        abort(403, 'Nemate pristup ovoj prijavi.');
    }

    private function commissionOrAdminMayView(Application $application, User $user): bool
    {
        $competition = $application->competition;
        if ($competition === null || $application->status === 'draft') {
            return false;
        }

        if (! $competition->isCanonicalTestPreview()
            && ! $competition->isApplicationDeadlinePassed()
            && ! in_array($competition->status, ['closed', 'completed'], true)) {
            return false;
        }

        $role = $user->role?->name;
        if (in_array($role, ['admin', 'superadmin', 'konkurs_admin', 'kk_admin'], true)) {
            return true;
        }

        if (! $competition->commission_id) {
            return false;
        }

        return CommissionMember::activeForCommission((int) $user->id, (int) $competition->commission_id) !== null;
    }
}
