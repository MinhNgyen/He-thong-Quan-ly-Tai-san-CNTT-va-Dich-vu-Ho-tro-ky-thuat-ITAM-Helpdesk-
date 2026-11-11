<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketCategory;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        TicketCategory::firstOrCreate(
            ['name' => 'Hardware'],
            [
                'description' => 'Su co phan cung may tinh va thiet bi',
                'is_active' => true,
            ]
        );

        TicketCategory::firstOrCreate(
            ['name' => 'Software'],
            [
                'description' => 'Loi phan mem va ung dung',
                'is_active' => true,
            ]
        );

        TicketCategory::firstOrCreate(
            ['name' => 'Network'],
            [
                'description' => 'Su co ket noi mang va Internet',
                'is_active' => true,
            ]
        );

        TicketCategory::firstOrCreate(
            ['name' => 'Account'],
            [
                'description' => 'Yeu cau cap va khoi phuc tai khoan',
                'is_active' => true,
            ]
        );

        TicketCategory::firstOrCreate(
            ['name' => 'Equipment Request'],
            [
                'description' => 'Yeu cau cap phat thiet bi CNTT',
                'is_active' => true,
            ]
        );
    }
}
