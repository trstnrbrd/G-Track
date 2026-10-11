<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalisesTransactionInput;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    use NormalisesTransactionInput;

    /**
     * Keeps this form's errors apart from the record modal's, which reads the
     * same field names from the default bag.
     */
    protected $errorBag = 'editTransaction';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Only the customer's number and the reference can change after the fact.
     * Type, amount, fee and who paid it decided how the balances moved, so
     * they are deliberately not accepted here: validated() drops anything the
     * browser sends for them, and the stored money figures stay as recorded.
     *
     * The required-ness mirrors recording: a cash in keeps its number, a cash
     * out keeps its reference. The other field can be filled in later, or
     * corrected, but the one the transaction depends on can't be blanked.
     */
    public function rules(): array
    {
        /** @var Transaction $transaction */
        $transaction = $this->route('transaction');

        return [
            'mobile_number' => [
                Rule::requiredIf($transaction->isCashIn()),
                'nullable',
                'regex:/^09\d{9}$/',
            ],

            'reference_number' => [
                Rule::requiredIf($transaction->isCashOut()),
                'nullable',
                'string',
                'max:50',
                // One GCash reference = one real transfer. Keeping its own
                // reference is fine; borrowing another row's is not.
                Rule::unique('transactions', 'reference_number')->ignore($transaction->id),
            ],
        ];
    }

    public function messages(): array
    {
        return $this->contactFieldMessages();
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalisedContactFields());
    }
}
