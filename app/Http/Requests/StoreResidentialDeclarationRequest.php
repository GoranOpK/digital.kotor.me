<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResidentialDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'residential_status' => ['required', 'string', 'in:resident,non-resident'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'residential_status.required' => 'Odaberite status rezidentnosti.',
            'residential_status.in' => 'Odaberite Rezident ili Nerezident.',
        ];
    }
}
