<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Run your app seeders
        $this->call([
            AccountTypeSeeder::class,
            PermissionsSeeder::class,
        ]);

        // Optional: create a test user (keep or remove)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
	
	$this->call([
    AccountTypeSeeder::class,
    PermissionsSeeder::class,
    RolesSeeder::class,
]);

}
