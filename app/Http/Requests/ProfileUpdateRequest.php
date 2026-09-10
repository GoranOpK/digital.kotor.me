<?php

namespace App\Http\Requests;

use App\Identity\Runtime\CanonicalIdentifierUniqueness;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Validation\CrpsIdentifierValidator;
use App\Identity\Validation\PibIdentifierValidator;
use App\Models\User;
use App\Rules\ValidCrps;
use App\Rules\ValidPib;
use App\Support\KotorAddress;
use App\Support\Pib;
use App\Support\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        [$street, $city] = KotorAddress::normalizeStreetAndCityInputs(
            $this->input('address'),
            $this->input('city')
        );

        $merge = [
            'address' => $street,
            'city' => $city,
        ];

        $currentType = $this->currentSubjectType($this->user());
        $postedType = $this->input('user_type');

        if (
            UserType::isNaturalPerson($currentType)
            && ! UserType::isLegalEntity($postedType)
            && $this->has('registers_as_entrepreneur')
        ) {
            $merge['user_type'] = $this->affirmativeEntrepreneurChoice($this->input('registers_as_entrepreneur'))
                ? UserType::ENTREPRENEUR
                : UserType::PHYSICAL_PERSON;
        }

        $this->merge($merge);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user();
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
        ];

        if (! $this->collectsCurrentBusinessIdentity($user)) {
            return $rules;
        }

        $currentType = $this->currentSubjectType($user);
        $allowedTypes = UserType::allowedProfileWriteValues($currentType);

        $rules['user_type'] = ['required', 'string', Rule::in($allowedTypes)];

        $incomingType = $this->input('user_type', $currentType);

        if (UserType::requiresResidentialStatus($incomingType) || UserType::isNaturalPerson($currentType)) {
            $rules['residential_status'] = ['required', 'string', 'in:resident,non-resident'];
        }

        if (UserType::isNaturalPerson($currentType) || UserType::isNaturalPerson($incomingType)) {
            $rules['registers_as_entrepreneur'] = ['nullable', 'in:0,1'];
            $rules['jmb'] = [
                'nullable',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
            ];
            if ($this->input('residential_status', $this->currentResidentialStatus($user)) === 'resident') {
                $rules['jmb'] = [
                    'required',
                    'string',
                    'size:13',
                    'regex:/^[0-9]{13}$/',
                ];
            }

            if ($this->resultingIsEntrepreneur()) {
                $rules['entrepreneur_business_name'] = ['required', 'string', 'max:255'];
                $rules['pib'] = ['required', 'string', 'regex:/^[0-9]{8}$/', new ValidPib];
                $rules['crps_number'] = [
                    'required',
                    'string',
                    'regex:/^[0-9]{8}$/',
                    new ValidCrps(CrpsIdentifierValidator::MARK_ENTREPRENEUR),
                ];
            } else {
                $rules['entrepreneur_business_name'] = ['nullable', 'string', 'max:255'];
                $rules['pib'] = ['nullable', 'string'];
                $rules['crps_number'] = ['nullable', 'string'];
                $rules['company_name'] = ['nullable', 'string', 'max:255'];
            }
        } else {
            $rules['pib'] = [
                'required',
                'string',
                'size:'.Pib::LENGTH,
                'regex:'.Pib::REGEX,
                Rule::unique(User::class)->ignore($user->id),
            ];
            $rules['jmb'] = ['nullable'];
            $rules['company_name'] = ['required', 'string', 'max:255'];
        }

        if ($this->input('residential_status') !== 'resident' && UserType::requiresResidentialStatus($incomingType)) {
            $rules['passport_number'] = [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($user->id),
            ];
        } else {
            $rules['passport_number'] = ['nullable'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->requiresKotorAddress()) {
                return;
            }

            if (!KotorAddress::isValidStreetLine($this->input('address'))) {
                $validator->errors()->add('address', KotorAddress::streetLineValidationMessage());

                return;
            }

            if (KotorAddress::isOnlyLocality($this->input('address'))) {
                $validator->errors()->add('address', KotorAddress::streetValidationMessage());

                return;
            }

            $fullAddress = KotorAddress::formatStreetAndCity(
                $this->input('address'),
                $this->input('city')
            );
            if (!KotorAddress::isInKotorMunicipality($fullAddress)) {
                $validator->errors()->add('city', KotorAddress::cityValidationMessage());
            }
        });

        $validator->after(function (Validator $validator): void {
            if (! $this->resultingIsEntrepreneur()) {
                return;
            }

            $this->refineEntrepreneurIdentifiers($validator);
            $this->refineEntrepreneurUniqueness($validator);
        });

        $validator->after(function (Validator $validator): void {
            $this->refineJmbUniqueness($validator);
        });
    }

    private function requiresKotorAddress(): bool
    {
        if ($this->input('residential_status') === 'resident') {
            return true;
        }

        $user = $this->user();
        $fallbackType = config('identity.canonical_read')
            ? $this->currentSubjectType($user)
            : $user?->user_type;

        return UserType::isLegalEntity($this->input('user_type', $fallbackType));
    }

    private function collectsCurrentBusinessIdentity(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (! config('identity.canonical_read')) {
            return $user->collectsBusinessIdentity();
        }

        $view = app(CurrentIdentityResolver::class)->viewFor($user);
        if (! $view->hasCurrentSubjectIdentity()) {
            return false;
        }

        return UserType::isNaturalPerson($view->userType) || UserType::isLegalEntity($view->userType);
    }

    private function currentSubjectType(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (! config('identity.canonical_read')) {
            return $user->user_type;
        }

        return app(CurrentIdentityResolver::class)->viewFor($user)->userType;
    }

    private function currentResidentialStatus(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (! config('identity.canonical_read')) {
            return $user->residential_status;
        }

        return app(CurrentIdentityResolver::class)->viewFor($user)->residentialStatus;
    }

    private function resultingIsEntrepreneur(): bool
    {
        if ($this->has('registers_as_entrepreneur')) {
            return $this->affirmativeEntrepreneurChoice($this->input('registers_as_entrepreneur'));
        }

        $user = $this->user();
        $incomingType = $this->input('user_type', $this->currentSubjectType($user));

        return UserType::isEntrepreneur($incomingType);
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

    private function refineEntrepreneurIdentifiers(Validator $validator): void
    {
        $pib = $this->input('pib');
        if (is_string($pib) && preg_match('/^[0-9]{8}$/', $pib) && ! (new PibIdentifierValidator)->isValid($pib)) {
            $validator->errors()->forget('pib');
            $validator->errors()->add('pib', 'PIB nije ispravan.');
        }

        $crps = $this->input('crps_number');
        if (! is_string($crps) || ! preg_match('/^[0-9]{8}$/', $crps)) {
            return;
        }

        $validatorInstance = new CrpsIdentifierValidator;
        if ($validatorInstance->isValid($crps, CrpsIdentifierValidator::MARK_ENTREPRENEUR)) {
            return;
        }

        $validator->errors()->forget('crps_number');
        if (! $validatorInstance->isValid($crps)) {
            $validator->errors()->add('crps_number', 'CRPS registracioni broj nije ispravan.');

            return;
        }

        $validator->errors()->add('crps_number', 'CRPS registracioni broj ne odgovara preduzetniku.');
    }

    private function refineEntrepreneurUniqueness(Validator $validator): void
    {
        $pib = $this->input('pib');
        if (! is_string($pib) || $pib === '') {
            return;
        }

        $userId = $this->user()?->id;
        if (app(CanonicalIdentifierUniqueness::class)->pibTaken($pib, $userId)) {
            $validator->errors()->add('pib', 'PIB je već registrovan.');
        }
    }

    private function refineJmbUniqueness(Validator $validator): void
    {
        $user = $this->user();
        if (! $this->collectsCurrentBusinessIdentity($user)) {
            return;
        }

        $currentType = $this->currentSubjectType($user);
        $incomingType = $this->input('user_type', $currentType);
        if (! UserType::isNaturalPerson($currentType) && ! UserType::isNaturalPerson($incomingType)) {
            return;
        }

        $jmb = $this->input('jmb');
        if (! is_string($jmb) || ! preg_match('/^[0-9]{13}$/', $jmb)) {
            return;
        }

        if ($validator->errors()->has('jmb')) {
            return;
        }

        app(CanonicalIdentifierUniqueness::class)->addJmbTakenValidationError(
            $validator,
            'jmb',
            $jmb,
            $user?->id,
        );
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $messages = [
            'first_name.required' => 'Ime je obavezno.',
            'last_name.required' => 'Prezime je obavezno.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Unijete validnu email adresu.',
            'email.unique' => 'Email adresa je već u upotrebi.',
            'phone.required' => 'Broj telefona je obavezan.',
            'address.required' => 'Ulica i broj (ili bb) je obavezna.',
            'address.max' => 'Adresa ne može biti duža od 500 karaktera.',
            'city.required' => 'Grad je obavezan.',
            'city.max' => 'Naziv grada ne može biti duži od 255 karaktera.',
            'user_type.required' => 'Tip korisnika je obavezan.',
            'user_type.in' => 'Izaberite validan tip korisnika.',
            'residential_status.required' => 'Status rezidentnosti je obavezan.',
            'residential_status.in' => 'Izaberite validan status rezidentnosti.',
            'jmb.required' => 'JMB je obavezan za fizička lica.',
            'jmb.size' => 'JMB mora imati tačno 13 cifara.',
            'jmb.regex' => 'JMB mora sadržati samo cifre.',
            'jmb.unique' => 'JMB je već u upotrebi.',
            'pib.required' => 'PIB je obavezan za pravna lica.',
            'pib.size' => Pib::VALIDATION_MESSAGE,
            'pib.regex' => 'PIB mora imati tačno 8 cifara.',
            'pib.unique' => 'PIB je već u upotrebi.',
            'passport_number.unique' => 'Broj pasoša je već u upotrebi.',
            'entrepreneur_business_name.required' => 'Unesite naziv preduzetnika.',
            'crps_number.required' => 'Unesite CRPS registracioni broj.',
            'crps_number.regex' => 'CRPS registracioni broj mora imati tačno 8 cifara.',
        ];

        if ($this->resultingIsEntrepreneur()) {
            $messages['pib.required'] = 'Unesite PIB.';
        }

        return $messages;
    }
}
