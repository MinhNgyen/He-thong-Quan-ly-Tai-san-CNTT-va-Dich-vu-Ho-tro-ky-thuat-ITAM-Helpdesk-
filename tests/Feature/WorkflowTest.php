<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Đăng ký, luồng trạng thái ticket, ràng buộc tài sản và giao diện tổng quan theo vai trò. */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(User $creator, ?User $assignee = null, string $status = 'open'): Ticket
    {
        $category = TicketCategory::firstOrCreate(['name' => 'Hardware'], ['description' => 'Hardware', 'is_active' => true]);

        return Ticket::create([
            'ticket_code' => 'HD-W-' . strtoupper(substr(md5((string) microtime(true) . random_int(0, 99999)), 0, 8)),
            'created_by' => $creator->id,
            'assigned_to' => $assignee?->id,
            'category_id' => $category->id,
            'title' => 'Phiếu kiểm thử',
            'description' => 'Mô tả',
            'priority' => 'medium',
            'status' => $status,
        ]);
    }

    // ---- Đăng ký ----

    public function test_register_page_lists_departments(): void
    {
        Department::create(['name' => 'Kế toán', 'description' => 'KT']);

        $this->get('/register')->assertOk()->assertSee('Kế toán');
    }

    public function test_user_can_register_as_employee(): void
    {
        Role::firstOrCreate(['name' => 'Employee'], ['description' => 'Nhan vien']);
        $department = Department::create(['name' => 'Kế toán', 'description' => 'KT']);

        $this->post('/register', [
            'employee_code' => 'NV900',
            'name' => 'Nguyễn Văn A',
            'email' => 'a@example.com',
            'department_id' => $department->id,
            'password' => 'MatKhau123!',
            'password_confirmation' => 'MatKhau123!',
        ])->assertRedirect('/');

        $user = User::where('email', 'a@example.com')->firstOrFail();
        $this->assertSame('Employee', $user->role->name);
        $this->assertAuthenticatedAs($user);
    }

    // ---- Luồng trạng thái ----

    public function test_technician_cannot_skip_from_assigned_to_closed(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->ticket(User::factory()->create(), $tech, 'assigned');

        $this->actingAs($tech)->patch('/tickets/' . $ticket->id, ['status' => 'closed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $ticket->fresh()->status);
    }

    public function test_technician_can_walk_ticket_through_valid_flow(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->ticket(User::factory()->create(), $tech, 'assigned');

        foreach (['in_progress', 'resolved', 'closed'] as $status) {
            $this->actingAs($tech)->patch('/tickets/' . $ticket->id, ['status' => $status])->assertRedirect();
            $this->assertSame($status, $ticket->fresh()->status);
        }
    }

    public function test_closed_ticket_is_locked_for_update_and_comments(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = $this->ticket($admin, User::factory()->technician()->create(), 'closed');

        $this->actingAs($admin)->patch('/tickets/' . $ticket->id, ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($admin)->post('/tickets/' . $ticket->id . '/comments', ['content' => 'Thêm'])->assertForbidden();
    }

    public function test_assigned_status_requires_an_assignee(): void
    {
        $ticket = $this->ticket(User::factory()->create());

        $this->actingAs(User::factory()->admin()->create())
            ->patch('/tickets/' . $ticket->id, ['status' => 'assigned'])
            ->assertSessionHasErrors('assigned_to');
    }

    // ---- Tài sản gắn vào phiếu ----

    public function test_cannot_attach_retired_asset_to_ticket(): void
    {
        $category = TicketCategory::firstOrCreate(['name' => 'Hardware'], ['description' => 'Hardware', 'is_active' => true]);
        $assetCategory = AssetCategory::firstOrCreate(['name' => 'Laptop'], ['description' => 'Laptop']);
        $retired = Asset::create(['asset_code' => 'OLD-1', 'name' => 'Máy cũ', 'category_id' => $assetCategory->id, 'status' => 'retired']);

        $this->actingAs(User::factory()->create())->post('/tickets', [
            'category_id' => $category->id,
            'asset_id' => $retired->id,
            'title' => 'Máy hỏng',
            'description' => 'Không mở được',
            'priority' => 'low',
        ])->assertSessionHasErrors('asset_id');
    }

    // ---- Giao diện theo vai trò ----

    public function test_each_role_sees_its_own_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')
            ->assertOk()->assertSee('Bảng điều khiển quản trị');

        $this->actingAs(User::factory()->technician()->create())->get('/')
            ->assertOk()->assertSee('Bảng công việc của tôi');

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()->assertSee('Trung tâm hỗ trợ');
    }

    public function test_technician_dashboard_lists_claimable_tickets(): void
    {
        $open = $this->ticket(User::factory()->create());

        $this->actingAs(User::factory()->technician()->create())->get('/')
            ->assertOk()->assertSee($open->ticket_code)->assertSee('Nhận việc');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Chào mừng trở lại');
    }
}
