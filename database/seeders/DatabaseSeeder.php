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
            FrameworkSeeder::class,
            FrameworkControlSeeder::class, // Seeds PCI DSS v4.0, ISO 27001:2022, BB ICT, SWIFT CSCF 2026 controls
            EvaluationCorpusSeeder::class, // Seeds the 60-item Chapter 6/7 evaluation corpus
            AdminUserSeeder::class,
            AuditorCustomerSeeder::class,
            PciDssRequirementSeeder::class, // Add the new seeder here
            AlexJohnUserSeeder::class,
            ComplianceTestTemplateSeeder::class,
            DepartmentSeeder::class,
        ]);
    }
}
