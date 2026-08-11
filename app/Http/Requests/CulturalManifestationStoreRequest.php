<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Services\CulturalManifestationDomain\ManifestationCatalogGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalManifestationStoreRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'naziv' => $this->filled('naziv') ? trim((string) $this->input('naziv')) : null,
            'opis' => $this->filled('opis') ? trim((string) $this->input('opis')) : null,
            'web_stranica' => $this->filled('web_stranica') ? trim((string) $this->input('web_stranica')) : null,
            'organizer_id' => $this->filled('organizer_id') ? (int) $this->input('organizer_id') : null,
            'cover_media_id' => $this->filled('cover_media_id') ? (int) $this->input('cover_media_id') : null,
            'event_entry_ids' => array_values(array_unique(array_filter(array_map(
                'intval',
                (array) $this->input('event_entry_ids', [])
            )))),
        ]);

        if ($this->input('naziv') === '') {
            $this->merge(['naziv' => null]);
        }
        if ($this->input('opis') === '') {
            $this->merge(['opis' => null]);
        }
        if ($this->input('web_stranica') === '') {
            $this->merge(['web_stranica' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:20000'],
            'organizer_id' => ['nullable', 'integer'],
            'cover_media_id' => ['nullable', 'integer'],
            'web_stranica' => ['nullable', 'string', 'max:2048', 'url'],
            'event_entry_ids' => ['nullable', 'array'],
            'event_entry_ids.*' => ['integer'],
            'status' => ['prohibited'],
            'published_at' => ['prohibited'],
            'cancelled_at' => ['prohibited'],
            'archived_at' => ['prohibited'],
            'first_submitted_at' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var ManifestationCatalogGuard $guard */
            $guard = app(ManifestationCatalogGuard::class);

            try {
                $guard->assertOrganizerAllowedForNewLink($this->input('organizer_id'));
                $guard->assertCoverMediaAllowedForNewLink($this->input('cover_media_id'));
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('domain', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     naziv: string,
     *     opis: ?string,
     *     organizer_id: ?int,
     *     cover_media_id: ?int,
     *     web_stranica: ?string,
     *     event_entry_ids: list<int>
     * }
     */
    public function domainPayload(): array
    {
        return [
            'naziv' => (string) $this->input('naziv'),
            'opis' => $this->input('opis'),
            'organizer_id' => $this->input('organizer_id'),
            'cover_media_id' => $this->input('cover_media_id'),
            'web_stranica' => $this->input('web_stranica'),
            'event_entry_ids' => $this->input('event_entry_ids', []),
        ];
    }
}
