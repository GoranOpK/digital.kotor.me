<?php

namespace App\Http\Requests;

use App\Exceptions\CulturalEventDomainException;
use App\Http\Requests\Concerns\HandlesEventCoverUpload;
use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Services\CulturalEventDomain\EventCatalogGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CulturalEventEntryStoreRequest extends FormRequest
{
    use HandlesEventCoverUpload;
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
            'organizer_id' => ['prohibited'],
            'organizer_manual_name' => ['nullable', 'string', 'max:255'],
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

            /** @var EventCatalogGuard $guard */
            $guard = app(EventCatalogGuard::class);

            try {
                $guard->assertCategoryAllowedForNewLink($this->input('category_id'));
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
     *     organizer_id: null,
     *     organizer_manual_name: ?string,
     *     category_id: ?int,
     *     tag_ids: list<int>,
     *     featured: bool
     * }
     */
    public function domainPayload(): array
    {
        return [
            'naslov' => $this->input('naslov'),
            'opis' => $this->input('opis'),
            'organizer_id' => null,
            'organizer_manual_name' => $this->input('organizer_manual_name'),
            'category_id' => $this->input('category_id'),
            'tag_ids' => $this->input('tag_ids', []),
            'featured' => false,
        ];
    }
}
