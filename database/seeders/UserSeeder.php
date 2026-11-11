<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->firstOrFail();
        $technicianRole = Role::where('name', 'Technician')->firstOrFail();
        $employeeRole = Role::where('name', 'Employee')->firstOrFail();

        $itDepartment = Department::where('name', 'IT')->firstOrFail();
        $hrDepartment = Department::where('name', 'HR')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'employee_code' => 'NV001',
                'name' => 'Admin IT',
                'phone' => '0900000001',
                'role_id' => $adminRole->id,
                'department_id' => $itDepartment->id,
                'is_active' => true,
                'password' => Hash::make('Password123!'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'technician@example.com'],
            [
                'employee_code' => 'NV002',
                'name' => 'Technician IT',
                'phone' => '0900000002',
                'role_id' => $technicianRole->id,
                'department_id' => $itDepartment->id,
                'is_active' => true,
                'password' => Hash::make('Password123!'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'employee@example.com'],
            [
                'employee_code' => 'NV003',
                'name' => 'Employee Demo',
                'phone' => '0900000003',
                'role_id' => $employeeRole->id,
                'department_id' => $hrDepartment->id,
                'is_active' => true,
                'password' => Hash::make('Password123!'),
            ]
        );
    }
}
