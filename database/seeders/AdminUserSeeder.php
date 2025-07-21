<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Find the 'Admin' role
        $adminRole = Role::where('name', 'Admin')->first();

        // Create the admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@compliancehub.com'],
            [
                'username' => 'admin',
                'password' => 'password', // Default password, should be changed
                'is_verified' => 1,
            ]
        );

        // Attach the 'Admin' role to the user
        if ($adminRole && $adminUser) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}