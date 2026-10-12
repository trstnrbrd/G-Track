<?php

namespace Database\Seeders;

use App\Models\Balance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run again: an existing owner account is left exactly as it is,
     * so re-seeding never resets a password someone has since changed.
     */
    public function run(): void
    {
        $this->createOwner();

        // Ensure the single balances row exists.
        Balance::current();
    }

    /**
     * The shop owner. The password comes from GTRACK_ADMIN_PASSWORD in .env;
     * if that isn't set, a random one is generated and shown once. No password
     * is written in the code — this repository is on GitHub.
     */
    private function createOwner(): void
    {
        $email = 'admin@gtrack.com';

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Owner {$email} already exists — password left unchanged.");

            return;
        }

        $configured = config('gtrack.admin_password');
        $password = filled($configured) ? $configured : Str::password(16, symbols: false);

        // Password is auto-hashed by the model cast.
        User::create([
            'name' => 'Marites Reboredo',
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_OWNER,
        ]);

        if (filled($configured)) {
            $this->command?->info("Owner {$email} created with the password from GTRACK_ADMIN_PASSWORD.");

            return;
        }

        $this->command?->warn("Owner {$email} created. Password (shown once, change it in Settings): {$password}");
    }
}
