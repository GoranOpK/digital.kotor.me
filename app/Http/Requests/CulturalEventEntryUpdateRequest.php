<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalEventEntry;
use App\Services\CulturalEventDomain\EventCatalogGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalEventEntryUpdateRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        $manual = $this->filled('organizer_manual_name')
            ? trim((string) $this->input('organizer_manual_name'))
            : null;

        $this->merge([
            'naslov' => $this->filled('naslov') ? trim((string) $this->input('naslov')) : null,
            'opis' => $this->filled('opis') ? trim((string) $this->input('opis')) : null,
            'organizer_manual_name' => $manual === '' ? null : $manual,
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
        /** @var CulturalEventEntry|null $entry */
        $entry = $this->route('kanonski_dogadjaj');
        $hasRegisteredOrganizer = $entry instanceof CulturalEventEntry && $entry->organizer_id !== null;

        return [
            'naslov' => ['nullable', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:20000'],
            'organizer_id' => ['prohibited'],
            'organizer_manual_name' => $hasRegisteredOrganizer
                ? ['prohibited']
                : ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'cover_media_id' => ['nullable', 'integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
            'status' => ['prohibited'],
            'featured' => ['prohibited'],
            'cancellation_reason' => ['prohibited'],
            'return_reason' => ['prohibited'],
            'archived_from_status' => ['prohibited'],
            'first_submitted_at' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var CulturalEventEntry $entry */
            $entry = $this->route('kanonski_dogadjaj');

            if (! $entry->isDraft() && ! $entry->isDirectFlowPublishedContentEditable()) {
                $validator->errors()->add(
                    'domain',
                    'Sadržaj se može mijenjati samo u pripremi ili na Objavljenom događaju bez registrovanog Organizatora.'
                );

                return;
            }

            /** @var EventCatalogGuard $guard */
            $guard = app(EventCatalogGuard::class);

            try {
                if ((int) $this->input('category_id') !== (int) $entry->category_id) {
                    $guard->assertCategoryAllowedForNewLink($this->input('category_id'));
                }
                if ((int) $this->input('cover_media_id') !== (int) $entry->cover_media_id) {
                    $guard->assertCoverMediaAllowedForNewLink($this->input('cover_media_id'));
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
     *     organizer_id: ?int,
     *     organizer_manual_name: ?string,
     *     category_id: ?int,
     *     cover_media_id: ?int,
     *     tag_ids: list<int>,
     *     featured?: bool
     * }
     */
    public function domainPayload(): array
    {
        /** @var CulturalEventEntry $entry */
        $entry = $this->route('kanonski_dogadjaj');

        $payload = [
            'naslov' => $this->input('naslov'),
            'opis' => $this->input('opis'),
            'organizer_id' => $entry->organizer_id,
            'organizer_manual_name' => $entry->organizer_id !== null
                ? null
                : $this->input('organizer_manual_name'),
            'category_id' => $this->input('category_id'),
            'cover_media_id' => $this->input('cover_media_id'),
            'tag_ids' => $this->input('tag_ids', []),
        ];

        // Draft: featured nije dostupno kroz content formu. Published: isticanje ide zasebnom rutom.
        if ($entry->isDraft()) {
            $payload['featured'] = false;
        }

        return $payload;
    }
}
