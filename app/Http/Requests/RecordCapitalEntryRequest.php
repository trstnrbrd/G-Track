<?php

namespace App\Http\Requests;

use App\Models\CapitalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCapitalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isBorrow = $this->input('kind') === CapitalEntry::KIND_BORROW;

        return [
            'kind' => ['required', Rule::in([
                CapitalEntry::KIND_BORROW,
                CapitalEntry::KIND_REPAY,
                CapitalEntry::KIND_CAPITAL,
            ])],

            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000'],

            // 'none' = nothing moved. A borrow always takes the money from
            // somewhere, so it must name the balance.
            'source' => [
                'required',
                Rule::in($isBorrow
                    ? [CapitalEntry::BALANCE_CASH, CapitalEntry::BALANCE_GCASH]
                    : [CapitalEntry::BALANCE_CASH, CapitalEntry::BALANCE_GCASH, 'none']),
            ],

            'purpose' => ['nullable', Rule::in(array_keys(CapitalEntry::PURPOSES))],

            'note' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'The amount must be greater than ₱0.',
            'source.required' => 'Choose where the money came from or went to.',
            'source.in' => 'Money taken out has to come from the cash or the GCash wallet.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => is_string($this->input('amount'))
                ? str_replace([',', ' ', '₱'], '', $this->input('amount'))
                : $this->input('amount'),
            'note' => is_string($this->input('note')) ? trim($this->input('note')) : $this->input('note'),
        ]);
    }
}
