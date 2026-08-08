<?php

namespace App\Http\Requests;

use App\Models\CulturalEventEntry;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * TS-010.4 — Moderator Otkaži Objavljeni Događaj (HTTP sloj; domen = EventLifecycle::cancel).
 */
class CulturalModeratorEventEntryCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CulturalEventEntry|null $entry */
        $entry = $this->route('moderator_dogadjaj');

        return $user !== null
            && $entry instanceof CulturalEventEntry
            && CulturalModeratorEventAccess::canAccessEntry($user, $entry);
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
