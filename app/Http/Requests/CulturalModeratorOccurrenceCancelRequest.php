<?php

namespace App\Http\Requests;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH-063 — Moderator: Otkaži Održavanje (opcion razlog).
 */
class CulturalModeratorOccurrenceCancelRequest extends FormRequest
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
            'status' => ['prohibited'],
            'postponement_reason' => ['prohibited'],
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
        $reason = $this->validated('cancellation_reason');

        return is_string($reason) ? $reason : null;
    }
}
