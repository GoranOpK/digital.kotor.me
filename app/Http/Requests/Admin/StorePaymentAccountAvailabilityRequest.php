<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesCanonicalAvailabilityRule;
use App\Models\PaymentAccountAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentAccountAvailabilityRequest extends FormRequest
{
    use ValidatesCanonicalAvailabilityRule;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareAvailabilityForValidation();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->availabilityFieldRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->configureAvailabilityValidator($validator);

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $account = $this->route('payment_account');
            $exists = PaymentAccountAvailability::query()
                ->where('payment_account_id', $account->id)
                ->where('user_type', $this->input('user_type'))
                ->when(
                    $this->input('residential_status') === null,
                    fn ($query) => $query->whereNull('residential_status'),
                    fn ($query) => $query->where('residential_status', $this->input('residential_status'))
                )
                ->exists();

            if ($exists) {
                $validator->errors()->add('user_type', 'Ista kombinacija dostupnosti već postoji za ovaj račun.');
            }
        });
    }
}
