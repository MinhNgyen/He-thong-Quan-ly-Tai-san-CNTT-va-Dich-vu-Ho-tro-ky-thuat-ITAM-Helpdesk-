<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\AssetCategory;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $laptop = AssetCategory::where('name', 'Laptop')->firstOrFail();
        $desktop = AssetCategory::where('name', 'Desktop')->firstOrFail();
        $monitor = AssetCategory::where('name', 'Monitor')->firstOrFail();
        $printer = AssetCategory::where('name', 'Printer')->firstOrFail();
        $network = AssetCategory::where('name', 'Network Device')->firstOrFail();

        Asset::firstOrCreate(
            ['asset_code' => 'TS001'],
            [
                'name' => 'Dell Latitude 5440',
                'category_id' => $laptop->id,
                'serial_number' => 'DELL-LAP-001',
                'brand' => 'Dell',
                'model' => 'Latitude 5440',
                'purchase_date' => '2025-01-15',
                'purchase_price' => 18000000,
                'status' => 'available',
                'description' => 'Laptop phuc vu cong viec',
            ]
        );

        Asset::firstOrCreate(
            ['asset_code' => 'TS002'],
            [
                'name' => 'HP ProDesk 400',
                'category_id' => $desktop->id,
                'serial_number' => 'HP-PC-002',
                'brand' => 'HP',
                'model' => 'ProDesk 400',
                'purchase_date' => '2025-02-10',
                'purchase_price' => 15000000,
                'status' => 'available',
                'description' => 'May tinh de ban van phong',
            ]
        );

        Asset::firstOrCreate(
            ['asset_code' => 'TS003'],
            [
                'name' => 'Dell P2422H',
                'category_id' => $monitor->id,
                'serial_number' => 'DELL-MON-003',
                'brand' => 'Dell',
                'model' => 'P2422H',
                'purchase_date' => '2025-03-05',
                'purchase_price' => 4500000,
                'status' => 'available',
                'description' => 'Man hinh may tinh',
            ]
        );

        Asset::firstOrCreate(
            ['asset_code' => 'TS004'],
            [
                'name' => 'HP LaserJet Pro',
                'category_id' => $printer->id,
                'serial_number' => 'HP-PRINT-004',
                'brand' => 'HP',
                'model' => 'LaserJet Pro',
                'purchase_date' => '2025-04-12',
                'purchase_price' => 6500000,
                'status' => 'available',
                'description' => 'May in tai phong hanh chinh',
            ]
        );

        Asset::firstOrCreate(
            ['asset_code' => 'TS005'],
            [
                'name' => 'TP-Link Router',
                'category_id' => $network->id,
                'serial_number' => 'TPL-ROUTER-005',
                'brand' => 'TP-Link',
                'model' => 'Archer',
                'purchase_date' => '2025-05-20',
                'purchase_price' => 1200000,
                'status' => 'available',
                'description' => 'Thiet bi mang noi bo',
            ]
        );
    }
}
