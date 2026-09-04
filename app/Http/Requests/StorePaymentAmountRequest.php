<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Unesite iznos.',
            'amount.numeric' => 'Iznos mora biti broj.',
            'amount.gt' => 'Iznos mora biti veći od 0.',
            'amount.regex' => 'Iznos mora biti u EUR sa najviše dvije decimale.',
        ];
    }
}
