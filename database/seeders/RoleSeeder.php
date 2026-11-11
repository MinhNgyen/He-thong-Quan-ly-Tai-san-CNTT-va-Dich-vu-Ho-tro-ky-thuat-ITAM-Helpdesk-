<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Quan tri he thong']
        );

        Role::firstOrCreate(
            ['name' => 'Technician'],
            ['description' => 'Nhan vien ky thuat']
        );

        Role::firstOrCreate(
            ['name' => 'Employee'],
            ['description' => 'Nhan vien']
        );
    }
}