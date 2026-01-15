<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\EmployeeRole;

class EmployeeSetupSeeder extends Seeder
{
    public function run(): void
    {
        // Departments (edit these anytime)
        $departments = [
            'Sales',
            'Accounting',
            'Estimating',
            'Warehouse',
            'Management',
            'Install',
            'Admin',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name], ['is_active' => true]);
        }

        // Roles (your list)
        $roles = [
            'Admin',
            'Sales',
            'Accounting',
            'Estimator',
            'Warehouse',
            'Manager',
            'Other',
        ];

        foreach ($roles as $name) {
            EmployeeRole::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
