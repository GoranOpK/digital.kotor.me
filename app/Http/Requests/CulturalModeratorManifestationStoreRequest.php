<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HandlesEventCoverUpload;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Support\CulturalModeratorManifestationAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Foundation\Http\FormRequest;

class CulturalModeratorManifestationStoreRequest extends FormRequest
{
    use HandlesEventCoverUpload;
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! CulturalModeratorManifestationAccess::isActiveModerator($user)) {
            return false;
        }

        $context = CulturalOrganizerContext::get($user);

        return $context !== null
            && CulturalModeratorManifestationAccess::canAccessOrganizer($user, $context);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'naziv' => $this->filled('naziv') ? trim((string) $this->input('naziv')) : null,
            'opis' => $this->filled('opis') ? trim((string) $this->input('opis')) : null,
            'web_stranica' => $this->filled('web_stranica') ? trim((string) $this->input('web_stranica')) : null,
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
            'organizer_id' => ['prohibited'],
            'web_stranica' => ['nullable', 'string', 'max:2048', 'url'],
            'event_entry_ids' => ['nullable', 'array'],
            'event_entry_ids.*' => ['integer'],
            'status' => ['prohibited'],
        ] + $this->eventCoverUploadRules();
    }

    /**
     * @return array{
     *     naziv: string,
     *     opis: ?string,
     *     organizer_id: int,
     *     web_stranica: ?string,
     *     event_entry_ids: list<int>
     * }
     */
    public function domainPayload(): array
    {
        $organizer = CulturalOrganizerContext::require($this->user());

        return [
            'naziv' => (string) $this->input('naziv'),
            'opis' => $this->input('opis'),
            'organizer_id' => $organizer->id,
            'web_stranica' => $this->input('web_stranica'),
            'event_entry_ids' => $this->input('event_entry_ids', []),
        ];
    }
}
