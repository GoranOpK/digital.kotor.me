<?php

namespace App\Http\Requests;

use App\Models\CulturalModeratorAuthorization;
use App\Models\CulturalModeratorRequest;
use App\Models\CulturalOrganizer;
use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * PO-ORG-06 Package 5 — privacy-safe ADD (name+email) / REMOVE (active Moderators only).
 */
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
        $type = $this->input('type');
        $name = $this->input('proposed_moderator_name');
        $email = $this->input('proposed_moderator_email');

        $merge = [
            'type' => is_string($type) ? trim($type) : $type,
        ];

        if ($this->input('type') === CulturalModeratorRequest::TYPE_ADD) {
            $merge['proposed_moderator_name'] = is_string($name) ? trim($name) : $name;
            $merge['proposed_moderator_email'] = is_string($email)
                ? Str::lower(trim($email))
                : $email;
        }

        if ($this->input('type') === CulturalModeratorRequest::TYPE_REMOVE) {
            $merge['target_user_id'] = $this->input('target_user_id') !== null
                ? (int) $this->input('target_user_id')
                : null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $type = $this->input('type');

        if ($type === CulturalModeratorRequest::TYPE_ADD) {
            return [
                'type' => ['required', Rule::in([CulturalModeratorRequest::TYPE_ADD])],
                'proposed_moderator_name' => ['required', 'string', 'max:255'],
                'proposed_moderator_email' => ['required', 'email', 'max:255'],
                'target_user_id' => ['prohibited'],
            ];
        }

        if ($type === CulturalModeratorRequest::TYPE_REMOVE) {
            return [
                'type' => ['required', Rule::in([CulturalModeratorRequest::TYPE_REMOVE])],
                'target_user_id' => ['required', 'integer'],
                'proposed_moderator_name' => ['prohibited'],
                'proposed_moderator_email' => ['prohibited'],
            ];
        }

        return [
            'type' => ['required', Rule::in(CulturalModeratorRequest::TYPES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== CulturalModeratorRequest::TYPE_REMOVE) {
                return;
            }

            /** @var CulturalOrganizer|null $organizer */
            $organizer = $this->route('organizatori');
            $targetId = (int) $this->input('target_user_id');

            if (! $organizer instanceof CulturalOrganizer || $targetId <= 0) {
                return;
            }

            $isActiveModerator = CulturalModeratorAuthorization::query()
                ->where('organizer_id', $organizer->id)
                ->where('user_id', $targetId)
                ->where('status', CulturalModeratorAuthorization::STATUS_ACTIVE)
                ->exists();

            if (! $isActiveModerator) {
                $validator->errors()->add(
                    'target_user_id',
                    'Ciljni korisnik mora biti aktivni Moderator ovog Organizatora.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'proposed_moderator_name.required' => 'Ime i prezime predloženog Moderatora je obavezno.',
            'proposed_moderator_email.required' => 'E-mail predloženog Moderatora je obavezan.',
            'proposed_moderator_email.email' => 'E-mail predloženog Moderatora mora biti ispravan format.',
            'target_user_id.prohibited' => 'Predloženog Moderatora nije dozvoljeno birati preko korisničkog identifikatora.',
            'target_user_id.required' => 'Morate izabrati aktivnog Moderatora za uklanjanje.',
        ];
    }
}
