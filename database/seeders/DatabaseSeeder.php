<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            AssetCategorySeeder::class,
            TicketCategorySeeder::class,
            SlaPolicySeeder::class,
            HolidaySeeder::class,
            UserSeeder::class,
            AssetSeeder::class,
            AssetTreeSeeder::class,
            TicketSeeder::class,
            TicketCommentSeeder::class,
            TicketHistorySeeder::class,
            SlaBackfillSeeder::class,
        ]);
    }
}
