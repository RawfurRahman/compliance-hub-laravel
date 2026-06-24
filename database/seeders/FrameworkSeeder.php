<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Framework;

class FrameworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Framework::firstOrCreate(
            ['slug' => 'pci_dss'],
            [
                'name' => 'PCI DSS',
                'version' => 'v4.0.1',
                'description' => 'Payment Card Industry Data Security Standard',
                'is_active' => true,
            ]
        );

        Framework::firstOrCreate(
            ['slug' => 'iso_27001'],
            [
                'name' => 'ISO 27001',
                'version' => '2022',
                'description' => 'Information Security Management Systems',
                'is_active' => true,
            ]
        );

        Framework::firstOrCreate(
            ['slug' => 'swift_csp'],
            [
                'name' => 'SWIFT CSP',
                'version' => '2024',
                'description' => 'Customer Security Programme',
                'is_active' => true,
            ]
        );

        Framework::firstOrCreate(
            ['slug' => 'vapt'],
            [
                'name' => 'VAPT',
                'version' => '',
                'description' => 'Vulnerability Assessment and Penetration Testing',
                'is_active' => true,
            ]
        );

        Framework::firstOrCreate(
            ['slug' => 'hitrust'],
            [
                'name' => 'HITRUST CSF',
                'version' => 'v 11.8.0',
                'description' => 'Health Information Trust Alliance Common Security Framework',
                'is_active' => true,
            ]
        );

        Framework::firstOrCreate(
            ['slug' => 'soc2'],
            [
                'name' => 'SOC 2',
                'version' => 'Type II',
                'description' => 'System and Organization Controls 2',
                'is_active' => true,
            ]
        );
    }
}
