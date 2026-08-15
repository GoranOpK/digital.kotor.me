<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\HandlesEventCoverUpload;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Services\CulturalManifestationDomain\ManifestationCatalogGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalManifestationUpdateRequest extends FormRequest
{
    use HandlesEventCoverUpload;
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->exists('naziv')) {
            $merge['naziv'] = $this->filled('naziv') ? trim((string) $this->input('naziv')) : null;
            if ($merge['naziv'] === '') {
                $merge['naziv'] = null;
            }
        }

        if ($this->exists('opis')) {
            $merge['opis'] = $this->filled('opis') ? trim((string) $this->input('opis')) : null;
            if ($merge['opis'] === '') {
                $merge['opis'] = null;
            }
        }

        if ($this->exists('web_stranica')) {
            $merge['web_stranica'] = $this->filled('web_stranica') ? trim((string) $this->input('web_stranica')) : null;
            if ($merge['web_stranica'] === '') {
                $merge['web_stranica'] = null;
            }
        }

        if ($this->exists('organizer_id')) {
            $merge['organizer_id'] = $this->filled('organizer_id') ? (int) $this->input('organizer_id') : null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'naziv' => ['sometimes', 'required', 'string', 'max:255'],
            'opis' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'organizer_id' => ['sometimes', 'nullable', 'integer'],
            'web_stranica' => ['sometimes', 'nullable', 'string', 'max:2048', 'url'],
            'status' => ['prohibited'],
            'published_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'archived_at' => ['prohibited'],
            'first_submitted_at' => ['prohibited'],
            'event_entry_ids' => ['prohibited'],
        ] + $this->eventCoverUploadRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var \App\Models\CulturalManifestation|null $manifestation */
            $manifestation = $this->route('kanonska_manifestacija');
            if (! $manifestation instanceof \App\Models\CulturalManifestation) {
                return;
            }

            /** @var ManifestationCatalogGuard $guard */
            $guard = app(ManifestationCatalogGuard::class);

            try {
                if ($this->exists('organizer_id')
                    && (int) ($this->input('organizer_id') ?? 0) !== (int) ($manifestation->organizer_id ?? 0)
                ) {
                    $guard->assertOrganizerAllowedForNewLink($this->input('organizer_id'));
                }
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('domain', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     naziv?: ?string,
     *     opis?: ?string,
     *     organizer_id?: ?int,
     *     web_stranica?: ?string
     * }
     */
    public function domainPayload(): array
    {
        $payload = [];

        foreach (['naziv', 'opis', 'organizer_id', 'web_stranica'] as $key) {
            if ($this->exists($key)) {
                $payload[$key] = $this->input($key);
            }
        }

        return $payload;
    }
}
