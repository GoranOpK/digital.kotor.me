<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CulturalManifestationEventLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('event_entry_id')) {
            $this->merge(['event_entry_id' => (int) $this->input('event_entry_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'event_entry_id' => ['required', 'integer'],
        ];
    }
}
