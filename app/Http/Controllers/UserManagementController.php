<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The owner creates accounts and resets passwords here. There is no public
 * registration, and staff may not have a real inbox, so this is how someone
 * who forgot their password gets back in. Routes are behind the `owner`
 * middleware.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', Rule::in(['all', User::ROLE_OWNER, User::ROLE_STAFF])],
        ]);

        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? 'all';

        $users = User::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($role !== 'all', fn ($q) => $q->where('role', $role))
            ->orderByRaw('role = ? desc', [User::ROLE_OWNER])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('user-management', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role],
            'total' => User::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Own error bag so the page reopens the right modal.
        $data = $request->validateWithBag('createUser', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in([User::ROLE_OWNER, User::ROLE_STAFF])],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => Str::lower(trim($data['email'])),
            'role' => $data['role'],
            'password' => $data['password'], // hashed by the model cast
        ]);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Account created',
            'text' => "{$user->name} can now log in with {$user->email}. Give them the password you set.",
        ]);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('swal', [
                'icon' => 'info',
                'title' => 'Use your profile',
                'text' => 'Change your own password from Settings → Profile.',
            ]);
        }

        $data = $request->validateWithBag('resetPassword', [
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $data['password'], // hashed by the model cast
            'remember_token' => Str::random(60),
        ])->save();

        // Anyone already logged in as them (a phone left signed in) is out.
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Password reset',
            'text' => "{$user->name} was signed out everywhere. Give them the new password.",
        ]);
    }
}
