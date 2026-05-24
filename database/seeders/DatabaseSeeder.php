<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin Account
        User::factory()->create([
            'name'     => 'Admin CV. Tri Jaya',
            'email'    => 'tj3113trijaya@gmail.com',
            'phone'    => '085100279866',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        // Create sample user for testing
        User::factory()->create([
            'name'     => 'User1',
            'email'    => 'user1@trijaya.com',
            'phone'    => '081298765432',
            'password' => bcrypt('password'),
            'role'     => 'user',
        ]);
    }
}
