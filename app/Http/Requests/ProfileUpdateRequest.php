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
        $user = $this->user();
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'user_type' => ['required', 'string', 'in:Fizičko lice,Preduzetnik,Ortačko društvo,Komanditno društvo,Društvo sa ograničenom odgovornošću,Akcionarsko društvo,Dio stranog društva (predstavništvo ili poslovna jedinica),Udruženje (nvo, fondacije, sportske organizacije),Ustanova (državne i privatne),Druge organizacije (Političke partije, Verske zajednice, Komore, Sindikati)'],
            'residential_status' => ['required', 'string', 'in:resident,non-resident,ex-non-resident'],
        ];

        // Validacija za JMB (obavezno za fizička lica)
        if ($this->input('user_type') === 'Fizičko lice') {
            $rules['jmb'] = [
                'required',
                'string',
                'size:13',
                'regex:/^[0-9]{13}$/',
                Rule::unique(User::class)->ignore($user->id),
            ];
            $rules['pib'] = ['nullable'];
        } else {
            // Validacija za PIB (obavezno za pravna lica)
            $rules['pib'] = [
                'required',
                'string',
                'size:9',
                'regex:/^[0-9]{9}$/',
                Rule::unique(User::class)->ignore($user->id),
            ];
            $rules['jmb'] = ['nullable'];
        }

        // Validacija za passport (opciono za nerezidente)
        if ($this->input('residential_status') !== 'resident') {
            $rules['passport_number'] = [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($user->id),
            ];
        } else {
            $rules['passport_number'] = ['nullable'];
        }

        return $rules;
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
            'user_type.required' => 'Tip korisnika je obavezan.',
            'user_type.in' => 'Izaberite validan tip korisnika.',
            'residential_status.required' => 'Status rezidentnosti je obavezan.',
            'residential_status.in' => 'Izaberite validan status rezidentnosti.',
            'jmb.required' => 'JMB je obavezan za fizička lica.',
            'jmb.size' => 'JMB mora imati tačno 13 cifara.',
            'jmb.regex' => 'JMB mora sadržati samo cifre.',
            'jmb.unique' => 'JMB je već u upotrebi.',
            'pib.required' => 'PIB je obavezan za pravna lica.',
            'pib.size' => 'PIB mora imati tačno 9 cifara.',
            'pib.regex' => 'PIB mora sadržati samo cifre.',
            'pib.unique' => 'PIB je već u upotrebi.',
            'passport_number.unique' => 'Broj pasoša je već u upotrebi.',
        ];
    }
}
