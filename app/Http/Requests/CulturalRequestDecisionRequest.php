<?php

namespace App\Http\Requests;

use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;

class CulturalRequestDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CulturalPortalAccess::isKkEditor($this->user());
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('decision_note');
        $this->merge([
            'decision_note' => is_string($note) && trim($note) === '' ? null : (is_string($note) ? trim($note) : $note),
        ]);
    }

    public function rules(): array
    {
        return [
            'decision_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
