<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use Illuminate\Foundation\Http\FormRequest;

class CulturalEventEntryReturnRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
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
            'return_reason' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_reason.required' => 'Razlog vraćanja na doradu je obavezan.',
            'return_reason.min' => 'Razlog vraćanja na doradu je obavezan.',
        ];
    }
}
