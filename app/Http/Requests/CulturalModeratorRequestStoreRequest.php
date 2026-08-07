<?php

namespace App\Http\Requests;

use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturalModeratorRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var CulturalOrganizer|null $organizer */
        $organizer = $this->route('organizatori');

        if (! $user || ! $organizer instanceof CulturalOrganizer) {
            return false;
        }

        return CulturalPortalAccess::canModerateOrganizer($user, $organizer);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'target_user_id' => $this->input('target_user_id') !== null
                ? (int) $this->input('target_user_id')
                : null,
            'type' => is_string($this->input('type')) ? trim($this->input('type')) : $this->input('type'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(CulturalModeratorRequest::TYPES)],
            'target_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('activation_status', 'active')
                        ->whereNotNull('email_verified_at');
                }),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_user_id.exists' => 'Ciljni korisnik mora biti postojeći aktivan nalog Digital Kotor.',
        ];
    }
}
