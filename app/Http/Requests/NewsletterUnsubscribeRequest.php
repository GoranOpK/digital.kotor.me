<?php

namespace App\Http\Requests;

use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;

class NewsletterUnsubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CulturalPortalAccess::isPlatformUserActive($this->user());
    }

    public function rules(): array
    {
        return [
            'confirm_unsubscribe' => ['required', 'accepted'],
            'user_id' => ['prohibited'],
            'email' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_unsubscribe.required' => 'Odjava zahtijeva potvrdu.',
            'confirm_unsubscribe.accepted' => 'Odjava zahtijeva potvrdu.',
            'user_id.prohibited' => 'Identitet pretplate određuje prijavljeni nalog.',
            'email.prohibited' => 'Newsletter se ne upravlja unosom e-mail adrese.',
        ];
    }
}
