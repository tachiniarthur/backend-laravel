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
        User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'phone' => '1234567890',
            'email' => 'admin@example.com',
            'password' => bcrypt('123456'),
            'is_admin' => true,
        ]);
    }
}
