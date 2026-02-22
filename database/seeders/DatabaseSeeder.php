<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'demo@compasia.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('Password123!'),
            ]
        );

        $this->call(ProductSeeder::class);
    }
}
