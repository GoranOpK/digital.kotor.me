<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PATCH-063 — Urednik: Otkaži Održavanje (opcion razlog).
 */
class CulturalOccurrenceCancelRequest extends FormRequest
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
