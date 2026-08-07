<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Services\CulturalEventDomain\EventCatalogGuard;
use App\Support\CulturalModeratorEventAccess;
use App\Support\CulturalOrganizerContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * TS-010.1 — Moderator create Draft; organizer_id se forsira iz konteksta.
 */
class CulturalModeratorEventEntryStoreRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! CulturalModeratorEventAccess::isActiveModerator($user)) {
            return false;
        }

        $context = CulturalOrganizerContext::get($user);

        return $context !== null
            && CulturalModeratorEventAccess::canAccessOrganizer($user, $context);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'naslov' => $this->filled('naslov') ? trim((string) $this->input('naslov')) : null,
            'opis' => $this->filled('opis') ? trim((string) $this->input('opis')) : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
            'cover_media_id' => $this->filled('cover_media_id') ? (int) $this->input('cover_media_id') : null,
            'tag_ids' => array_values(array_filter(array_map('intval', (array) $this->input('tag_ids', [])))),
        ]);

        if ($this->input('naslov') === '') {
            $this->merge(['naslov' => null]);
        }
        if ($this->input('opis') === '') {
            $this->merge(['opis' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'naslov' => ['nullable', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:20000'],
            'category_id' => ['nullable', 'integer'],
            'cover_media_id' => ['nullable', 'integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var EventCatalogGuard $guard */
            $guard = app(EventCatalogGuard::class);

            try {
                $guard->assertCategoryAllowedForNewLink($this->input('category_id'));
                $guard->assertCoverMediaAllowedForNewLink($this->input('cover_media_id'));
                $guard->assertTagsAllowedForNewLinks($this->input('tag_ids', []));
            } catch (CulturalEventDomainException $e) {
                $validator->errors()->add('domain', $e->getMessage());
            }
        });
    }

    /**
     * @return array{
     *     naslov: ?string,
     *     opis: ?string,
     *     organizer_id: int,
     *     category_id: ?int,
     *     cover_media_id: ?int,
     *     tag_ids: list<int>,
     *     featured: bool
     * }
     */
    public function domainPayload(): array
    {
        $organizer = CulturalOrganizerContext::require($this->user());

        return [
            'naslov' => $this->input('naslov'),
            'opis' => $this->input('opis'),
            'organizer_id' => $organizer->id,
            'category_id' => $this->input('category_id'),
            'cover_media_id' => $this->input('cover_media_id'),
            'tag_ids' => $this->input('tag_ids', []),
            'featured' => false,
        ];
    }
}
