<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class AlexJohnUserSeeder extends Seeder
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

        // Find the 'AlexJohn' user by username
        $user = User::where('username', 'AlexJohn')->first();

        // Attach the 'Admin' role to the user if both exist
        if ($adminRole && $user) {
            // Use syncWithoutDetaching to avoid removing other roles the user might have
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}