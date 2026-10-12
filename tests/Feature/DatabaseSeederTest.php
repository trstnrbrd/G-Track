<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_configured_owner_password(): void
    {
        $chosen = Str::random(20);
        config(['gtrack.admin_password' => $chosen]);

        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'admin@gtrack.com')->sole();
        $this->assertTrue($owner->isSuperAdmin());
        $this->assertTrue(Hash::check($chosen, $owner->password));
        $this->assertSame(1, Balance::count());
    }

    public function test_without_one_it_generates_a_random_password_and_prints_it_once(): void
    {
        config(['gtrack.admin_password' => null]);

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);

        $owner = User::where('email', 'admin@gtrack.com')->sole();
        $printed = Artisan::output();

        $this->assertTrue($owner->isSuperAdmin());
        $this->assertFalse(Hash::check('gtrack2026', $owner->password), 'The old well-known password must not work.');
        $this->assertStringContainsString('shown once', $printed);

        // The printed password is the real one.
        preg_match('/: ([A-Za-z0-9]{16})\s*$/m', $printed, $m);
        $this->assertNotEmpty($m, 'The generated password should be printed.');
        $this->assertTrue(Hash::check($m[1], $owner->password));
    }

    public function test_reseeding_never_resets_an_existing_password(): void
    {
        config(['gtrack.admin_password' => Str::random(20)]);
        $this->seed(DatabaseSeeder::class);

        $changed = Str::random(20);
        User::where('email', 'admin@gtrack.com')->sole()->update(['password' => $changed]);

        config(['gtrack.admin_password' => Str::random(20)]);
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check($changed, User::where('email', 'admin@gtrack.com')->sole()->password));
        $this->assertSame(1, User::where('email', 'admin@gtrack.com')->count());
    }
}
