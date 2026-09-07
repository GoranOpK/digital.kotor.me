<?php

namespace App\Http\Requests;

use App\Identity\CountryCatalog;
use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\Runtime\ExistingSubjectIdentityEligibility;
use App\Identity\Runtime\ExistingSubjectIdentityEligibilityResult;
use App\Identity\Validation\CrpsIdentifierValidator;
use App\Identity\Validation\JmbIdentifierValidator;
use App\Identity\Validation\PibIdentifierValidator;
use App\Models\PhysicalPersonIdentity;
use App\Rules\ValidCrps;
use App\Rules\ValidJmb;
use App\Rules\ValidPib;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompleteExistingSubjectIdentityRequest extends FormRequest
{
    private ?ExistingSubjectIdentityEligibilityResult $eligibilityResult = null;

    public function authorize(): bool
    {
        $result = $this->eligibility();
        if ($result === null) {
            return false;
        }

        return $result->eligible || $result->isCurrent();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $result = $this->eligibility();
        if ($result === null || $result->isCurrent()) {
            return [];
        }

        $phoneRules = $this->phoneRules();

        if ($result->branch === ExistingSubjectIdentityEligibilityResult::BRANCH_DOO) {
            return array_merge($phoneRules, $this->dooRules());
        }

        return array_merge($phoneRules, $this->preduzetnikRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $result = $this->eligibility();
            if ($result === null || $result->isCurrent() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $this->refineIdentifierMessages($validator, $result);
            $this->refinePhone($validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'legal_name.required' => 'Unesite puni naziv pravnog lica.',
            'first_name.required' => 'Unesite ime.',
            'last_name.required' => 'Unesite prezime.',
            'residential_status.required' => 'Izaberite status rezidentnosti.',
            'residential_status.in' => 'Izaberite status rezidentnosti.',
            'id_document_type.required' => 'Izaberite vrstu identifikacionog dokumenta.',
            'id_document_type.in' => 'Izaberite vrstu identifikacionog dokumenta.',
            'jmb.required' => 'Unesite JMB.',
            'jmb.regex' => 'JMB mora imati tačno 13 cifara.',
            'passport_number.required' => 'Unesite broj pasoša.',
            'residence_country_code.required' => 'Izaberite državu prebivališta.',
            'residence_country_code.in' => 'Izaberite državu prebivališta iz liste.',
            'entrepreneur_business_name.required' => 'Unesite naziv preduzetnika.',
            'pib.required' => 'Unesite PIB.',
            'pib.regex' => 'PIB mora imati tačno 8 cifara.',
            'crps_number.required' => 'Unesite CRPS registracioni broj.',
            'crps_number.regex' => 'CRPS registracioni broj mora imati tačno 8 cifara.',
            'street_and_number.required' => 'Unesite ulicu i broj.',
            'city.required' => 'Unesite grad.',
            'phone_calling_code.required' => 'Izaberite pozivni broj.',
            'phone_calling_code.in' => 'Izaberite pozivni broj.',
            'phone_national.required' => 'Unesite broj mobilnog telefona.',
            'authorized_first_name.required' => 'Unesite ime ovlašćenog lica.',
            'authorized_last_name.required' => 'Unesite prezime ovlašćenog lica.',
            'authorized_id_document_type.required' => 'Izaberite vrstu identifikacionog dokumenta ovlašćenog lica.',
            'authorized_id_document_type.in' => 'Izaberite vrstu identifikacionog dokumenta ovlašćenog lica.',
            'authorized_jmb.required' => 'Unesite JMB ovlašćenog lica.',
            'authorized_jmb.regex' => 'JMB mora imati tačno 13 cifara.',
            'authorized_passport_number.required' => 'Unesite broj pasoša ovlašćenog lica.',
            'authorized_passport_issuing_country_code.required' => 'Izaberite državu izdavanja pasoša.',
            'authorized_passport_issuing_country_code.in' => 'Izaberite državu izdavanja pasoša iz liste.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();
        $reason = is_array($failed) && $failed !== [] ? (string) array_key_first($failed) : 'validation';

        Log::info('identity.completion', [
            'user_id' => $this->user()?->id,
            'branch' => $this->eligibility()?->branch,
            'outcome' => 'validation_failed',
            'reason_code' => $reason,
        ]);

        parent::failedValidation($validator);
    }

    public function branch(): ?string
    {
        return $this->eligibility()?->branch;
    }

    public function eligibility(): ?ExistingSubjectIdentityEligibilityResult
    {
        if ($this->eligibilityResult !== null) {
            return $this->eligibilityResult;
        }

        $user = $this->user();
        if ($user === null) {
            return null;
        }

        return $this->eligibilityResult = app(ExistingSubjectIdentityEligibility::class)->inspect($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function phoneRules(): array
    {
        $codes = array_values(array_unique(array_map(
            fn (array $entry): string => $entry['calling_code'],
            PhoneCallingCodeCatalog::pickerEntries(),
        )));

        return [
            'phone_calling_code' => ['required', 'string', Rule::in($codes)],
            'phone_national' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'street_and_number' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dooRules(): array
    {
        $countryCodes = CountryCatalog::codes();
        $documentType = $this->input('authorized_id_document_type');

        $rules = [
            'legal_name' => ['required', 'string', 'max:255'],
            'pib' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib],
            'crps_number' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidCrps(CrpsIdentifierValidator::MARK_DOO)],
            'authorized_first_name' => ['required', 'string', 'max:255'],
            'authorized_last_name' => ['required', 'string', 'max:255'],
            'authorized_id_document_type' => ['required', 'string', Rule::in([
                PhysicalPersonIdentity::DOCUMENT_JMB,
                PhysicalPersonIdentity::DOCUMENT_PASSPORT,
            ])],
        ];

        if ($documentType === PhysicalPersonIdentity::DOCUMENT_JMB) {
            $rules['authorized_jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
        } elseif ($documentType === PhysicalPersonIdentity::DOCUMENT_PASSPORT) {
            $rules['authorized_passport_number'] = ['required', 'string', 'min:3', 'max:50'];
            $rules['authorized_passport_issuing_country_code'] = ['required', 'string', Rule::in($countryCodes)];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function preduzetnikRules(): array
    {
        $countryCodes = CountryCatalog::codes();
        $residential = $this->input('residential_status');

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'residential_status' => ['required', 'string', Rule::in(['resident', 'non-resident'])],
            'entrepreneur_business_name' => ['required', 'string', 'max:255'],
            'pib' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib],
            'crps_number' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidCrps(CrpsIdentifierValidator::MARK_ENTREPRENEUR)],
        ];

        if ($residential === 'resident') {
            $rules['jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
        } elseif ($residential === 'non-resident') {
            $rules['id_document_type'] = ['required', 'string', Rule::in([
                PhysicalPersonIdentity::DOCUMENT_JMB,
                PhysicalPersonIdentity::DOCUMENT_PASSPORT,
            ])];
            $rules['residence_country_code'] = ['required', 'string', Rule::in($countryCodes)];

            if ($this->input('id_document_type') === PhysicalPersonIdentity::DOCUMENT_JMB) {
                $rules['jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
            } elseif ($this->input('id_document_type') === PhysicalPersonIdentity::DOCUMENT_PASSPORT) {
                $rules['passport_number'] = ['required', 'string', 'min:3', 'max:50'];
            }
        }

        return $rules;
    }

    private function refineIdentifierMessages(Validator $validator, ExistingSubjectIdentityEligibilityResult $result): void
    {
        $pib = $this->input('pib');
        if (is_string($pib) && preg_match('/^[0-9]{8}$/', $pib) && ! (new PibIdentifierValidator)->isValid($pib)) {
            $validator->errors()->forget('pib');
            $validator->errors()->add('pib', 'PIB nije ispravan.');
        }

        $jmbFields = $result->branch === ExistingSubjectIdentityEligibilityResult::BRANCH_DOO
            ? ['authorized_jmb']
            : ['jmb'];
        foreach ($jmbFields as $field) {
            $jmb = $this->input($field);
            if (is_string($jmb) && preg_match('/^[0-9]{13}$/', $jmb) && ! (new JmbIdentifierValidator)->isValid($jmb)) {
                $validator->errors()->forget($field);
                $validator->errors()->add($field, 'JMB nije ispravan.');
            }
        }

        $crps = $this->input('crps_number');
        if (! is_string($crps) || ! preg_match('/^[0-9]{8}$/', $crps)) {
            return;
        }

        $mark = $result->branch === ExistingSubjectIdentityEligibilityResult::BRANCH_DOO
            ? CrpsIdentifierValidator::MARK_DOO
            : CrpsIdentifierValidator::MARK_ENTREPRENEUR;
        $crpsValidator = new CrpsIdentifierValidator;
        if ($crpsValidator->isValid($crps, $mark)) {
            return;
        }

        $validator->errors()->forget('crps_number');
        if (! $crpsValidator->isValid($crps)) {
            $validator->errors()->add('crps_number', 'CRPS registracioni broj nije ispravan.');

            return;
        }

        $message = $result->branch === ExistingSubjectIdentityEligibilityResult::BRANCH_PREDUZETNIK
            ? 'CRPS registracioni broj ne odgovara preduzetniku.'
            : 'CRPS registracioni broj ne odgovara izabranom pravnom obliku.';
        $validator->errors()->add('crps_number', $message);
    }

    private function refinePhone(Validator $validator): void
    {
        $code = (string) $this->input('phone_calling_code');
        $national = (string) $this->input('phone_national');
        if ($code === '+382' && str_starts_with($national, '0')) {
            $validator->errors()->add('phone_national', 'Unesite broj mobilnog telefona bez pozivnog broja i bez početne nule.');
        }
    }
}
