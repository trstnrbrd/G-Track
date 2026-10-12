<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    /** Generated per run, so no password literal sits in the repo. */
    private string $plain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plain = Str::random(20);

        $this->owner = User::factory()->create(['name' => 'Marites Owner', 'role' => User::ROLE_OWNER]);
        $this->staff = User::factory()->create(['name' => 'Papa Staff', 'role' => User::ROLE_STAFF]);
    }

    private function newUser(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ate Cashier',
            'email' => 'cashier@example.com',
            'role' => 'admin',
            'password' => $this->plain,
        ], $overrides);
    }

    // ---- who may enter -----------------------------------------------------

    public function test_staff_cannot_open_user_management_even_by_url(): void
    {
        $this->actingAs($this->staff)->get(route('user-management'))->assertForbidden();
    }

    public function test_staff_cannot_create_users_or_reset_passwords(): void
    {
        $target = User::factory()->create();
        $attempt = Str::random(20);

        $this->actingAs($this->staff)->post(route('user-management.store'), $this->newUser())->assertForbidden();
        $this->actingAs($this->staff)
            ->post(route('user-management.password', $target), ['password' => $attempt])
            ->assertForbidden();

        $this->assertNull(User::where('email', 'cashier@example.com')->first());
        $this->assertFalse(Hash::check($attempt, $target->fresh()->password));
    }

    public function test_staff_cannot_open_or_write_to_the_capital_page(): void
    {
        $this->actingAs($this->staff)->get(route('capital'))->assertForbidden();
        $this->actingAs($this->staff)
            ->post(route('capital.entries.store'), ['kind' => 'capital', 'amount' => 100, 'source' => 'none'])
            ->assertForbidden();
    }

    public function test_the_owner_can_open_both_pages(): void
    {
        $this->actingAs($this->owner)->get(route('user-management'))->assertOk();
        $this->actingAs($this->owner)->get(route('capital'))->assertOk();
    }

    public function test_staff_do_not_see_the_owner_only_links(): void
    {
        $this->actingAs($this->staff)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('capital').'"', false)
            ->assertDontSee('href="'.route('user-management').'"', false);

        $this->actingAs($this->owner)->get(route('dashboard'))
            ->assertSee('href="'.route('capital').'"', false)
            ->assertSee('href="'.route('user-management').'"', false);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('user-management'))->assertRedirect(route('login'));
        $this->post(route('user-management.store'), $this->newUser())->assertRedirect(route('login'));
    }

    // ---- creating accounts ---------------------------------------------------

    public function test_the_owner_creates_a_staff_account_that_can_log_in(): void
    {
        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['email' => '  Cashier@Example.com ']))
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'success');

        $user = User::where('email', 'cashier@example.com')->sole();
        $this->assertSame('Ate Cashier', $user->name);
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertNotSame($this->plain, $user->password, 'The password must be stored hashed.');

        // And it really works for the person it was made for.
        auth()->logout();
        $this->post(route('login'), ['email' => 'cashier@example.com', 'password' => $this->plain])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_owner_can_create_another_owner(): void
    {
        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['role' => 'super_admin']))
            ->assertSessionHas('swal.icon', 'success');

        $this->assertTrue(User::where('email', 'cashier@example.com')->sole()->isSuperAdmin());
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['email' => $this->staff->email]))
            ->assertSessionHasErrors('email', errorBag: 'createUser');

        $this->assertSame(2, User::count());
    }

    public function test_a_short_password_or_unknown_role_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['password' => Str::random(3)]))
            ->assertSessionHasErrors('password', errorBag: 'createUser');

        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['role' => 'janitor']))
            ->assertSessionHasErrors('role', errorBag: 'createUser');

        $this->assertSame(2, User::count());
    }

    // ---- resetting passwords --------------------------------------------------

    public function test_the_owner_can_reset_a_staff_password_and_signs_them_out(): void
    {
        $fresh = Str::random(20);

        DB::table('sessions')->insert([
            'id' => 'staff-phone-session',
            'user_id' => $this->staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phone',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($this->owner)
            ->post(route('user-management.password', $this->staff), ['password' => $fresh])
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'success');

        $this->assertTrue(Hash::check($fresh, $this->staff->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $this->staff->id)->count());
    }

    public function test_a_weak_reset_password_is_rejected_and_nothing_changes(): void
    {
        $before = $this->staff->password;

        $this->actingAs($this->owner)
            ->post(route('user-management.password', $this->staff), ['password' => Str::random(4)])
            ->assertSessionHasErrors('password', errorBag: 'resetPassword');

        $this->assertSame($before, $this->staff->fresh()->password);
    }

    public function test_the_owner_is_sent_to_their_profile_for_their_own_password(): void
    {
        $before = $this->owner->password;

        $this->actingAs($this->owner)
            ->post(route('user-management.password', $this->owner), ['password' => Str::random(20)])
            ->assertSessionHas('swal.icon', 'info');

        $this->assertSame($before, $this->owner->fresh()->password);
    }

    // ---- the list -------------------------------------------------------------

    public function test_the_list_can_be_searched_and_filtered_by_role(): void
    {
        $this->actingAs($this->owner)->get(route('user-management', ['search' => 'Papa']))
            ->assertViewHas('users', fn ($users) => $users->total() === 1
                && $users->first()->is($this->staff));

        $this->actingAs($this->owner)->get(route('user-management', ['role' => 'super_admin']))
            ->assertViewHas('users', fn ($users) => $users->total() === 1
                && $users->first()->is($this->owner));
    }

    public function test_the_list_is_paginated(): void
    {
        User::factory()->count(25)->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($this->owner)->get(route('user-management'))
            ->assertViewHas('users', fn ($users) => $users->total() === 27
                && $users->perPage() === 20
                && count($users->items()) === 20)
            ->assertSee('page=2');
    }

    // ---- the welcome email -------------------------------------------------------

    public function test_creating_an_account_emails_the_new_user_a_set_password_link(): void
    {
        Notification::fake();

        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['send_email' => '1']))
            ->assertSessionHas('swal.icon', 'success');

        $user = User::where('email', 'cashier@example.com')->sole();

        Notification::assertSentTo($user, AccountCreated::class);
        Notification::assertSentToTimes($user, AccountCreated::class, 1);
    }

    public function test_no_email_is_sent_unless_asked_for(): void
    {
        Notification::fake();

        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser())
            ->assertSessionHas('swal.icon', 'success');

        Notification::assertNothingSent();
    }

    public function test_the_email_has_a_working_link_and_never_the_password(): void
    {
        Notification::fake();

        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['send_email' => '1']));

        $user = User::where('email', 'cashier@example.com')->sole();
        $mail = null;

        Notification::assertSentTo($user, AccountCreated::class, function (AccountCreated $notification) use ($user, &$mail) {
            $mail = $notification->toMail($user);

            return true;
        });

        $html = (string) $mail->render();
        $this->assertStringContainsString('Hi Ate Cashier,', $html);
        $this->assertStringContainsString('Marites Owner made you an account', $html);
        $this->assertStringContainsString('Set your password', $html);
        $this->assertStringContainsString('reset-password/', $html);
        $this->assertStringContainsString('alt="'.config('app.name').'"', $html, 'The logo sits at the top.');
        $this->assertStringNotContainsString($this->plain, $html, 'The email must never contain the password.');
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);

        // The link really works: follow it and choose a password.
        $url = $mail->viewData['actionUrl'];
        $this->assertStringStartsWith('http', $url);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $chosen = Str::random(20);

        auth()->logout();
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'cashier@example.com',
            'password' => $chosen,
            'password_confirmation' => $chosen,
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($chosen, $user->fresh()->password));
    }

    /**
     * The reported bug: the owner makes the account, is still signed in on this
     * browser, clicks the emailed link — and got the dashboard, not the page.
     */
    public function test_the_set_password_link_opens_even_while_the_owner_is_signed_in(): void
    {
        $newbie = User::factory()->create(['email' => 'newbie@example.com', 'role' => User::ROLE_STAFF]);
        $token = Password::broker()->createToken($newbie);

        $this->actingAs($this->owner)
            ->get(route('password.reset', ['token' => $token, 'email' => 'newbie@example.com']))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('newbie@example.com');

        $this->assertGuest();
    }

    public function test_the_set_password_page_has_the_eye_the_strength_bar_and_a_confirm_field(): void
    {
        $this->get(route('password.reset', ['token' => 'tok', 'email' => 'newbie@example.com']))
            ->assertOk()
            ->assertSee('Set your password')
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('aria-label', false)          // the show/hide eye buttons
            ->assertSee('gtrackPasswordStrength', false) // the weak → strong bar
            ->assertSee('Passwords match');
    }

    public function test_setting_the_password_works_from_a_browser_the_owner_is_signed_in_on(): void
    {
        $newbie = User::factory()->create(['email' => 'newbie@example.com', 'role' => User::ROLE_STAFF]);
        $token = Password::broker()->createToken($newbie);
        $chosen = Str::random(20);

        $this->actingAs($this->owner)->post(route('password.store'), [
            'token' => $token,
            'email' => 'newbie@example.com',
            'password' => $chosen,
            'password_confirmation' => $chosen,
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($chosen, $newbie->fresh()->password));
        $this->assertGuest();
    }

    /**
     * The reported inconsistency: the forgot-password email came out in
     * Laravel's stock black-button template while the welcome email was
     * branded. Both must have the same look.
     */
    public function test_the_forgot_password_email_has_the_same_branded_look(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => $this->staff->email])->assertSessionHasNoErrors();

        $mail = null;
        Notification::assertSentTo($this->staff, ResetPassword::class, function ($notification) use (&$mail) {
            $mail = $notification->toMail($this->staff);

            return true;
        });

        $html = (string) $mail->render();

        // Same branded frame as the welcome email.
        $this->assertStringContainsString('Hi Papa Staff,', $html);
        $this->assertStringContainsString('alt="'.config('app.name').'"', $html);
        $this->assertStringContainsString('background:#1F5AE0', $html, 'The button is the app blue, not Laravel black.');
        $this->assertStringContainsString('Reset password', $html);
        $this->assertStringContainsString('reset-password/', $html);

        // None of the stock template's furniture.
        $this->assertStringNotContainsString('Hello!', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringNotContainsString('having trouble clicking', $html);

        // And its link is the real one: it sets the password.
        $url = $mail->viewData['actionUrl'];
        $token = basename(parse_url($url, PHP_URL_PATH));
        $chosen = Str::random(20);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $this->staff->email,
            'password' => $chosen,
            'password_confirmation' => $chosen,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check($chosen, $this->staff->fresh()->password));
    }

    public function test_both_emails_share_one_layout(): void
    {
        $welcome = (new AccountCreated('tok', 'Marites Owner'))->toMail($this->staff);
        $reset = (new ResetPassword('tok'))->toMail($this->staff);

        $this->assertSame($welcome->view, $reset->view, 'Both go through the same view pair.');
    }

    public function test_a_mail_failure_does_not_lose_the_account(): void
    {
        // A mail server that refuses the connection.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2,
        ]);

        $this->actingAs($this->owner)
            ->post(route('user-management.store'), $this->newUser(['send_email' => '1']))
            ->assertRedirect()
            ->assertSessionHas('swal.icon', 'warning');

        $user = User::where('email', 'cashier@example.com')->sole();
        $this->assertTrue(Hash::check($this->plain, $user->password), 'The typed password still works.');
    }

    public function test_a_failed_create_reopens_the_form_with_the_error(): void
    {
        $this->actingAs($this->owner)
            ->from(route('user-management'))
            ->post(route('user-management.store'), $this->newUser(['email' => $this->staff->email]))
            ->assertRedirect(route('user-management'));

        $this->actingAs($this->owner)->get(route('user-management'))
            ->assertOk()
            ->assertSee('has already been taken');
    }
}
