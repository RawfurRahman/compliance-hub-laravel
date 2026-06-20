<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the essential roles for the application
        // firstOrCreate prevents duplicate roles from being created
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Auditor']);
        Role::firstOrCreate(['name' => 'Customer']);
    }
}
