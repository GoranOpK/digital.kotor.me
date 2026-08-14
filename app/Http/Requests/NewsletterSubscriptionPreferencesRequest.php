<?php

namespace App\Http\Requests;

use App\Models\CulturalOrganizer;
use App\Models\NewsletterSubscription;
use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NewsletterSubscriptionPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CulturalPortalAccess::isPlatformUserActive($this->user());
    }

    protected function prepareForValidation(): void
    {
        $organizerIds = $this->input('organizer_ids', []);
        if (! is_array($organizerIds)) {
            $organizerIds = [];
        }

        $this->merge([
            'organizer_ids' => array_values(array_unique(array_map('intval', $organizerIds))),
            'include_without_organizer' => $this->boolean('include_without_organizer'),
        ]);
    }

    public function rules(): array
    {
        return [
            'scope_mode' => ['required', 'string', Rule::in([
                NewsletterSubscription::SCOPE_ALL_EVENTS,
                NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS,
            ])],
            'organizer_ids' => ['nullable', 'array'],
            'organizer_ids.*' => ['integer', Rule::exists('cultural_organizers', 'id')],
            'include_without_organizer' => ['required', 'boolean'],
            'user_id' => ['prohibited'],
            'email' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'scope_mode.required' => 'Odaberite Svi događaji ili Odabrani organizatori.',
            'scope_mode.in' => 'Odaberite Svi događaji ili Odabrani organizatori.',
            'organizer_ids.*.exists' => 'Izabrani Organizator ne postoji.',
            'user_id.prohibited' => 'Identitet pretplate određuje prijavljeni nalog.',
            'email.prohibited' => 'Newsletter se ne upravlja unosom e-mail adrese.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $scopeMode = $this->input('scope_mode');
            if ($scopeMode !== NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS) {
                return;
            }

            $organizerIds = $this->organizerIds();
            $includeWithout = $this->boolean('include_without_organizer');

            if ($organizerIds === [] && ! $includeWithout) {
                $validator->errors()->add(
                    'organizer_ids',
                    'Izaberite najmanje jednog Organizatora ili opciju Bez organizatora.'
                );

                return;
            }

            $allowedInactiveIds = $this->currentlySavedOrganizerIds();

            foreach ($organizerIds as $organizerId) {
                $organizer = CulturalOrganizer::query()->find($organizerId);
                if ($organizer === null) {
                    continue;
                }

                if ($organizer->isActive()) {
                    continue;
                }

                if (! in_array($organizer->id, $allowedInactiveIds, true)) {
                    $validator->errors()->add(
                        'organizer_ids',
                        'Neaktivni Organizator se ne može novo izabrati.'
                    );
                }
            }
        });
    }

    /**
     * @return list<int>
     */
    public function organizerIds(): array
    {
        if ($this->input('scope_mode') !== NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS) {
            return [];
        }

        /** @var list<int> $ids */
        $ids = $this->input('organizer_ids', []);

        return $ids;
    }

    public function includeWithoutOrganizer(): bool
    {
        if ($this->input('scope_mode') !== NewsletterSubscription::SCOPE_SELECTED_ORGANIZERS) {
            return false;
        }

        return $this->boolean('include_without_organizer');
    }

    /**
     * @return list<int>
     */
    private function currentlySavedOrganizerIds(): array
    {
        $user = $this->user();
        if ($user === null) {
            return [];
        }

        $subscription = $user->newsletterSubscription()->first();
        if ($subscription === null || ! $subscription->isActive()) {
            return [];
        }

        return $subscription->organizers()->pluck('cultural_organizers.id')->map(fn ($id) => (int) $id)->all();
    }
}
