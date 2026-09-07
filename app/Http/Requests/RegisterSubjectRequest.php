<?php

namespace App\Http\Requests;

use App\Identity\CountryCatalog;
use App\Identity\PhoneCallingCodeCatalog;
use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Validation\CrpsIdentifierValidator;
use App\Identity\Validation\JmbIdentifierValidator;
use App\Identity\Validation\PibIdentifierValidator;
use App\Models\PhysicalPersonIdentity;
use App\Rules\ValidCrps;
use App\Rules\ValidJmb;
use App\Rules\ValidPib;
use App\Support\UserType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $callingCodes = array_values(array_unique(array_map(
            fn (array $entry): string => $entry['calling_code'],
            PhoneCallingCodeCatalog::pickerEntries(),
        )));
        $countryCodes = CountryCatalog::codes();

        $rules = [
            'user_type' => ['required', 'in:'.implode(',', [
                UserType::PHYSICAL_PERSON,
                UserType::REGISTRATION_GROUP_LEGAL_ENTITY,
                UserType::REGISTRATION_GROUP_BUSINESS,
                UserType::FOREIGN_BRANCH,
            ])],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'email_confirmation' => ['required', 'same:email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone_calling_code' => ['required', 'string', Rule::in($callingCodes)],
            'phone_national' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
        ];

        $userType = $this->input('user_type');

        if ($userType === UserType::PHYSICAL_PERSON || $this->isEntrepreneur()) {
            $rules = array_merge($rules, $this->naturalPersonRules($countryCodes));
        }

        if ($userType === UserType::REGISTRATION_GROUP_LEGAL_ENTITY) {
            $rules['business_type'] = ['required', 'in:'.implode(',', UserType::canonicalLegalEntityStorageValues())];
        }

        if ($userType === UserType::REGISTRATION_GROUP_BUSINESS) {
            $rules['business_type'] = ['required', 'in:'.implode(',', UserType::registrationBusinessStorageValues())];
        }

        if ($this->isEntrepreneur()) {
            $rules['entrepreneur_business_name'] = ['required', 'string', 'max:255'];
            $rules['pib'] = ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib];
            $rules['crps_number'] = ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidCrps(CrpsIdentifierValidator::MARK_ENTREPRENEUR)];
        }

        if ($this->isLegalEntityBranch()) {
            $rules = array_merge($rules, $this->legalEntityRules($countryCodes));
        }

        if ($userType === UserType::FOREIGN_BRANCH) {
            $rules = array_merge($rules, $this->dspdRules($countryCodes));
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->refinePhone($validator);
            $this->refineIdentifiers($validator);
            $this->refineUniqueness($validator);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_type.required' => 'Izaberite vrstu subjekta.',
            'user_type.in' => 'Izaberite vrstu subjekta.',
            'business_type.required' => 'Izaberite pravni oblik.',
            'business_type.in' => 'Izaberite pravni oblik.',
            'first_name.required' => 'Unesite ime.',
            'last_name.required' => 'Unesite prezime.',
            'email.required' => 'Unesite e-mail adresu.',
            'email.email' => 'E-mail adresa nije ispravna.',
            'email.unique' => 'E-mail adresa je već u upotrebi.',
            'email_confirmation.required' => 'Potvrdite e-mail adresu.',
            'email_confirmation.same' => 'E-mail adrese se ne podudaraju.',
            'password.required' => 'Unesite korisničku lozinku.',
            'password.confirmed' => 'Korisničke lozinke se ne podudaraju.',
            'phone_calling_code.required' => 'Izaberite pozivni broj.',
            'phone_calling_code.in' => 'Izaberite pozivni broj.',
            'phone_national.required' => 'Unesite broj mobilnog telefona.',
            'phone_national.regex' => 'Unesite broj mobilnog telefona.',
            'address.required' => 'Unesite ulicu i broj.',
            'city.required' => 'Unesite grad.',
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
            'legal_name.required' => 'Unesite puni naziv pravnog lica.',
            'authorized_first_name.required' => 'Unesite ime ovlašćenog lica.',
            'authorized_last_name.required' => 'Unesite prezime ovlašćenog lica.',
            'authorized_id_document_type.required' => 'Izaberite vrstu identifikacionog dokumenta ovlašćenog lica.',
            'authorized_id_document_type.in' => 'Izaberite vrstu identifikacionog dokumenta ovlašćenog lica.',
            'authorized_jmb.required' => 'Unesite JMB ovlašćenog lica.',
            'authorized_jmb.regex' => 'JMB mora imati tačno 13 cifara.',
            'authorized_passport_number.required' => 'Unesite broj pasoša ovlašćenog lica.',
            'authorized_passport_issuing_country_code.required' => 'Izaberite državu izdavanja pasoša.',
            'authorized_passport_issuing_country_code.in' => 'Izaberite državu izdavanja pasoša iz liste.',
            'foreign_company_name.required' => 'Unesite naziv stranog privrednog društva.',
            'branch_name_in_montenegro.required' => 'Unesite naziv dijela stranog privrednog društva u Crnoj Gori.',
            'representative_first_name.required' => 'Unesite ime zastupnika.',
            'representative_last_name.required' => 'Unesite prezime zastupnika.',
            'representative_id_document_type.required' => 'Izaberite vrstu identifikacionog dokumenta zastupnika.',
            'representative_id_document_type.in' => 'Izaberite vrstu identifikacionog dokumenta zastupnika.',
            'representative_jmb.required' => 'Unesite JMB zastupnika.',
            'representative_jmb.regex' => 'JMB mora imati tačno 13 cifara.',
            'representative_passport_number.required' => 'Unesite broj pasoša zastupnika.',
            'representative_passport_issuing_country_code.required' => 'Izaberite državu izdavanja pasoša.',
            'representative_passport_issuing_country_code.in' => 'Izaberite državu izdavanja pasoša iz liste.',
        ];
    }

    public function storedUserType(): string
    {
        if ($this->input('user_type') === UserType::FOREIGN_BRANCH) {
            return UserType::FOREIGN_BRANCH;
        }

        if ($this->isEntrepreneur()) {
            return UserType::ENTREPRENEUR;
        }

        if ($this->input('user_type') === UserType::PHYSICAL_PERSON) {
            return UserType::PHYSICAL_PERSON;
        }

        return (string) $this->input('business_type');
    }

    public function resolvedJmb(): ?string
    {
        if ($this->isLegalEntityBranch()) {
            return $this->filled('authorized_jmb') ? (string) $this->input('authorized_jmb') : null;
        }

        if ($this->input('user_type') === UserType::FOREIGN_BRANCH) {
            return $this->filled('representative_jmb') ? (string) $this->input('representative_jmb') : null;
        }

        if ($this->filled('jmb')) {
            return (string) $this->input('jmb');
        }

        return null;
    }

    public function composedPhone(): string
    {
        return PhoneCallingCodeCatalog::compose(
            (string) $this->input('phone_calling_code'),
            (string) $this->input('phone_national'),
        );
    }

    public function accountDisplayName(): string
    {
        $stored = $this->storedUserType();

        if (UserType::isLegalEntity($stored)) {
            return trim((string) $this->input('legal_name'));
        }

        if (UserType::isForeignBranch($stored)) {
            return trim((string) $this->input('branch_name_in_montenegro'));
        }

        if (UserType::isEntrepreneur($stored)) {
            return trim((string) $this->input('first_name')).' '.trim((string) $this->input('last_name'));
        }

        return trim((string) $this->input('first_name')).' '.trim((string) $this->input('last_name'));
    }

    /**
     * @param  list<string>  $countryCodes
     * @return array<string, mixed>
     */
    private function naturalPersonRules(array $countryCodes): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'residential_status' => ['required', 'in:resident,non-resident'],
        ];

        if ($this->input('residential_status') === 'resident') {
            $rules['jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
        } elseif ($this->input('residential_status') === 'non-resident') {
            $rules['id_document_type'] = ['required', 'in:'.PhysicalPersonIdentity::DOCUMENT_JMB.','.PhysicalPersonIdentity::DOCUMENT_PASSPORT];
            $rules['residence_country_code'] = ['required', 'string', Rule::in($countryCodes)];

            if ($this->input('id_document_type') === PhysicalPersonIdentity::DOCUMENT_JMB) {
                $rules['jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
            } elseif ($this->input('id_document_type') === PhysicalPersonIdentity::DOCUMENT_PASSPORT) {
                $rules['passport_number'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9]+$/'];
            }
        }

        return $rules;
    }

    /**
     * @param  list<string>  $countryCodes
     * @return array<string, mixed>
     */
    private function legalEntityRules(array $countryCodes): array
    {
        $stored = $this->storedUserType();
        $documentType = $this->input('authorized_id_document_type');

        $rules = [
            'legal_name' => ['required', 'string', 'max:255'],
            'pib' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib],
            'authorized_first_name' => ['required', 'string', 'max:255'],
            'authorized_last_name' => ['required', 'string', 'max:255'],
            'authorized_id_document_type' => ['required', 'in:'.PhysicalPersonIdentity::DOCUMENT_JMB.','.PhysicalPersonIdentity::DOCUMENT_PASSPORT],
        ];

        if (UserType::requiresCrps($stored)) {
            $mark = $this->crpsMarkFor($stored);
            $rules['crps_number'] = ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidCrps($mark)];
        }

        if ($documentType === PhysicalPersonIdentity::DOCUMENT_JMB) {
            $rules['authorized_jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
        } elseif ($documentType === PhysicalPersonIdentity::DOCUMENT_PASSPORT) {
            $rules['authorized_passport_number'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9]+$/'];
            $rules['authorized_passport_issuing_country_code'] = ['required', 'string', Rule::in($countryCodes)];
        }

        return $rules;
    }

    /**
     * @param  list<string>  $countryCodes
     * @return array<string, mixed>
     */
    private function dspdRules(array $countryCodes): array
    {
        $documentType = $this->input('representative_id_document_type');

        $rules = [
            'foreign_company_name' => ['required', 'string', 'max:255'],
            'branch_name_in_montenegro' => ['required', 'string', 'max:255'],
            'pib' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib],
            'crps_number' => ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidCrps(CrpsIdentifierValidator::MARK_FOREIGN_BRANCH)],
            'representative_first_name' => ['required', 'string', 'max:255'],
            'representative_last_name' => ['required', 'string', 'max:255'],
            'representative_id_document_type' => ['required', 'in:'.PhysicalPersonIdentity::DOCUMENT_JMB.','.PhysicalPersonIdentity::DOCUMENT_PASSPORT],
        ];

        if ($documentType === PhysicalPersonIdentity::DOCUMENT_JMB) {
            $rules['representative_jmb'] = ['required', 'string', 'regex:/^[0-9]{13}$/', new ValidJmb];
        } elseif ($documentType === PhysicalPersonIdentity::DOCUMENT_PASSPORT) {
            $rules['representative_passport_number'] = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9]+$/'];
            $rules['representative_passport_issuing_country_code'] = ['required', 'string', Rule::in($countryCodes)];
        }

        return $rules;
    }

    private function refinePhone(Validator $validator): void
    {
        $code = (string) $this->input('phone_calling_code');
        $national = (string) $this->input('phone_national');

        if ($code === '+382' && (str_starts_with($national, '0') || str_starts_with($national, '382'))) {
            $validator->errors()->add(
                'phone_national',
                'Unesite broj mobilnog telefona bez pozivnog broja i bez početne nule.'
            );
        }
    }

    private function refineIdentifiers(Validator $validator): void
    {
        $pib = $this->input('pib');
        if (is_string($pib) && preg_match('/^[0-9]{8}$/', $pib) && ! (new PibIdentifierValidator)->isValid($pib)) {
            $validator->errors()->forget('pib');
            $validator->errors()->add('pib', 'PIB nije ispravan.');
        }

        foreach (['jmb', 'authorized_jmb', 'representative_jmb'] as $field) {
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

        $mark = $this->crpsMarkFor($this->storedUserType());
        if ($mark === null) {
            return;
        }

        $validatorInstance = new CrpsIdentifierValidator;
        if ($validatorInstance->isValid($crps, $mark)) {
            return;
        }

        $validator->errors()->forget('crps_number');
        if (! $validatorInstance->isValid($crps)) {
            $validator->errors()->add('crps_number', 'CRPS registracioni broj nije ispravan.');

            return;
        }

        $validator->errors()->add('crps_number', $this->crpsMarkMessage($this->storedUserType()));
    }

    private function refineUniqueness(Validator $validator): void
    {
        $uniqueness = app(CanonicalIdentifierUniqueness::class);

        $jmb = $this->resolvedJmb();
        if (is_string($jmb) && $this->isNaturalPersonBranch() && $uniqueness->jmbTaken($jmb)) {
            $validator->errors()->add('jmb', 'JMB je već registrovan.');
        }

        $pib = $this->input('pib');
        if (is_string($pib) && $pib !== '' && $uniqueness->pibTaken($pib)) {
            $validator->errors()->add('pib', 'PIB je već registrovan.');
        }

        $passport = $this->input('passport_number');
        if (is_string($passport) && $passport !== '' && $this->isNaturalPersonBranch() && $uniqueness->physicalPassportTaken($passport)) {
            $validator->errors()->add('passport_number', 'Broj pasoša je već registrovan.');
        }
    }

    private function isEntrepreneur(): bool
    {
        if ($this->input('user_type') === UserType::PHYSICAL_PERSON) {
            return $this->affirmativeEntrepreneurChoice($this->input('registers_as_entrepreneur'));
        }

        return $this->input('user_type') === UserType::REGISTRATION_GROUP_BUSINESS
            && $this->input('business_type') === UserType::ENTREPRENEUR;
    }

    private function isLegalEntityBranch(): bool
    {
        $group = $this->input('user_type');

        if (! in_array($group, [
            UserType::REGISTRATION_GROUP_LEGAL_ENTITY,
            UserType::REGISTRATION_GROUP_BUSINESS,
        ], true)) {
            return false;
        }

        $businessType = $this->input('business_type');

        return is_string($businessType)
            && UserType::isLegalEntity($businessType)
            && $businessType !== UserType::ENTREPRENEUR;
    }

    private function affirmativeEntrepreneurChoice(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'da', 'yes', 'true'], true);
    }

    private function isNaturalPersonBranch(): bool
    {
        return $this->input('user_type') === UserType::PHYSICAL_PERSON || $this->isEntrepreneur();
    }

    private function crpsMarkFor(string $type): ?int
    {
        return match ($type) {
            UserType::ENTREPRENEUR => CrpsIdentifierValidator::MARK_ENTREPRENEUR,
            UserType::GENERAL_PARTNERSHIP => CrpsIdentifierValidator::MARK_OD,
            UserType::LIMITED_PARTNERSHIP => CrpsIdentifierValidator::MARK_KD,
            UserType::JOINT_STOCK_COMPANY => CrpsIdentifierValidator::MARK_AD,
            UserType::LIMITED_LIABILITY_COMPANY => CrpsIdentifierValidator::MARK_DOO,
            UserType::FOREIGN_BRANCH => CrpsIdentifierValidator::MARK_FOREIGN_BRANCH,
            default => null,
        };
    }

    private function crpsMarkMessage(string $type): string
    {
        return match ($type) {
            UserType::ENTREPRENEUR => 'CRPS registracioni broj ne odgovara preduzetniku.',
            UserType::FOREIGN_BRANCH => 'CRPS registracioni broj ne odgovara dijelu stranog privrednog društva.',
            default => 'CRPS registracioni broj ne odgovara izabranom pravnom obliku.',
        };
    }
}
