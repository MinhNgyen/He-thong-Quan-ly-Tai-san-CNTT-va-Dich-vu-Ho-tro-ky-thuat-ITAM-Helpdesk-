<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API phiếu hỗ trợ: Bearer token, phạm vi dữ liệu và quyền giống giao diện web. */
class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(): TicketCategory
    {
        return TicketCategory::firstOrCreate(['name' => 'Hardware'], ['description' => 'Hardware', 'is_active' => true]);
    }

    private function ticket(User $creator, ?User $assignee = null, string $status = 'open'): Ticket
    {
        return Ticket::create([
            'ticket_code' => 'HD-A-' . strtoupper(substr(md5((string) microtime(true) . random_int(0, 99999)), 0, 8)),
            'created_by' => $creator->id,
            'assigned_to' => $assignee?->id,
            'category_id' => $this->category()->id,
            'title' => 'Phiếu API',
            'description' => 'Mô tả',
            'priority' => 'medium',
            'status' => $status,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');
    }

    public function test_requires_token(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
        $this->postJson('/api/v1/tickets', [])->assertUnauthorized();
    }

    public function test_options_returns_categories_and_priorities(): void
    {
        $this->category();
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->getJson('/api/v1/tickets/options')
            ->assertOk()
            ->assertJsonPath('data.categories.0.name', 'Hardware')
            ->assertJsonCount(4, 'data.priorities');
    }

    public function test_employee_can_create_ticket(): void
    {
        $employee = User::factory()->create();
        $token = $this->tokenFor($employee);

        $this->withToken($token)->postJson('/api/v1/tickets', [
            'category_id' => $this->category()->id,
            'title' => 'Laptop không khởi động',
            'description' => 'Máy không lên nguồn sau khi cắm sạc.',
            'priority' => 'high',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.created_by.id', $employee->id);

        $this->assertDatabaseHas('tickets', ['created_by' => $employee->id, 'title' => 'Laptop không khởi động']);
    }

    public function test_create_ticket_validation_errors_are_json(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->postJson('/api/v1/tickets', ['title' => 'Thiếu dữ liệu'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'description', 'priority']);
    }

    public function test_employee_only_sees_own_tickets(): void
    {
        $me = User::factory()->create();
        $mine = $this->ticket($me);
        $this->ticket(User::factory()->create());

        $response = $this->withToken($this->tokenFor($me))->getJson('/api/v1/tickets')->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_employee_cannot_view_another_employees_ticket(): void
    {
        $other = $this->ticket(User::factory()->create());

        $this->withToken($this->tokenFor(User::factory()->create()))
            ->getJson('/api/v1/tickets/' . $other->id)->assertForbidden();
    }

    public function test_show_includes_comments_and_history(): void
    {
        $employee = User::factory()->create();
        $ticket = $this->ticket($employee);
        $token = $this->tokenFor($employee);

        $this->withToken($token)->postJson('/api/v1/tickets/' . $ticket->id . '/comments', ['content' => 'Bổ sung thông tin'])
            ->assertCreated();

        $this->withToken($token)->getJson('/api/v1/tickets/' . $ticket->id)
            ->assertOk()
            ->assertJsonPath('data.comments.0.content', 'Bổ sung thông tin')
            ->assertJsonPath('data.history.0.action', 'commented');
    }

    public function test_technician_can_claim_then_walk_status_flow(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->ticket(User::factory()->create());
        $token = $this->tokenFor($tech);

        $this->withToken($token)->postJson('/api/v1/tickets/' . $ticket->id . '/claim')
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.assigned_to.id', $tech->id);

        $this->withToken($token)->patchJson('/api/v1/tickets/' . $ticket->id, ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->withToken($token)->patchJson('/api/v1/tickets/' . $ticket->id, ['status' => 'closed'])
            ->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_employee_cannot_claim_or_update_ticket(): void
    {
        $employee = User::factory()->create();
        $ticket = $this->ticket($employee);
        $token = $this->tokenFor($employee);

        $this->withToken($token)->postJson('/api/v1/tickets/' . $ticket->id . '/claim')->assertForbidden();
        $this->withToken($token)->patchJson('/api/v1/tickets/' . $ticket->id, ['status' => 'closed'])->assertForbidden();
    }

    public function test_admin_can_assign_ticket_to_technician(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->ticket(User::factory()->create());

        $this->withToken($this->tokenFor(User::factory()->admin()->create()))
            ->patchJson('/api/v1/tickets/' . $ticket->id, ['status' => 'assigned', 'assigned_to' => $tech->id])
            ->assertOk()
            ->assertJsonPath('data.assigned_to.id', $tech->id);
    }

    public function test_closed_ticket_rejects_comments(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->ticket($admin, User::factory()->technician()->create(), 'closed');

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/v1/tickets/' . $ticket->id . '/comments', ['content' => 'Thêm'])
            ->assertForbidden();
    }
}
