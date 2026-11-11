<?php
namespace Database\Seeders;
    
use Illuminate\Database\Seeder;
use App\Models\AssetCategory;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        AssetCategory::firstOrCreate(
            ['name' => 'Laptop'],
            ['description' => 'May tinh xach tay']
        );

        AssetCategory::firstOrCreate(
            ['name' => 'Desktop'],
            ['description' => 'May tinh de ban']
        );

        AssetCategory::firstOrCreate(
            ['name' => 'Monitor'],
            ['description' => 'Man hinh may tinh']
        );

        AssetCategory::firstOrCreate(
            ['name' => 'Printer'],
            ['description' => 'May in']
        );

        AssetCategory::firstOrCreate(
            ['name' => 'Network Device'],
            ['description' => 'Thiet bi mang']
        );
    }
}
