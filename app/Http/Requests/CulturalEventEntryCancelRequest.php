<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use Illuminate\Foundation\Http\FormRequest;

class CulturalEventEntryCancelRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cancellation_reason') && is_string($this->input('cancellation_reason'))) {
            $this->merge([
                'cancellation_reason' => trim($this->input('cancellation_reason')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Razlog otkazivanja je obavezan.',
            'cancellation_reason.min' => 'Razlog otkazivanja je obavezan.',
        ];
    }
}
