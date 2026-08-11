<?php

namespace App\Http\Requests;

use App\Models\CulturalModeratorRequest;
use App\Support\CulturalPortalAccess;
use Illuminate\Foundation\Http\FormRequest;

class CulturalRequestDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CulturalPortalAccess::isKkEditor($this->user());
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('decision_note');
        $this->merge([
            'decision_note' => is_string($note) && trim($note) === '' ? null : (is_string($note) ? trim($note) : $note),
        ]);
    }

    public function rules(): array
    {
        // PO-ORG-05: Org-create reject requires note.
        if ($this->routeIs('cultural-organizer-creation-requests.reject')) {
            return [
                'decision_note' => ['required', 'string', 'max:5000'],
            ];
        }

        // PO-ORG-06 / BR-317: subsequent ADD reject requires note. REMOVE reject note not required.
        if ($this->routeIs('cultural-moderator-requests.reject')) {
            $moderatorRequest = $this->route('zahtjev');
            if ($moderatorRequest instanceof CulturalModeratorRequest
                && $moderatorRequest->type === CulturalModeratorRequest::TYPE_ADD
            ) {
                return [
                    'decision_note' => ['required', 'string', 'max:5000'],
                ];
            }
        }

        return [
            'decision_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision_note.required' => 'Napomena je obavezna prilikom odbijanja zahtjeva.',
        ];
    }
}
