<?php

namespace App\Http\Requests;

use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturalOrganizerCreationRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && CulturalPortalAccess::isPlatformUserActive($user);
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
            'proposed_moderator_user_id' => $this->input('proposed_moderator_user_id') !== null
                ? (int) $this->input('proposed_moderator_user_id')
                : null,
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
            'proposed_moderator_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('activation_status', 'active')
                        ->whereNotNull('email_verified_at');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proposed_moderator_user_id.required' => 'Predloženi Moderator mora biti izabran preko postojećeg korisničkog naloga.',
            'proposed_moderator_user_id.exists' => 'Predloženi Moderator mora biti postojeći aktivan nalog Digital Kotor.',
        ];
    }
}
