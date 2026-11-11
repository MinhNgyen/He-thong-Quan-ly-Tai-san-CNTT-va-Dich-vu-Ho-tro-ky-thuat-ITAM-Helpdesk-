<?php

namespace Tests\Feature;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Đồng hồ SLA theo giờ làm việc: hạn, tạm dừng khi chờ người dùng, tiếp tục, leo thang. */
class SlaTest extends TestCase
{
    use RefreshDatabase;

    private const TZ = 'Asia/Ho_Chi_Minh';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $local): void
    {
        Carbon::setTestNow(Carbon::parse($local, self::TZ));
    }

    private function localDue(Ticket $ticket): string
    {
        return $ticket->fresh()->sla_due_at->copy()->timezone(self::TZ)->format('Y-m-d H:i');
    }

    private function createTicket(User $creator, string $priority): Ticket
    {
        $category = TicketCategory::firstOrCreate(['name' => 'Hardware'], ['description' => 'HW', 'is_active' => true]);

        $this->actingAs($creator)->post('/tickets', [
            'category_id' => $category->id,
            'title' => 'Máy in hỏng',
            'description' => 'Máy in không in được.',
            'priority' => $priority,
        ])->assertRedirect();

        return Ticket::latest('id')->firstOrFail();
    }

    public function test_ticket_created_friday_17h_with_4_business_hours_is_due_monday_noon(): void
    {
        $this->at('2026-11-06 17:00'); // thứ Sáu
        $ticket = $this->createTicket(User::factory()->create(), 'high'); // mặc định 240 phút làm việc

        $this->assertSame('2026-11-09 12:00', $this->localDue($ticket));
    }

    public function test_sla_policy_table_overrides_default_minutes(): void
    {
        SlaPolicy::create(['priority' => 'high', 'name' => 'Cao', 'resolution_minutes' => 60]);

        $this->at('2026-11-09 09:00');
        $ticket = $this->createTicket(User::factory()->create(), 'high');

        $this->assertSame('2026-11-09 10:00', $this->localDue($ticket));
    }

    public function test_clock_pauses_while_waiting_for_user_and_resumes_on_reply(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();

        $this->at('2026-11-09 08:00'); // thứ Hai, mức trung bình = 480 phút (8 giờ) => hạn 16:00
        $ticket = $this->createTicket($employee, 'medium');
        $this->assertSame('2026-11-09 16:00', $this->localDue($ticket));

        $this->at('2026-11-09 09:00');
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'assigned', 'assigned_to' => $tech->id])->assertRedirect();
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'in_progress', 'assigned_to' => $tech->id])->assertRedirect();

        // 10:00: chuyển sang chờ người dùng => đồng hồ dừng, còn 360 phút làm việc (10:00-16:00)
        $this->at('2026-11-09 10:00');
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'waiting_user', 'assigned_to' => $tech->id])->assertRedirect();

        $paused = $ticket->fresh();
        $this->assertNull($paused->sla_due_at);
        $this->assertNotNull($paused->sla_paused_at);
        $this->assertSame(360, $paused->sla_remaining_minutes);

        // Chờ qua đêm (không bị tính). Thứ Ba 14:00 người dùng phản hồi => chạy tiếp 360 phút
        $this->at('2026-11-10 14:00');
        $this->actingAs($employee)->post("/tickets/{$ticket->id}/comments", ['content' => 'Đã thử lại, vẫn lỗi.'])->assertRedirect();

        $resumed = $ticket->fresh();
        $this->assertSame('in_progress', $resumed->status);
        $this->assertNull($resumed->sla_paused_at);
        // 14:00-17:00 thứ Ba = 180 phút, còn 180 phút => thứ Tư 08:00-11:00
        $this->assertSame('2026-11-11 11:00', $this->localDue($ticket));
    }

    public function test_overdue_ticket_is_escalated_exactly_once(): void
    {
        $admin = User::factory()->admin()->create();
        $this->at('2026-11-09 08:00');
        $ticket = $this->createTicket(User::factory()->create(), 'urgent'); // 120 phút => hạn 10:00

        $this->at('2026-11-09 09:00');
        $this->artisan('sla:escalate')->expectsOutputToContain('Đã leo thang 0')->assertSuccessful();

        $this->at('2026-11-09 11:00'); // đã quá hạn
        $this->artisan('sla:escalate')->expectsOutputToContain('Đã leo thang 1')->assertSuccessful();
        $this->artisan('sla:escalate')->expectsOutputToContain('Đã leo thang 0')->assertSuccessful();

        $this->assertDatabaseCount('escalations', 1);
        $this->assertNotNull($ticket->fresh()->sla_escalated_at);
        $this->assertDatabaseHas('ticket_histories', ['ticket_id' => $ticket->id, 'action' => 'sla_escalated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.sla_escalated', 'entity_id' => $ticket->id]);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_paused_ticket_is_never_escalated(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();

        $this->at('2026-11-09 08:00');
        $ticket = $this->createTicket($employee, 'urgent');
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'assigned', 'assigned_to' => $tech->id]);
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'in_progress', 'assigned_to' => $tech->id]);
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'waiting_user', 'assigned_to' => $tech->id]);

        $this->at('2026-11-12 09:00');
        $this->artisan('sla:escalate')->expectsOutputToContain('Đã leo thang 0')->assertSuccessful();
    }

    public function test_sla_state_is_met_when_resolved_before_due_and_breached_after(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();
        $service = app(\App\Services\SlaService::class);

        $this->at('2026-11-09 08:00');
        $ticket = $this->createTicket($employee, 'high'); // hạn 12:00
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'assigned', 'assigned_to' => $tech->id]);
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'in_progress', 'assigned_to' => $tech->id]);

        $this->at('2026-11-09 11:00');
        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'resolved', 'assigned_to' => $tech->id]);
        $this->assertSame('met', $service->state($ticket->fresh())['state']);

        $late = $this->createTicket($employee, 'high'); // tạo 11:00 => hạn 15:00
        $this->actingAs($admin)->patch("/tickets/{$late->id}", ['status' => 'assigned', 'assigned_to' => $tech->id]);
        $this->actingAs($admin)->patch("/tickets/{$late->id}", ['status' => 'in_progress', 'assigned_to' => $tech->id]);
        $this->at('2026-11-10 10:00');
        $this->actingAs($admin)->patch("/tickets/{$late->id}", ['status' => 'resolved', 'assigned_to' => $tech->id]);
        $this->assertSame('breached', $service->state($late->fresh())['state']);
    }

    public function test_api_exposes_sla_block(): void
    {
        $this->at('2026-11-09 08:00');
        $employee = User::factory()->create();
        $ticket = $this->createTicket($employee, 'medium');

        $token = $this->postJson('/api/v1/login', ['email' => $employee->email, 'password' => 'password'])->json('data.token');

        $this->withToken($token)->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.sla.state', 'running')
            ->assertJsonStructure(['data' => ['sla' => ['state', 'due_at', 'remaining_business_minutes']]]);
    }

    public function test_internal_cron_endpoint_requires_secret_key_and_escalates(): void
    {
        $admin = User::factory()->admin()->create();
        $this->at('2026-11-09 08:00');
        $ticket = $this->createTicket(User::factory()->create(), 'urgent'); // hạn 10:00
        $this->at('2026-11-09 12:00');

        // Chưa cấu hình khóa: điểm cuối không tồn tại
        config(['sla.cron_key' => null]);
        $this->postJson('/api/v1/internal/sla-escalate')->assertNotFound();

        config(['sla.cron_key' => 'khoa-bi-mat-dai-ngau-nhien']);
        $this->postJson('/api/v1/internal/sla-escalate')->assertForbidden();
        $this->postJson('/api/v1/internal/sla-escalate', [], ['X-Cron-Key' => 'sai'])->assertForbidden();
        $this->assertDatabaseCount('escalations', 0);

        $this->postJson('/api/v1/internal/sla-escalate', [], ['X-Cron-Key' => 'khoa-bi-mat-dai-ngau-nhien'])
            ->assertOk()->assertJsonPath('data.escalated', 1);
        $this->postJson('/api/v1/internal/sla-escalate', [], ['X-Cron-Key' => 'khoa-bi-mat-dai-ngau-nhien'])
            ->assertOk()->assertJsonPath('data.escalated', 0);

        $this->assertDatabaseCount('escalations', 1);
    }
}

