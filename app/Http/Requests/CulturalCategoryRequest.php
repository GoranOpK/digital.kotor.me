<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CulturalCategoryRequest extends FormRequest
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
            'status' => ['required', Rule::in(CulturalCategory::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv kategorije je obavezan.',
            'status.required' => 'Status kategorije je obavezan.',
            'status.in' => 'Status kategorije nije validan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addForbiddenCategoryNameValidation($validator);
        $this->addActiveDuplicateValidation(
            $validator,
            CulturalCategory::class,
            'kategorije',
            CulturalCategory::STATUS_ACTIVE,
            'kategorija'
        );
    }
}
