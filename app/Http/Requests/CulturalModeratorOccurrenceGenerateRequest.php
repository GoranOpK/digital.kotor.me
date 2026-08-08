<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventEntry;
use App\Services\CulturalEventDomain\OccurrenceGenerator;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CulturalModeratorOccurrenceGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CulturalEventEntry|null $entry */
        $entry = $this->route('moderator_dogadjaj');

        return $user !== null
            && $entry instanceof CulturalEventEntry
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
            'count' => $this->filled('count') ? (int) $this->input('count') : null,
            'end_date' => $this->filled('end_date') ? (string) $this->input('end_date') : null,
        ]);

        if ($this->input('location_manual_name') === '') {
            $this->merge(['location_manual_name' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'recurrence_type' => ['required', 'string', Rule::in(OccurrenceGenerator::TYPES)],
            'start_date' => ['required', 'date'],
            'count' => ['nullable', 'integer', 'min:1', 'max:'.OccurrenceGenerator::MAX_COUNT],
            'end_date' => ['nullable', 'date'],
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

            $hasCount = $this->input('count') !== null;
            $hasEnd = $this->filled('end_date');
            if ($hasCount === $hasEnd) {
                $validator->errors()->add(
                    'count',
                    'Zadajte tačno jedan završetak: broj Održavanja ili krajnji datum.'
                );

                return;
            }

            if ($hasEnd && $this->input('end_date') < $this->input('start_date')) {
                $validator->errors()->add('end_date', 'Krajnji datum ne može biti prije početnog datuma.');

                return;
            }

            try {
                app(OccurrenceWriter::class)->normalizeAndValidate([
                    'datum' => (string) $this->input('start_date'),
                    'vrijeme_od' => $this->input('vrijeme_od'),
                    'vrijeme_do' => $this->input('vrijeme_do'),
                    'cjelodnevno' => (bool) $this->input('cjelodnevno'),
                    'location_id' => $this->input('location_id'),
                    'location_manual_name' => $this->input('location_manual_name'),
                ]);
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('generator', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     recurrence_type: string,
     *     start_date: string,
     *     count: ?int,
     *     end_date: ?string,
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
            'recurrence_type' => (string) $this->input('recurrence_type'),
            'start_date' => (string) $this->input('start_date'),
            'count' => $this->input('count'),
            'end_date' => $this->input('end_date'),
            'vrijeme_od' => $this->input('vrijeme_od'),
            'vrijeme_do' => $this->input('vrijeme_do'),
            'cjelodnevno' => (bool) $this->input('cjelodnevno'),
            'location_id' => $this->input('location_id'),
            'location_manual_name' => $this->input('location_manual_name'),
        ];
    }
}
