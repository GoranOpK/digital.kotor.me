<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TS-010.3a — vraćanje prijedloga izmjene na doradu.
 * Autorizacija je u kontroleru.
 */
class CulturalEventChangeProposalReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('return_reason') && is_string($this->input('return_reason'))) {
            $this->merge([
                'return_reason' => trim($this->input('return_reason')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'return_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_reason.required' => 'Razlog vraćanja na doradu je obavezan.',
        ];
    }
}
