<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH-063 — Urednik: Odgodi Održavanje (opcion razlog).
 */
class CulturalOccurrencePostponeRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        if (! $this->authorizeKkAdmin()) {
            return false;
        }

        /** @var CulturalEventEntry|null $entry */
        $entry = $this->route('kanonski_dogadjaj');
        /** @var CulturalOccurrence|null $occurrence */
        $occurrence = $this->route('odrzavanje');

        return $entry instanceof CulturalEventEntry
            && $occurrence instanceof CulturalOccurrence
            && (int) $occurrence->event_entry_id === (int) $entry->id;
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
