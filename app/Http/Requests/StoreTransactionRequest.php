<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
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
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Enter an 11-digit mobile number starting with 09 (e.g. 09171234567).',
            'mobile_number.required' => 'The customer\'s mobile number is required for a cash in — that\'s where the money is sent.',
            'reference_number.required' => 'A reference number is required for a cash out. Ask the customer for their GCash receipt.',
            'reference_number.unique' => 'This reference number is already recorded — that transaction has been logged before.',
            'amount.gt' => 'The amount must be greater than ₱0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Operators paste numbers in all sorts of shapes: "0917 123 4567",
        // "+639171234567", "1,500.00". Normalise before the rules run.
        $this->merge([
            'mobile_number' => $this->normaliseMobile($this->input('mobile_number')),
            'amount' => is_string($this->input('amount'))
                ? str_replace([',', ' ', '₱'], '', $this->input('amount'))
                : $this->input('amount'),
            'reference_number' => is_string($this->input('reference_number'))
                ? trim($this->input('reference_number'))
                : $this->input('reference_number'),
        ]);
    }

    private function normaliseMobile(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return null;
        }

        // +63 917 123 4567 / 63917... / 9171234567 all mean the same number.
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
