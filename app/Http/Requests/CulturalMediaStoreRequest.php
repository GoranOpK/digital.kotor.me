<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCulturalCatalogItem;
use App\Models\CulturalMedia;
use App\Services\CulturalMedia\CulturalMediaFileValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CulturalMediaStoreRequest extends FormRequest
{
    use ValidatesCulturalCatalogItem;

    public function authorize(): bool
    {
        return $this->authorizeKkAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCatalogNazivAndOpis();
        $this->trimOptionalStrings(['alt_tekst', 'autor', 'izvor', 'licenca']);
    }

    public function rules(): array
    {
        return [
            'naziv' => ['required', 'string', 'max:255'],
            'alt_tekst' => ['required', 'string', 'max:255'],
            'namjena' => ['required', Rule::in(CulturalMedia::PURPOSES)],
            'status' => ['required', Rule::in(CulturalMedia::STATUSES)],
            'opis' => ['nullable', 'string', 'max:5000'],
            'autor' => ['nullable', 'string', 'max:255'],
            'izvor' => ['nullable', 'string', 'max:255'],
            'licenca' => ['nullable', 'string', 'max:255'],
            'fajl' => ['required', 'file', 'max:'.CulturalMediaFileValidator::MAX_KILOBYTES],
        ];
    }

    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv medija je obavezan.',
            'alt_tekst.required' => 'ALT tekst je obavezan.',
            'namjena.required' => 'Namjena je obavezna.',
            'namjena.in' => 'Namjena nije validna.',
            'status.required' => 'Status je obavezan.',
            'status.in' => 'Status nije validan.',
            'fajl.required' => 'Fotografija je obavezna.',
            'fajl.file' => 'Morate učitati validan fajl.',
            'fajl.max' => 'Fotografija ne smije biti veća od 2 MB.',
        ];
    }

    /**
     * @param  list<string>  $fields
     */
    private function trimOptionalStrings(array $fields): void
    {
        $merged = [];
        foreach ($fields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));
                $merged[$field] = $value === '' ? null : $value;
            }
        }
        if ($merged !== []) {
            $this->merge($merged);
        }
    }
}
