<?php

namespace App\Http\Requests;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH-063 — Moderator: Odgodi Održavanje (opcion razlog).
 */
class CulturalModeratorOccurrencePostponeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CulturalEventEntry|null $entry */
        $entry = $this->route('moderator_dogadjaj');
        /** @var CulturalOccurrence|null $occurrence */
        $occurrence = $this->route('odrzavanje');

        return $user !== null
            && $entry instanceof CulturalEventEntry
            && $occurrence instanceof CulturalOccurrence
            && CulturalModeratorEventAccess::canMutatePublishedOccurrenceStatus($user, $entry, $occurrence);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('postponement_reason') && is_string($this->input('postponement_reason'))) {
            $trimmed = trim($this->input('postponement_reason'));
            $this->merge([
                'postponement_reason' => $trimmed === '' ? null : $trimmed,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'postponement_reason' => ['nullable', 'string', 'max:5000'],
            'status' => ['prohibited'],
            'cancellation_reason' => ['prohibited'],
            'organizer_id' => ['prohibited'],
            'organizer_manual_name' => ['prohibited'],
            'datum' => ['prohibited'],
            'vrijeme_od' => ['prohibited'],
            'vrijeme_do' => ['prohibited'],
            'cjelodnevno' => ['prohibited'],
        ];
    }

    public function optionalReason(): ?string
    {
        $reason = $this->validated('postponement_reason');

        return is_string($reason) ? $reason : null;
    }
}
