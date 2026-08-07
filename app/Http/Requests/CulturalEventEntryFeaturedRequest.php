<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use Illuminate\Foundation\Http\FormRequest;

class CulturalEventEntryFeaturedRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    public function rules(): array
    {
        return [
            'featured' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('featured')) {
            $this->merge([
                'featured' => filter_var($this->input('featured'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
