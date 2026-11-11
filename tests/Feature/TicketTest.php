<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_a_ticket(): void
    {
        $employee = User::factory()->create();
        $category = TicketCategory::create(['name' => 'Hardware', 'description' => 'Hardware', 'is_active' => true]);

        $response = $this->actingAs($employee)->post('/tickets', [
            'category_id' => $category->id,
            'asset_id' => null,
            'title' => 'Laptop khong khoi dong',
            'description' => 'May khong bat nguon sau khi cam sac.',
            'priority' => 'high',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'created_by' => $employee->id,
            'category_id' => $category->id,
            'title' => 'Laptop khong khoi dong',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'user_id' => $employee->id,
            'action' => 'created',
            'new_status' => 'open',
        ]);
    }

    public function test_employee_cannot_view_another_employees_ticket(): void
    {
        $owner = User::factory()->create();
        $otherEmployee = User::factory()->create();
        $category = TicketCategory::create(['name' => 'Software', 'description' => 'Software', 'is_active' => true]);
        $ticket = Ticket::create([
            'ticket_code' => 'HD-TEST-0001',
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Private ticket',
            'description' => 'Ticket description',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->actingAs($otherEmployee)->get('/tickets/'.$ticket->id)->assertForbidden();
    }
}
