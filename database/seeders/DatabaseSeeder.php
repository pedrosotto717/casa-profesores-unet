<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create initial administrator if environment variables are set
        $this->call(InitialAdminSeeder::class);

        // Seed base catalogs (order matters for foreign keys)
        $this->call(AreasSeeder::class);
        $this->call(ServicesSeeder::class);
        $this->call(AcademiesSeeder::class);
        $this->call(DocumentsSeeder::class);

        // Create test user for development
        // if (app()->environment('local', 'testing')) {
        //     User::factory()->create([
        //         'name' => 'Test User',
        //         'email' => 'test@example.com',
        //     ]);
        // }
    }
}
