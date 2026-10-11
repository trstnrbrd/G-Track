<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisesTransactionInput;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    use NormalisesTransactionInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Note what is NOT accepted here: service_charge. The browser shows the
     * customer a preview of the fee, but the server recomputes it from
     * config/gtrack.php. Anything the client sends is ignored.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([Transaction::TYPE_CASH_IN, Transaction::TYPE_CASH_OUT])],

            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],

            'charge_paid_in' => ['required', Rule::in([Transaction::PAID_IN_CASH, Transaction::PAID_IN_GCASH])],

            // Cash In: you need the customer's number to send the money to.
            // Cash Out: they sent to you, so the number is optional.
            'mobile_number' => [
                Rule::requiredIf(fn () => $this->input('type') === Transaction::TYPE_CASH_IN),
                'nullable',
                'regex:/^09\d{9}$/',
            ],

            // Cash Out: the customer's e-receipt reference is the proof the
            // money actually arrived — never record one without it.
            // Cash In: the reference only exists after you send, so it's optional.
            'reference_number' => [
                Rule::requiredIf(fn () => $this->input('type') === Transaction::TYPE_CASH_OUT),
                'nullable',
                'string',
                'max:50',
                // One GCash reference = one real transfer. Recording it twice
                // would move the balances twice for money that moved once.
                Rule::unique('transactions', 'reference_number'),
                // ...including one already logged as a store item paid via GCash.
                Rule::unique('store_sales', 'reference_number'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->contactFieldMessages(),
            'amount.gt' => 'The amount must be greater than ₱0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Operators paste amounts in all sorts of shapes too: "1,500.00", "₱500".
        // The number fields are normalised by the shared trait.
        $this->merge([
            ...$this->normalisedContactFields(),
            'amount' => is_string($this->input('amount'))
                ? str_replace([',', ' ', '₱'], '', $this->input('amount'))
                : $this->input('amount'),
        ]);
    }
}
