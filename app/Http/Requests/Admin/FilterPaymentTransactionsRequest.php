<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPaymentTransactionsRequest extends FormRequest
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
        $statuses = array_map(fn (PaymentStatus $status) => $status->value, PaymentStatus::cases());

        return [
            'status' => ['nullable', 'string', Rule::in(array_merge(['all'], $statuses))],
            'payment_type_id' => ['nullable', 'integer', 'exists:payment_types,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:64'],
            'user' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['status', 'q', 'user'] as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
