<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Asset;
use App\Models\TicketCategory;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::where('email', 'employee@example.com')->firstOrFail();
        $technician = User::where('email', 'technician@example.com')->firstOrFail();

        $hardware = TicketCategory::where('name', 'Hardware')->firstOrFail();
        $software = TicketCategory::where('name', 'Software')->firstOrFail();
        $network = TicketCategory::where('name', 'Network')->firstOrFail();
        $account = TicketCategory::where('name', 'Account')->firstOrFail();
        $equipment = TicketCategory::where('name', 'Equipment Request')->firstOrFail();

        $laptop = Asset::where('asset_code', 'TS001')->firstOrFail();
        $desktop = Asset::where('asset_code', 'TS002')->firstOrFail();
        $monitor = Asset::where('asset_code', 'TS003')->firstOrFail();

        Ticket::firstOrCreate(
            ['ticket_code' => 'YC001'],
            [
                'created_by' => $employee->id,
                'assigned_to' => $technician->id,
                'category_id' => $hardware->id,
                'asset_id' => $laptop->id,
                'title' => 'Laptop khong khoi dong',
                'description' => 'Laptop bam nut nguon nhung khong khoi dong.',
                'priority' => 'high',
                'status' => 'assigned',
            ]
        );

        Ticket::firstOrCreate(
            ['ticket_code' => 'YC002'],
            [
                'created_by' => $employee->id,
                'assigned_to' => $technician->id,
                'category_id' => $software->id,
                'asset_id' => $desktop->id,
                'title' => 'Khong mo duoc phan mem',
                'description' => 'Phan mem lam viec gap loi khi khoi dong.',
                'priority' => 'medium',
                'status' => 'in_progress',
            ]
        );

        Ticket::firstOrCreate(
            ['ticket_code' => 'YC003'],
            [
                'created_by' => $employee->id,
                'assigned_to' => null,
                'category_id' => $network->id,
                'asset_id' => null,
                'title' => 'Mat ket noi Internet',
                'description' => 'May tinh khong truy cap duoc Internet.',
                'priority' => 'urgent',
                'status' => 'open',
            ]
        );

        Ticket::firstOrCreate(
            ['ticket_code' => 'YC004'],
            [
                'created_by' => $employee->id,
                'assigned_to' => $technician->id,
                'category_id' => $account->id,
                'asset_id' => null,
                'title' => 'Yeu cau khoi phuc tai khoan',
                'description' => 'Nhan vien can ho tro khoi phuc tai khoan.',
                'priority' => 'low',
                'status' => 'resolved',
                'resolved_at' => now(),
            ]
        );

        Ticket::firstOrCreate(
            ['ticket_code' => 'YC005'],
            [
                'created_by' => $employee->id,
                'assigned_to' => null,
                'category_id' => $equipment->id,
                'asset_id' => $monitor->id,
                'title' => 'Yeu cau kiem tra man hinh',
                'description' => 'Man hinh hien thi khong on dinh.',
                'priority' => 'medium',
                'status' => 'open',
            ]
        );
    }
}
