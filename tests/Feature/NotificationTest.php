<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** K9: thông báo trong ứng dụng cho các sự kiện nghiệp vụ quan trọng. */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(User $owner): Ticket
    {
        $cat = TicketCategory::firstOrCreate(['name' => 'HW'], ['description' => 'HW', 'is_active' => true]);

        return Ticket::create([
            'ticket_code' => 'HD-N-' . random_int(1000, 9999), 'created_by' => $owner->id, 'category_id' => $cat->id,
            'title' => 'Máy chậm', 'description' => 'd', 'priority' => 'medium', 'status' => 'open',
        ]);
    }

    public function test_assignment_notifies_technician_and_status_change_notifies_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $ticket = $this->ticket($employee);

        $this->actingAs($admin)->patch("/tickets/{$ticket->id}", ['status' => 'assigned', 'assigned_to' => $tech->id])->assertRedirect();

        $this->assertSame(1, $tech->notifications()->count());
        $this->assertSame('assigned', $tech->notifications()->first()->data['type']);
        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count()); // người thao tác không tự nhận thông báo
    }

    public function test_comment_notifies_the_other_party_not_the_author(): void
    {
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $ticket = $this->ticket($employee);
        $ticket->update(['assigned_to' => $tech->id, 'status' => 'assigned']);

        $this->actingAs($tech)->post("/tickets/{$ticket->id}/comments", ['content' => 'Bạn khởi động lại giúp mình nhé.']);

        $this->assertSame(1, $employee->notifications()->count());
        $this->assertSame(0, $tech->notifications()->count());
    }

    public function test_user_can_read_only_own_notifications_and_is_redirected_to_ticket(): void
    {
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = $this->ticket($employee);
        $ticket->update(['assigned_to' => $tech->id, 'status' => 'assigned']);
        $this->actingAs($tech)->post("/tickets/{$ticket->id}/comments", ['content' => 'Xin chào']);

        $notification = $employee->notifications()->firstOrFail();

        $this->actingAs($stranger)->post("/notifications/{$notification->id}/read")->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($employee)->post("/notifications/{$notification->id}/read")->assertRedirect(route('tickets.show', $ticket));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_bell_shows_unread_count_and_mark_all_read(): void
    {
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $ticket = $this->ticket($employee);
        $ticket->update(['assigned_to' => $tech->id, 'status' => 'assigned']);
        $this->actingAs($tech)->post("/tickets/{$ticket->id}/comments", ['content' => 'a']);
        $this->actingAs($tech)->post("/tickets/{$ticket->id}/comments", ['content' => 'b']);

        $this->actingAs($employee)->get('/notifications')->assertOk()->assertSee('Bình luận mới');
        $this->actingAs($employee)->get('/tickets')->assertSee('2 chưa đọc');

        $this->actingAs($employee)->post('/notifications/read-all')->assertRedirect();
        $this->assertSame(0, $employee->unreadNotifications()->count());
    }

    public function test_guest_cannot_see_notifications(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }
}
