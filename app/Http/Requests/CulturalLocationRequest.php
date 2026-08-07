<?php

namespace App\Http\Requests;

use App\Models\CulturalLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CulturalLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->role
            && $user->role->name === 'kk_admin';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('naziv') && is_string($this->input('naziv'))) {
            $this->merge([
                'naziv' => trim($this->input('naziv')),
            ]);
        }

        if ($this->has('opis') && is_string($this->input('opis'))) {
            $opis = trim($this->input('opis'));
            $this->merge([
                'opis' => $opis === '' ? null : $opis,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(CulturalLocation::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv lokacije je obavezan.',
            'status.required' => 'Status lokacije je obavezan.',
            'status.in' => 'Status lokacije nije validan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $status = (string) $this->input('status');
            if ($status !== CulturalLocation::STATUS_ACTIVE) {
                return;
            }

            $naziv = (string) $this->input('naziv');
            $exceptId = $this->route('lokacije')?->getKey();

            if (CulturalLocation::activeDuplicateExists($naziv, $exceptId ? (int) $exceptId : null)) {
                $validator->errors()->add(
                    'naziv',
                    'Već postoji aktivna lokacija sa istim nazivom.'
                );
            }
        });
    }
}
