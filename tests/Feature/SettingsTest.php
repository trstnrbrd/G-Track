<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $plain;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plain = Str::random(20);
        $this->user = User::factory()->create([
            'name' => 'Papa Staff',
            'email' => 'papa@example.com',
            'role' => User::ROLE_STAFF,
            'password' => $this->plain,
        ]);
    }

    // ---- name and email ---------------------------------------------------------

    public function test_the_page_has_an_editable_profile_and_the_new_password_form(): void
    {
        $this->actingAs($this->user)->get(route('settings'))
            ->assertOk()
            ->assertSee('Profile details')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('gtrackPasswordStrength', false) // the weak → strong bar
            ->assertDontSee('View only')
            ->assertDontSee('future update');
    }

    public function test_a_user_can_change_their_own_name_and_email(): void
    {
        $this->actingAs($this->user)
            ->from(route('settings'))
            ->patch(route('settings.profile'), ['name' => '  Papa Reboredo ', 'email' => ' Papa.New@Example.com '])
            ->assertRedirect(route('settings'))
            ->assertSessionHas('status', 'profile-updated');

        $fresh = $this->user->fresh();
        $this->assertSame('Papa Reboredo', $fresh->name);
        $this->assertSame('papa.new@example.com', $fresh->email);
    }

    public function test_the_role_cannot_be_changed_from_here(): void
    {
        $this->actingAs($this->user)->patch(route('settings.profile'), [
            'name' => 'Papa Staff',
            'email' => 'papa@example.com',
            'role' => User::ROLE_OWNER,
        ]);

        $this->assertSame(User::ROLE_STAFF, $this->user->fresh()->role);
    }

    public function test_an_email_someone_else_has_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->user)
            ->patch(route('settings.profile'), ['name' => 'Papa Staff', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email', errorBag: 'updateProfile');

        $this->assertSame('papa@example.com', $this->user->fresh()->email);
    }

    public function test_keeping_your_own_email_is_fine_and_a_blank_name_is_not(): void
    {
        $this->actingAs($this->user)
            ->patch(route('settings.profile'), ['name' => 'New Name', 'email' => 'papa@example.com'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->patch(route('settings.profile'), ['name' => '', 'email' => 'papa@example.com'])
            ->assertSessionHasErrors('name', errorBag: 'updateProfile');

        $this->assertSame('New Name', $this->user->fresh()->name);
    }

    public function test_a_malformed_email_is_refused(): void
    {
        $this->actingAs($this->user)
            ->patch(route('settings.profile'), ['name' => 'Papa Staff', 'email' => 'not-an-email'])
            ->assertSessionHasErrors('email', errorBag: 'updateProfile');
    }

    // ---- other devices -------------------------------------------------------------

    private function otherSession(int $userId, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '192.168.100.50',
            'user_agent' => 'phone',
            'payload' => '',
            'last_activity' => time() - 300,
        ]);
    }

    public function test_it_signs_out_the_other_devices_but_not_this_one_or_anyone_elses(): void
    {
        config(['session.driver' => 'database']);

        $someoneElse = User::factory()->create();
        $this->otherSession($this->user->id, 'phone-left-logged-in');
        $this->otherSession($this->user->id, 'old-laptop');
        $this->otherSession($someoneElse->id, 'not-mine');

        $this->actingAs($this->user)
            ->post(route('settings.sign-out-others'), ['current_password' => $this->plain])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'signed-out-others');

        // The other two are gone; someone else's is untouched. (This test's own
        // session is also in the table now — that's the one that must stay.)
        $this->assertFalse(DB::table('sessions')->where('id', 'phone-left-logged-in')->exists());
        $this->assertFalse(DB::table('sessions')->where('id', 'old-laptop')->exists());
        $this->assertTrue(DB::table('sessions')->where('id', 'not-mine')->exists());
        $this->assertSame(
            1,
            DB::table('sessions')->where('user_id', $this->user->id)->count(),
            'Only the current device is left signed in.',
        );
    }

    public function test_it_needs_the_right_password(): void
    {
        config(['session.driver' => 'database']);
        $this->otherSession($this->user->id, 'phone-left-logged-in');

        $this->actingAs($this->user)
            ->post(route('settings.sign-out-others'), ['current_password' => Str::random(20)])
            ->assertSessionHasErrors('current_password', errorBag: 'signOutOthers');

        $this->assertTrue(DB::table('sessions')->where('id', 'phone-left-logged-in')->exists());
    }

    public function test_the_page_says_how_many_other_devices_are_signed_in(): void
    {
        config(['session.driver' => 'database']);
        $this->otherSession($this->user->id, 'phone-1');
        $this->otherSession($this->user->id, 'phone-2');

        $this->actingAs($this->user)->get(route('settings'))
            ->assertOk()
            ->assertSee('Signed in on 2 other devices');
    }

    public function test_guests_cannot_use_any_of_it(): void
    {
        $this->patch(route('settings.profile'), ['name' => 'x', 'email' => 'x@example.com'])->assertRedirect(route('login'));
        $this->post(route('settings.sign-out-others'), ['current_password' => 'x'])->assertRedirect(route('login'));
        $this->get(route('settings'))->assertRedirect(route('login'));

        $this->assertTrue(Hash::check($this->plain, $this->user->fresh()->password));
    }
}
