<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class AuditorCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find roles
        $auditorRole = Role::where('name', 'Auditor')->first();
        $customerRole = Role::where('name', 'Customer')->first();

        // Create Auditor User
        $auditor = User::firstOrCreate(
            ['email' => 'auditor@compliancehub.com'],
            [
                'username' => 'auditor',
                'password' => 'password',
                'is_verified' => 1,
            ]
        );

        if ($auditorRole && $auditor) {
            $auditor->roles()->syncWithoutDetaching([$auditorRole->id]);
        }

        // Create Customer User
        $customer = User::firstOrCreate(
            ['email' => 'customer@compliancehub.com'],
            [
                'username' => 'customer',
                'password' => 'password',
                'is_verified' => 1,
            ]
        );

        if ($customerRole && $customer) {
            $customer->roles()->syncWithoutDetaching([$customerRole->id]);
        }

        // Create AlexJohn User (who will be assigned Admin role in AlexJohnUserSeeder)
        $alexJohn = User::firstOrCreate(
            ['email' => 'alexjohn@compliancehub.com'],
            [
                'username' => 'AlexJohn',
                'password' => 'password',
                'is_verified' => 1,
            ]
        );

        if ($auditorRole && $alexJohn) {
            $alexJohn->roles()->syncWithoutDetaching([$auditorRole->id]);
        }
    }
}
