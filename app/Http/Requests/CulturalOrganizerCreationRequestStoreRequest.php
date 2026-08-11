<?php

namespace App\Http\Requests;

use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * PO-ORG-06 Package 2 — privacy-safe Organizer creation submit (name + email).
 */
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
        $proposedName = $this->input('proposed_moderator_name');
        $proposedEmail = $this->input('proposed_moderator_email');

        $this->merge([
            'naziv' => is_string($naziv) ? trim($naziv) : $naziv,
            'opis' => is_string($opis) && trim($opis) === '' ? null : (is_string($opis) ? trim($opis) : $opis),
            'contact_email' => is_string($email) && trim($email) === '' ? null : (is_string($email) ? trim($email) : $email),
            'contact_phone' => is_string($phone) && trim($phone) === '' ? null : (is_string($phone) ? trim($phone) : $phone),
            'website' => is_string($website) && trim($website) === '' ? null : (is_string($website) ? trim($website) : $website),
            'proposed_moderator_name' => is_string($proposedName) ? trim($proposedName) : $proposedName,
            'proposed_moderator_email' => is_string($proposedEmail)
                ? Str::lower(trim($proposedEmail))
                : $proposedEmail,
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
            'proposed_moderator_name' => ['required', 'string', 'max:255'],
            'proposed_moderator_email' => ['required', 'email', 'max:255'],
            'proposed_moderator_user_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'proposed_moderator_name.required' => 'Ime i prezime predloženog Moderatora je obavezno.',
            'proposed_moderator_email.required' => 'E-mail predloženog Moderatora je obavezan.',
            'proposed_moderator_email.email' => 'E-mail predloženog Moderatora mora biti ispravan format.',
            'proposed_moderator_user_id.prohibited' => 'Predloženog Moderatora nije dozvoljeno birati preko korisničkog identifikatora.',
        ];
    }
}
