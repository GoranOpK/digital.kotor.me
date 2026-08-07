<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CulturalTagRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCatalogNazivAndOpis();
    }

    public function rules(): array
    {
        return [
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(CulturalTag::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv oznake je obavezan.',
            'status.required' => 'Status oznake je obavezan.',
            'status.in' => 'Status oznake nije validan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addActiveDuplicateValidation(
            $validator,
            CulturalTag::class,
            'oznake',
            CulturalTag::STATUS_ACTIVE,
            'oznaka'
        );
    }
}
