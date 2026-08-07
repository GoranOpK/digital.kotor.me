<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalModeratorOccurrenceUpdateRequest extends FormRequest
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
            && $occurrence->event_entry_id === $entry->id
            && CulturalModeratorEventAccess::canEditDraft($user, $entry);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cjelodnevno' => $this->boolean('cjelodnevno'),
            'location_id' => $this->filled('location_id') ? (int) $this->input('location_id') : null,
            'location_manual_name' => $this->filled('location_manual_name')
                ? trim((string) $this->input('location_manual_name'))
                : null,
            'vrijeme_od' => $this->filled('vrijeme_od') ? trim((string) $this->input('vrijeme_od')) : null,
            'vrijeme_do' => $this->filled('vrijeme_do') ? trim((string) $this->input('vrijeme_do')) : null,
        ]);

        if ($this->input('location_manual_name') === '') {
            $this->merge(['location_manual_name' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'datum' => ['required', 'date'],
            'vrijeme_od' => ['nullable', 'string', 'max:16'],
            'vrijeme_do' => ['nullable', 'string', 'max:16'],
            'cjelodnevno' => ['boolean'],
            'location_id' => ['nullable', 'integer'],
            'location_manual_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                app(OccurrenceWriter::class)->normalizeAndValidate($this->domainPayload());
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('occurrence', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     datum: string,
     *     vrijeme_od: ?string,
     *     vrijeme_do: ?string,
     *     cjelodnevno: bool,
     *     location_id: ?int,
     *     location_manual_name: ?string
     * }
     */
    public function domainPayload(): array
    {
        return [
            'datum' => (string) $this->input('datum'),
            'vrijeme_od' => $this->input('vrijeme_od'),
            'vrijeme_do' => $this->input('vrijeme_do'),
            'cjelodnevno' => (bool) $this->input('cjelodnevno'),
            'location_id' => $this->input('location_id'),
            'location_manual_name' => $this->input('location_manual_name'),
        ];
    }
}
