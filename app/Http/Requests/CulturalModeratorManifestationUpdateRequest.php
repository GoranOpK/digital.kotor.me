<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalManifestation;
use App\Services\CulturalManifestationDomain\ManifestationCatalogGuard;
use App\Support\CulturalModeratorManifestationAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalModeratorManifestationUpdateRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        /** @var CulturalManifestation $manifestation */
        $manifestation = $this->route('moderator_manifestacija');

        return $manifestation instanceof CulturalManifestation
            && CulturalModeratorManifestationAccess::canEditContent($user, $manifestation);
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

        if ($this->exists('cover_media_id')) {
            $merge['cover_media_id'] = $this->filled('cover_media_id') ? (int) $this->input('cover_media_id') : null;
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
            'cover_media_id' => ['sometimes', 'nullable', 'integer'],
            'web_stranica' => ['sometimes', 'nullable', 'string', 'max:2048', 'url'],
            'organizer_id' => ['prohibited'],
            'status' => ['prohibited'],
            'event_entry_ids' => ['prohibited'],
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

            if (! $this->exists('cover_media_id')) {
                return;
            }

            /** @var ManifestationCatalogGuard $guard */
            $guard = app(ManifestationCatalogGuard::class);

            try {
                $guard->assertCoverMediaAllowedForNewLink($this->input('cover_media_id'));
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('domain', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     naziv?: ?string,
     *     opis?: ?string,
     *     cover_media_id?: ?int,
     *     web_stranica?: ?string
     * }
     */
    public function domainPayload(): array
    {
        $payload = [];

        foreach (['naziv', 'opis', 'cover_media_id', 'web_stranica'] as $key) {
            if ($this->exists($key)) {
                $payload[$key] = $this->input($key);
            }
        }

        return $payload;
    }
}
