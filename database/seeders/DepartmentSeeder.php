<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'IT Department',
            'Compliance Department',
            'Finance Department',
            'HR Department',
            'Operations',
            'Facilities Department',
            'Legal',
            'Executive',
            'IT Security Department',
            'PCI Compliance Team',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
