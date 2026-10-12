<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ShopSettings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The signed-in person's own settings: their name and email, and signing out
 * of other devices. (Password changes go through PasswordController, and the
 * owner's business settings through ShopSettingsController.)
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings', [
            'otherDevices' => $this->otherDevices($request),
            'shop' => $request->user()->isSuperAdmin() ? $this->shopSettings() : null,
        ]);
    }

    /**
     * What the owner's section of the page needs: each setting's current value,
     * whether it differs from the shipped default, who changed it, and the
     * state of backups.
     *
     * @return array<string, mixed>
     */
    private function shopSettings(): array
    {
        $describe = fn (string $key) => ShopSettings::lastChange($key);
        $backup = Setting::query()->where('key', ShopSettings::LAST_BACKUP)->first();
        $backupAt = $backup?->value['at'] ?? null;

        return [
            'brackets' => ShopSettings::serviceChargeBrackets(),
            'bracketsCustom' => ShopSettings::isCustom(ShopSettings::BRACKETS),
            'bracketsChange' => $describe(ShopSettings::BRACKETS),

            'quickAmounts' => ShopSettings::quickAmounts(),
            'quickCustom' => ShopSettings::isCustom(ShopSettings::QUICK_AMOUNTS),
            'quickChange' => $describe(ShopSettings::QUICK_AMOUNTS),

            'autoClose' => ShopSettings::autoCloseAfterHours(),
            'autoCloseCustom' => ShopSettings::isCustom(ShopSettings::AUTO_CLOSE_HOURS),
            'autoCloseChange' => $describe(ShopSettings::AUTO_CLOSE_HOURS),

            'defaults' => [
                'autoClose' => (int) config('gtrack.auto_close_after_hours'),
            ],

            'backup' => [
                'at' => $backupAt ? Carbon::parse($backupAt) : null,
                'via' => $backup?->value['via'] ?? null,
                'rows' => $backup?->value['rows'] ?? null,
                'filesOnThisPc' => count(array_filter(
                    Storage::disk('local')->files('backups'),
                    fn (string $file) => str_ends_with($file, '.sql'),
                )),
            ],
        ];
    }

    /**
     * Change my own name and email.
     *
     * Role is deliberately not here: nobody promotes themselves.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        // Own error bag so Settings reopens this form, not the password one.
        $data = $request->validateWithBag('updateProfile', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->forceFill([
            'name' => trim($data['name']),
            'email' => $data['email'],
        ])->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Sign this account out everywhere except here — for the phone someone
     * left logged in. Asks for the current password first, so an unattended
     * open screen can't be used to lock the real owner out.
     */
    public function signOutOtherDevices(Request $request): RedirectResponse
    {
        $request->validateWithBag('signOutOthers', [
            'current_password' => ['required', 'current_password'],
        ]);

        // Only the database session driver can be enumerated per user.
        if (config('session.driver') !== 'database') {
            return back()->with('status', 'sign-out-others-unavailable');
        }

        $user = $request->user();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        // Also ends "remember me" logins on other devices.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return back()->with('status', 'signed-out-others');
    }

    /**
     * How many other devices are signed in as this user, and when one was last
     * active. Null when the session driver can't tell us.
     *
     * @return array{count: int, last_active: ?Carbon}|null
     */
    private function otherDevices(Request $request): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        $others = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId());

        $lastActivity = (clone $others)->max('last_activity');

        return [
            'count' => (clone $others)->count(),
            'last_active' => $lastActivity ? Carbon::createFromTimestamp((int) $lastActivity) : null,
        ];
    }
}
