<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturalEventEntryLinkOrganizerRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    public function rules(): array
    {
        return [
            'organizer_id' => [
                'required',
                'integer',
                Rule::exists('cultural_organizers', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'organizer_id.required' => 'Organizator je obavezan.',
            'organizer_id.exists' => 'Izabrani Organizator ne postoji.',
        ];
    }
}
