<?php

namespace App\Services;

use App\Identity\Runtime\CurrentIdentityResolver;
use App\Models\Application;
use App\Models\Competition;
use App\Models\User;
use App\Support\KnApplicationClassification;
use App\Support\KnApplicationStartContext;
use App\Support\KnCommercialCompanyForm;
use App\Support\UserType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class KnApplicationStartContextFactory
{
    public function fromShowRequest(User $user, Competition $competition, Request $request): KnApplicationStartContext
    {
        $identity = app(CurrentIdentityResolver::class)->viewFor($user);
        $kn = KnApplicationClassification::fromUserType($identity->userType);

        if ($kn->isUnregisteredPhysicalPerson) {
            return $this->forUnregisteredPhysicalPerson($user, $competition, $request);
        }

        $requestedStage = $request->input('business_stage');
        if (is_string($requestedStage) && $requestedStage !== '' && ! $kn->allowsStage($requestedStage)) {
            throw ValidationException::withMessages([
                'business_stage' => 'Izabrana faza biznisa nije dozvoljena za ovaj identitet.',
            ]);
        }
        $stage = $kn->resolveBusinessStage(is_string($requestedStage) ? $requestedStage : null);

        if ($kn->isExistingEntrepreneur) {
            return new KnApplicationStartContext(
                userId: (int) $user->id,
                competitionId: (int) $competition->id,
                intent: KnApplicationStartContext::INTENT_CANONICAL_ENTREPRENEUR,
                applicantType: KnApplicationClassification::FORM_PREDUZETNICA,
                isRegistered: true,
                businessStage: $stage,
                commercialForm: null,
                registrationForm: 'Preduzetnik',
                targetForm: KnApplicationStartContext::TARGET_1A,
                stageLocked: false,
            );
        }

        $commercial = KnCommercialCompanyForm::fromUserType($identity->userType);
        if ($commercial !== null) {
            return new KnApplicationStartContext(
                userId: (int) $user->id,
                competitionId: (int) $competition->id,
                intent: KnApplicationStartContext::INTENT_CANONICAL_COMPANY,
                applicantType: KnCommercialCompanyForm::applicantTypeFor($commercial),
                isRegistered: true,
                businessStage: $stage,
                commercialForm: $commercial,
                registrationForm: KnCommercialCompanyForm::registrationFormLabel($commercial),
                targetForm: KnApplicationStartContext::TARGET_1B,
                stageLocked: false,
            );
        }

        return new KnApplicationStartContext(
            userId: (int) $user->id,
            competitionId: (int) $competition->id,
            intent: KnApplicationStartContext::INTENT_LEGACY_OTHER,
            applicantType: KnApplicationClassification::FORM_OSTALO,
            isRegistered: $kn->isRegisteredBusiness,
            businessStage: $stage,
            commercialForm: null,
            registrationForm: is_string($identity->userType) && $identity->userType !== '' ? $identity->userType : null,
            targetForm: KnApplicationStartContext::TARGET_1B,
            stageLocked: false,
        );
    }

    public function fromSavedApplication(Application $application, User $user): KnApplicationStartContext
    {
        $identity = app(CurrentIdentityResolver::class)->viewFor($user);
        $kn = KnApplicationClassification::fromUserType($identity->userType);
        $applicantType = (string) $application->applicant_type;
        $registrationForm = is_string($application->registration_form) && $application->registration_form !== ''
            ? $application->registration_form
            : null;
        $commercial = KnCommercialCompanyForm::fromRegistrationForm($registrationForm);
        if ($commercial === null && $applicantType === KnApplicationClassification::FORM_DOO) {
            $commercial = KnCommercialCompanyForm::DOO;
            $registrationForm ??= KnCommercialCompanyForm::registrationFormLabel(KnCommercialCompanyForm::DOO);
        }

        $targetForm = in_array($applicantType, [
            KnApplicationClassification::FORM_FIZICKO_LICE,
            KnApplicationClassification::FORM_PREDUZETNICA,
        ], true)
            ? KnApplicationStartContext::TARGET_1A
            : KnApplicationStartContext::TARGET_1B;

        return new KnApplicationStartContext(
            userId: (int) $user->id,
            competitionId: (int) $application->competition_id,
            intent: KnApplicationStartContext::INTENT_SAVED_APPLICATION,
            applicantType: $applicantType,
            isRegistered: $kn->isRegisteredBusiness,
            businessStage: is_string($application->business_stage) && $application->business_stage !== ''
                ? $application->business_stage
                : KnApplicationClassification::STAGE_ZAPOCINJANJE,
            commercialForm: $commercial,
            registrationForm: $registrationForm,
            targetForm: $targetForm,
            stageLocked: ! $kn->isRegisteredBusiness,
        );
    }

    private function forUnregisteredPhysicalPerson(User $user, Competition $competition, Request $request): KnApplicationStartContext
    {
        $intent = (string) $request->input('planned_intent', '');

        if ($intent === KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR) {
            return new KnApplicationStartContext(
                userId: (int) $user->id,
                competitionId: (int) $competition->id,
                intent: KnApplicationStartContext::INTENT_FUTURE_ENTREPRENEUR,
                applicantType: KnApplicationClassification::FORM_FIZICKO_LICE,
                isRegistered: false,
                businessStage: KnApplicationClassification::STAGE_ZAPOCINJANJE,
                commercialForm: null,
                registrationForm: UserType::ENTREPRENEUR,
                targetForm: KnApplicationStartContext::TARGET_1A,
                stageLocked: true,
            );
        }

        if ($intent === KnApplicationStartContext::INTENT_PLANNED_COMPANY) {
            $form = (string) $request->input('planned_company_form', '');
            if (! KnCommercialCompanyForm::isValid($form)) {
                throw ValidationException::withMessages([
                    'planned_company_form' => 'Izaberite planirani pravni oblik: DOO, AD, OD ili KD.',
                ]);
            }

            return new KnApplicationStartContext(
                userId: (int) $user->id,
                competitionId: (int) $competition->id,
                intent: KnApplicationStartContext::INTENT_PLANNED_COMPANY,
                applicantType: KnCommercialCompanyForm::applicantTypeFor($form),
                isRegistered: false,
                businessStage: KnApplicationClassification::STAGE_ZAPOCINJANJE,
                commercialForm: $form,
                registrationForm: KnCommercialCompanyForm::registrationFormLabel($form),
                targetForm: KnApplicationStartContext::TARGET_1B,
                stageLocked: true,
            );
        }

        throw ValidationException::withMessages([
            'planned_intent' => 'Izaberite tip prijave prije ulaska u obrazac.',
        ]);
    }
}
