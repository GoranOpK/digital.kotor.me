<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalEventChangeProposalOccurrence;
use App\Models\CulturalOccurrence;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * TS-010.3b — payload za predloženu operaciju Održavanja (add/update podataka).
 *
 * Lokacija: nova veza → samo aktivna; ista istorijska veza (update) → smije ostati deaktivirana.
 */
class CulturalEventChangeProposalOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
                app(OccurrenceWriter::class)->normalizeAndValidate(
                    $this->domainPayload(),
                    validateNewLocation: $this->shouldValidateNewLocation()
                );
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('occurrence', $e->getMessage());
            }
        });
    }

    /**
     * Nova katalog veza zahtijeva aktivnu Lokaciju; zadržavanje iste (update) ne.
     */
    public function shouldValidateNewLocation(): bool
    {
        $requestedId = $this->input('location_id');
        $requestedId = $requestedId !== null && $requestedId !== '' ? (int) $requestedId : null;
        $baselineId = $this->baselineCanonicalLocationId();

        return (int) ($requestedId ?? 0) !== (int) ($baselineId ?? 0);
    }

    /**
     * Kanonska location_id samo za update postojećeg Održavanja (istorijska veza).
     * Add (uključujući doradu add-op) nema kanonske veze → null.
     */
    private function baselineCanonicalLocationId(): ?int
    {
        $occurrence = $this->route('odrzavanje');
        if ($occurrence instanceof CulturalOccurrence) {
            return $occurrence->location_id !== null ? (int) $occurrence->location_id : null;
        }

        $op = $this->route('operacija');
        if ($op instanceof CulturalEventChangeProposalOccurrence && $op->isUpdate()) {
            $op->loadMissing('sourceOccurrence');
            $source = $op->sourceOccurrence;
            if ($source !== null && $source->location_id !== null) {
                return (int) $source->location_id;
            }

            return null;
        }

        return null;
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
