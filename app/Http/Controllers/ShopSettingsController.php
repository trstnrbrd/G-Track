<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\DatabaseBackup;
use App\Support\ShopSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The owner's shop settings: service charge rates, quick amounts, the
 * auto-close cutoff, and downloading a backup. Every route is behind the
 * `owner` middleware.
 *
 * A saved value overrides config/gtrack.php; "Reset" deletes the row so the
 * shipped default applies again. Past transactions are never touched — each
 * one stored the fee it was recorded with.
 */
class ShopSettingsController extends Controller
{
    /** The three settings the owner can change, by their URL name. */
    public const KEYS = [
        'rates' => ShopSettings::BRACKETS,
        'quick-amounts' => ShopSettings::QUICK_AMOUNTS,
        'auto-close' => ShopSettings::AUTO_CLOSE_HOURS,
    ];

    public function updateRates(Request $request): RedirectResponse
    {
        $request->validateWithBag('rates', [
            'brackets' => ['required', 'array', 'min:1', 'max:12'],
            'brackets.*.up_to' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'brackets.*.fee' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'brackets.*.fee.required' => 'Every row needs a fee.',
            'brackets.*.fee.numeric' => 'A fee has to be a number.',
            'brackets.*.fee.min' => 'A fee can\'t be negative.',
            'brackets.*.up_to.numeric' => 'An upper limit has to be a number.',
            'brackets.*.up_to.gt' => 'An upper limit has to be more than ₱0.',
        ]);

        $rows = array_values($request->input('brackets'));
        $last = count($rows) - 1;

        // The final row is always the catch-all, whatever was typed in it.
        $brackets = array_map(fn (array $row, int $i) => [
            'up_to' => $i === $last ? null : $this->number($row['up_to'] ?? null),
            'fee' => $this->number($row['fee']),
        ], $rows, array_keys($rows));

        if (! ShopSettings::validBrackets($brackets)) {
            throw ValidationException::withMessages([
                'brackets' => 'Each "up to" amount must be more than the one above it.',
            ])->errorBag('rates');
        }

        Setting::put(ShopSettings::BRACKETS, $brackets, $request->user()->id);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Service charge rates saved',
            'text' => 'New transactions use these rates from now on. Past transactions keep the fee they were recorded with.',
        ]);
    }

    public function updateQuickAmounts(Request $request): RedirectResponse
    {
        $request->validateWithBag('quickAmounts', [
            'quick_amounts' => ['required', 'string', 'max:200'],
        ]);

        $amounts = collect(preg_split('/[\s,]+/', trim((string) $request->input('quick_amounts')), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($value) => is_numeric($value) ? $this->number($value) : null)
            ->all();

        if (in_array(null, $amounts, true) || ! ShopSettings::validQuickAmounts($amounts)) {
            throw ValidationException::withMessages([
                'quick_amounts' => 'Use 1 to 8 different amounts between ₱1 and ₱1,000,000, separated by commas.',
            ])->errorBag('quickAmounts');
        }

        sort($amounts);

        Setting::put(ShopSettings::QUICK_AMOUNTS, array_values($amounts), $request->user()->id);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Quick amounts saved',
            'text' => 'The buttons in the Cash In / Cash Out form are updated.',
        ]);
    }

    public function updateAutoClose(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('autoClose', [
            'hours' => ['required', 'integer', 'between:1,48'],
        ], [
            'hours.between' => 'Choose between 1 and 48 hours.',
            'hours.integer' => 'Enter a whole number of hours.',
        ]);

        Setting::put(ShopSettings::AUTO_CLOSE_HOURS, (int) $data['hours'], $request->user()->id);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Auto-close saved',
            'text' => "A day left open for more than {$data['hours']} hours will be closed automatically.",
        ]);
    }

    /** Put one setting back to the shipped default. */
    public function reset(string $setting): RedirectResponse
    {
        abort_unless(array_key_exists($setting, self::KEYS), 404);

        Setting::forget(self::KEYS[$setting]);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Back to the default',
            'text' => 'That setting is using its original value again.',
        ]);
    }

    /**
     * Stream a backup straight to the browser and note when it was taken.
     * It holds password hashes, so it is owner-only and never cached.
     */
    public function downloadBackup(Request $request): StreamedResponse
    {
        $userId = $request->user()->id;

        return response()->streamDownload(function () use ($userId) {
            $rows = DatabaseBackup::stream(function (string $chunk) {
                echo $chunk;
            });

            Setting::put(ShopSettings::LAST_BACKUP, [
                'at' => now()->toIso8601String(),
                'via' => 'download',
                'rows' => $rows,
            ], $userId);
        }, DatabaseBackup::filename(), [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /** 500.0 → 500, 12.5 → 12.5: the way the config file writes them. */
    private function number(mixed $value): int|float|null
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $float = round((float) $value, 2);

        return floor($float) === $float ? (int) $float : $float;
    }
}
