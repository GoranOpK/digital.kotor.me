<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Support\UserType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesCanonicalAvailabilityRule
{
    /**
     * @return array<string, mixed>
     */
    protected function availabilityFieldRules(): array
    {
        return [
            'user_type' => ['required', 'string', Rule::in(UserType::canonicalStorageValues())],
            'residential_status' => ['nullable', 'string', Rule::in(['resident', 'non-resident'])],
        ];
    }

    protected function configureAvailabilityValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $userType = (string) $this->input('user_type');
            $residential = $this->input('residential_status');

            if (UserType::requiresResidentialStatus($userType)) {
                if (! in_array($residential, ['resident', 'non-resident'], true)) {
                    $validator->errors()->add(
                        'residential_status',
                        'Status prebivališta je obavezan za fizičko lice i preduzetnika.'
                    );
                }

                return;
            }

            if ($residential !== null && $residential !== '') {
                $validator->errors()->add(
                    'residential_status',
                    'Pravno lice ne smije imati status prebivališta.'
                );
            }
        });
    }

    protected function prepareAvailabilityForValidation(): void
    {
        if ($this->input('residential_status') === '') {
            $this->merge(['residential_status' => null]);
        }
    }
}
