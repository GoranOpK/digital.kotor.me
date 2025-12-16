<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ime je obavezno.',
            'last_name.required' => 'Prezime je obavezno.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Unijete validnu email adresu.',
            'email.unique' => 'Email adresa je već u upotrebi.',
            'phone.required' => 'Broj telefona je obavezan.',
            'address.required' => 'Adresa je obavezna.',
            'address.max' => 'Adresa ne može biti duža od 500 karaktera.',
        ];
    }
}
