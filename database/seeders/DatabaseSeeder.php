<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the RoleSeeder to create the default roles
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            PciDssRequirementSeeder::class, // Add the new seeder here
            AlexJohnUserSeeder::class,
        ]);
    }
}
