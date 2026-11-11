<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::firstOrCreate(
            ['name' => 'IT'],
            ['description' => 'Phong cong nghe thong tin']
        );

        Department::firstOrCreate(
            ['name' => 'HR'],
            ['description' => 'Phong nhan su']
        );

        Department::firstOrCreate(
            ['name' => 'Accounting'],
            ['description' => 'Phong ke toan']
        );
    }
}
