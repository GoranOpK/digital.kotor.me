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
            $trimmed = trim($this->input('cancellation_reason'));
            $this->merge([
                'cancellation_reason' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function optionalReason(): ?string
    {
        $reason = $this->validated('cancellation_reason');

        return is_string($reason) ? $reason : null;
    }
}
