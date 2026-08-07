<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TS-010.3a — ažuriranje sadržaja prijedloga izmjene (Moderator nacrt / Urednik pregled).
 * Autorizacija je u kontroleru.
 */
class CulturalEventChangeProposalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $naslov = $this->filled('proposed_naslov')
            ? trim((string) $this->input('proposed_naslov'))
            : null;
        $opis = $this->filled('proposed_opis')
            ? trim((string) $this->input('proposed_opis'))
            : null;

        if ($naslov === '') {
            $naslov = null;
        }
        if ($opis === '') {
            $opis = null;
        }

        $this->merge([
            'proposed_naslov' => $naslov,
            'proposed_opis' => $opis,
            'proposed_category_id' => $this->filled('proposed_category_id')
                ? (int) $this->input('proposed_category_id')
                : null,
            'proposed_cover_media_id' => $this->filled('proposed_cover_media_id')
                ? (int) $this->input('proposed_cover_media_id')
                : null,
            'tag_ids' => array_values(array_unique(array_map(
                'intval',
                (array) $this->input('tag_ids', [])
            ))),
        ]);
    }

    public function rules(): array
    {
        return [
            'proposed_naslov' => ['nullable', 'string', 'max:255'],
            'proposed_opis' => ['nullable', 'string'],
            'proposed_category_id' => ['nullable', 'integer'],
            'proposed_cover_media_id' => ['nullable', 'integer'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array{
     *     proposed_naslov: ?string,
     *     proposed_opis: ?string,
     *     proposed_category_id: ?int,
     *     proposed_cover_media_id: ?int,
     *     tag_ids: list<int>
     * }
     */
    public function domainPayload(): array
    {
        return [
            'proposed_naslov' => $this->input('proposed_naslov'),
            'proposed_opis' => $this->input('proposed_opis'),
            'proposed_category_id' => $this->input('proposed_category_id'),
            'proposed_cover_media_id' => $this->input('proposed_cover_media_id'),
            'tag_ids' => $this->input('tag_ids', []),
        ];
    }
}
