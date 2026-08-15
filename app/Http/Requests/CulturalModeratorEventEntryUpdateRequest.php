<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\HandlesEventCoverUpload;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalEventEntry;
use App\Services\CulturalEventDomain\EventCatalogGuard;
use App\Support\CulturalModeratorEventAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * TS-010.1 — Moderator update Draft; organizer_id se ne mijenja iz requesta.
 */
class CulturalModeratorEventEntryUpdateRequest extends FormRequest
{
    use HandlesEventCoverUpload;
    use ValidatesCulturalCatalogItem;

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
            'naslov' => $this->filled('naslov') ? trim((string) $this->input('naslov')) : null,
            'opis' => $this->filled('opis') ? trim((string) $this->input('opis')) : null,
            'category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
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
            'organizer_manual_name' => ['prohibited'],
            'category_id' => ['nullable', 'integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
        ] + $this->eventCoverUploadRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var CulturalEventEntry $entry */
            $entry = $this->route('moderator_dogadjaj');
            /** @var EventCatalogGuard $guard */
            $guard = app(EventCatalogGuard::class);

            try {
                if ((int) $this->input('category_id') !== (int) $entry->category_id) {
                    $guard->assertCategoryAllowedForNewLink($this->input('category_id'));
                }

                $currentIds = $entry->tags()->pluck('cultural_tags.id')->map(fn ($id) => (int) $id)->all();
                $newIds = $this->input('tag_ids', []);
                $added = array_values(array_diff($newIds, $currentIds));
                if ($added !== []) {
                    $guard->assertTagsAllowedForNewLinks($added);
                }
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
     *     organizer_manual_name: null,
     *     category_id: ?int,
     *     tag_ids: list<int>,
     *     featured: bool
     * }
     */
    public function domainPayload(): array
    {
        /** @var CulturalEventEntry $entry */
        $entry = $this->route('moderator_dogadjaj');

        return [
            'naslov' => $this->input('naslov'),
            'opis' => $this->input('opis'),
            'organizer_id' => $entry->organizer_id,
            'organizer_manual_name' => null,
            'category_id' => $this->input('category_id'),
            'tag_ids' => $this->input('tag_ids', []),
            'featured' => false,
        ];
    }
}
