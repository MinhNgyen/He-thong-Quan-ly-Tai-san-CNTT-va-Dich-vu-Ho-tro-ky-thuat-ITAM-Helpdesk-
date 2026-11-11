<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/** K8: nhật ký hệ thống ghi nhận hành vi nhạy cảm, chỉ ghi thêm, chỉ Admin tra cứu. */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_and_failed_logins_and_logout_are_logged(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'sai']);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password']);
        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_ticket_and_asset_actions_are_logged_with_actor_and_ip(): void
    {
        $employee = User::factory()->create();
        $category = TicketCategory::create(['name' => 'Net', 'description' => 'Net', 'is_active' => true]);

        $this->actingAs($employee)->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->post('/tickets', [
            'category_id' => $category->id, 'title' => 'Mất mạng', 'description' => 'Không vào được mạng.', 'priority' => 'low',
        ]);

        $log = AuditLog::where('action', 'ticket.created')->firstOrFail();
        $this->assertSame($employee->id, $log->user_id);
        $this->assertSame('203.0.113.9', $log->ip_address);
        $this->assertSame('Ticket', $log->entity_type);
    }

    public function test_password_is_never_written_to_metadata(): void
    {
        app(AuditService::class)->log('test.event', null, null, ['password' => 'bi-mat', 'token' => 'abc', 'note' => 'ok']);

        $log = AuditLog::where('action', 'test.event')->firstOrFail();
        $this->assertSame(['note' => 'ok'], $log->metadata);
    }

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::create(['action' => 'x']);

        try {
            $log->update(['action' => 'sửa']);
            $this->fail('Phải chặn sửa nhật ký.');
        } catch (LogicException) {
            $this->assertSame('x', $log->fresh()->action);
        }

        try {
            $log->delete();
            $this->fail('Phải chặn xóa nhật ký.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
        }
    }

    public function test_only_admin_can_view_audit_page_and_filters_work(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::create(['action' => 'auth.login', 'user_id' => $admin->id, 'ip_address' => '10.0.0.1']);
        AuditLog::create(['action' => 'asset.deleted', 'ip_address' => '10.0.0.2']);

        $this->actingAs(User::factory()->create())->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())->get('/admin/audit-logs')->assertForbidden();

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk()->assertSee('auth.login')->assertSee('asset.deleted');
        $this->actingAs($admin)->get('/admin/audit-logs?action=asset.deleted')->assertOk()->assertSee('10.0.0.2')->assertDontSee('10.0.0.1');
    }
}
