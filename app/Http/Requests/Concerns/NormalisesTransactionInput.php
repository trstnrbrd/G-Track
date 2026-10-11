<?php

namespace App\Http\Requests\Concerns;

/**
 * The mobile-number and reference-number handling shared by recording a
 * transaction and editing one afterwards, so the two can't drift apart.
 */
trait NormalisesTransactionInput
{
    /**
     * Operators paste numbers in all sorts of shapes: "0917 123 4567",
     * "+639171234567". Normalise before the rules run.
     *
     * @return array{mobile_number: mixed, reference_number: mixed}
     */
    protected function normalisedContactFields(): array
    {
        return [
            'mobile_number' => $this->normaliseMobile($this->input('mobile_number')),
            'reference_number' => is_string($this->input('reference_number'))
                ? trim($this->input('reference_number'))
                : $this->input('reference_number'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function contactFieldMessages(): array
    {
        return [
            'mobile_number.regex' => 'Enter an 11-digit mobile number starting with 09 (e.g. 09171234567).',
            'mobile_number.required' => 'The customer\'s mobile number is required for a cash in — that\'s where the money is sent.',
            'reference_number.required' => 'A reference number is required for a cash out. Ask the customer for their GCash receipt.',
            'reference_number.unique' => 'This reference number is already recorded — that transaction has been logged before.',
        ];
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
