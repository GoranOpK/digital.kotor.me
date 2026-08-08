<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Moderator: Odgođen → Planiran — samo termin (bez Lokacije).
 */
class CulturalModeratorOccurrenceResumeRequest extends FormRequest
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
        $this->merge([
            'cjelodnevno' => $this->boolean('cjelodnevno'),
            'vrijeme_od' => $this->filled('vrijeme_od') ? trim((string) $this->input('vrijeme_od')) : null,
            'vrijeme_do' => $this->filled('vrijeme_do') ? trim((string) $this->input('vrijeme_do')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'datum' => ['required', 'date'],
            'vrijeme_od' => ['nullable', 'string', 'max:16'],
            'vrijeme_do' => ['nullable', 'string', 'max:16'],
            'cjelodnevno' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->has('location_id') || $this->has('location_manual_name')) {
                $validator->errors()->add('occurrence', 'Lokacija se ne mijenja kroz statusni tok Održavanja.');

                return;
            }

            try {
                /** @var CulturalOccurrence $occurrence */
                $occurrence = $this->route('odrzavanje');
                app(OccurrenceWriter::class)->normalizeAndValidate([
                    ...$this->terminPayload(),
                    'location_id' => $occurrence->location_id,
                    'location_manual_name' => $occurrence->location_manual_name,
                ], validateNewLocation: false);
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
     *     cjelodnevno: bool
     * }
     */
    public function terminPayload(): array
    {
        return [
            'datum' => (string) $this->input('datum'),
            'vrijeme_od' => $this->input('vrijeme_od'),
            'vrijeme_do' => $this->input('vrijeme_do'),
            'cjelodnevno' => (bool) $this->input('cjelodnevno'),
        ];
    }
}
