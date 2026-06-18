<?php

namespace Database\Seeders;

use App\Models\Balance;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Super admin (the store owner). Password is auto-hashed by the model cast.
        User::updateOrCreate(
            ['email' => 'admin@gtrack.com'],
            [
                'name' => 'Marites Reboredo',
                'password' => 'gtrack2026',
                'role' => 'super_admin',
            ]
        );

        // Ensure the single balances row exists.
        Balance::current();
    }
}
