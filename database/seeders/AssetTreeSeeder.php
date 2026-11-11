<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

/** Cây tài sản mẫu nhiều cấp: máy chủ > thiết bị lưu trữ > ổ đĩa (minh họa truy vấn đệ quy). */
class AssetTreeSeeder extends Seeder
{
    public function run(): void
    {
        $network = AssetCategory::where('name', 'Network Device')->firstOrFail();

        $server = $this->node('SRV001', 'Máy chủ ứng dụng Rack 1', $network, null);
        $storage = $this->node('SRV001-ST1', 'Khay lưu trữ RAID 1', $network, $server);
        $this->node('SRV001-ST1-D1', 'Ổ đĩa SSD 1 TB (vị trí 1)', $network, $storage);
        $this->node('SRV001-ST1-D2', 'Ổ đĩa SSD 1 TB (vị trí 2)', $network, $storage);
        $this->node('SRV001-NIC', 'Card mạng 10 GbE', $network, $server);
    }

    private function node(string $code, string $name, AssetCategory $category, ?Asset $parent): Asset
    {
        return Asset::firstOrCreate(
            ['asset_code' => $code],
            ['name' => $name, 'category_id' => $category->id, 'status' => 'available', 'parent_id' => $parent?->id],
        );
    }
}
