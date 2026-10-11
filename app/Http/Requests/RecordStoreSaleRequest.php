<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordStoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],

            // Optional: the customer's GCash receipt, if they show one. Unique
            // across both tables — a reference already logged as a cash out is
            // the same real transfer, and recording it here too would count
            // the money twice.
            'reference_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('store_sales', 'reference_number'),
                Rule::unique('transactions', 'reference_number'),
            ],

            'note' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'The amount must be greater than ₱0.',
            'reference_number.unique' => 'This reference number is already recorded — that GCash transfer has been logged before.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => is_string($this->input('amount'))
                ? str_replace([',', ' ', '₱'], '', $this->input('amount'))
                : $this->input('amount'),
            'reference_number' => is_string($this->input('reference_number'))
                ? trim($this->input('reference_number'))
                : $this->input('reference_number'),
            'note' => is_string($this->input('note'))
                ? trim($this->input('note'))
                : $this->input('note'),
        ]);
    }
}
