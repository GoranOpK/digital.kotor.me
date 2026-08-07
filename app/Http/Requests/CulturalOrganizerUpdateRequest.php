<?php

namespace App\Http\Requests;

use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;

class CulturalOrganizerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CulturalPortalAccess::isKkEditor($this->user());
    }

    protected function prepareForValidation(): void
    {
        $naziv = $this->input('naziv');
        $opis = $this->input('opis');
        $email = $this->input('contact_email');
        $phone = $this->input('contact_phone');
        $website = $this->input('website');

        $this->merge([
            'naziv' => is_string($naziv) ? trim($naziv) : $naziv,
            'opis' => is_string($opis) && trim($opis) === '' ? null : (is_string($opis) ? trim($opis) : $opis),
            'contact_email' => is_string($email) && trim($email) === '' ? null : (is_string($email) ? trim($email) : $email),
            'contact_phone' => is_string($phone) && trim($phone) === '' ? null : (is_string($phone) ? trim($phone) : $phone),
            'website' => is_string($website) && trim($website) === '' ? null : (is_string($website) ? trim($website) : $website),
        ]);
    }

    public function rules(): array
    {
        return [
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string', 'max:5000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }
}
