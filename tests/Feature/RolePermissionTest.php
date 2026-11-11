<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiem thu ma tran phan quyen Admin / Technician / Employee.
 * Moi test "Khong" la mot ca kiem chung BM4 / BM5 (mong doi 403).
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(User $creator, ?User $assignee = null, string $status = 'open'): Ticket
    {
        $category = TicketCategory::firstOrCreate(['name' => 'Hardware'], ['description' => 'Hardware', 'is_active' => true]);

        return Ticket::create([
            'ticket_code' => 'HD-T-' . strtoupper(substr(md5((string) microtime(true) . random_int(0, 99999)), 0, 8)),
            'created_by' => $creator->id,
            'assigned_to' => $assignee?->id,
            'category_id' => $category->id,
            'title' => 'Test ticket',
            'description' => 'Mo ta',
            'priority' => 'medium',
            'status' => $status,
        ]);
    }

    private function makeAsset(): Asset
    {
        $cat = AssetCategory::firstOrCreate(['name' => 'Laptop'], ['description' => 'Laptop']);

        return Asset::create([
            'asset_code' => 'LT-' . random_int(1000, 9999),
            'name' => 'Laptop test',
            'category_id' => $cat->id,
            'status' => 'available',
        ]);
    }

    // ---- Tai san: chi Admin duoc xoa ----

    public function test_technician_cannot_delete_asset(): void
    {
        $asset = $this->makeAsset();
        $this->actingAs(User::factory()->technician()->create())
            ->delete('/assets/' . $asset->id)->assertForbidden();
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    public function test_employee_cannot_delete_asset(): void
    {
        $asset = $this->makeAsset();
        $this->actingAs(User::factory()->create())
            ->delete('/assets/' . $asset->id)->assertForbidden();
    }

    public function test_admin_can_delete_asset(): void
    {
        $asset = $this->makeAsset();
        $this->actingAs(User::factory()->admin()->create())
            ->delete('/assets/' . $asset->id)->assertRedirect();
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_technician_can_still_open_asset_list(): void
    {
        $this->actingAs(User::factory()->technician()->create())
            ->get('/assets')->assertOk();
    }

    // ---- Pham vi xem ticket ----

    public function test_technician_cannot_view_ticket_assigned_to_another_technician(): void
    {
        $other = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $other, 'assigned');

        $this->actingAs(User::factory()->technician()->create())
            ->get('/tickets/' . $ticket->id)->assertForbidden();
    }

    public function test_technician_can_view_own_assigned_and_unassigned_tickets(): void
    {
        $tech = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $mine = $this->makeTicket($employee, $tech, 'assigned');
        $unassigned = $this->makeTicket($employee);

        $this->actingAs($tech)->get('/tickets/' . $mine->id)->assertOk();
        $this->actingAs($tech)->get('/tickets/' . $unassigned->id)->assertOk();
    }

    public function test_technician_ticket_list_hides_tickets_of_other_technicians(): void
    {
        $tech = User::factory()->technician()->create();
        $other = User::factory()->technician()->create();
        $employee = User::factory()->create();
        $mine = $this->makeTicket($employee, $tech, 'assigned');
        $theirs = $this->makeTicket($employee, $other, 'assigned');

        $response = $this->actingAs($tech)->get('/tickets');
        $response->assertOk()->assertSee($mine->ticket_code)->assertDontSee($theirs->ticket_code);
    }

    public function test_admin_sees_all_tickets(): void
    {
        $employee = User::factory()->create();
        $a = $this->makeTicket($employee, User::factory()->technician()->create(), 'assigned');
        $b = $this->makeTicket($employee, User::factory()->technician()->create(), 'assigned');

        $this->actingAs(User::factory()->admin()->create())->get('/tickets')
            ->assertOk()->assertSee($a->ticket_code)->assertSee($b->ticket_code);
    }

    public function test_technician_cannot_comment_on_ticket_outside_scope(): void
    {
        $ticket = $this->makeTicket(User::factory()->create(), User::factory()->technician()->create(), 'assigned');

        $this->actingAs(User::factory()->technician()->create())
            ->post('/tickets/' . $ticket->id . '/comments', ['content' => 'xin chao'])->assertForbidden();
    }

    // ---- Doi trang thai / phan cong ----

    public function test_technician_cannot_update_ticket_assigned_to_another(): void
    {
        $other = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $other, 'assigned');

        $this->actingAs(User::factory()->technician()->create())
            ->patch('/tickets/' . $ticket->id, ['status' => 'closed'])->assertForbidden();
        $this->assertSame('assigned', $ticket->fresh()->status);
    }

    public function test_technician_can_update_status_of_own_ticket(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $tech, 'assigned');

        $this->actingAs($tech)->patch('/tickets/' . $ticket->id, ['status' => 'in_progress'])->assertRedirect();
        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_technician_cannot_reassign_own_ticket_to_someone_else(): void
    {
        $tech = User::factory()->technician()->create();
        $other = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $tech, 'assigned');

        $this->actingAs($tech)->patch('/tickets/' . $ticket->id, [
            'status' => 'in_progress',
            'assigned_to' => $other->id,
        ]);

        $this->assertSame($tech->id, (int) $ticket->fresh()->assigned_to);   // assigned_to bi bo qua
    }

    public function test_admin_can_assign_ticket_to_technician(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create());

        $this->actingAs(User::factory()->admin()->create())->patch('/tickets/' . $ticket->id, [
            'status' => 'assigned',
            'assigned_to' => $tech->id,
        ])->assertRedirect();

        $this->assertSame($tech->id, (int) $ticket->fresh()->assigned_to);
        $this->assertDatabaseHas('ticket_histories', ['ticket_id' => $ticket->id, 'action' => 'assigned_or_reassigned']);
    }

    public function test_employee_cannot_update_ticket(): void
    {
        $employee = User::factory()->create();
        $ticket = $this->makeTicket($employee);

        $this->actingAs($employee)->patch('/tickets/' . $ticket->id, ['status' => 'closed'])->assertForbidden();
    }

    // ---- Tu nhan viec ----

    public function test_technician_can_claim_open_unassigned_ticket(): void
    {
        $tech = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create());

        $this->actingAs($tech)->post('/tickets/' . $ticket->id . '/claim')->assertRedirect();

        $fresh = $ticket->fresh();
        $this->assertSame($tech->id, (int) $fresh->assigned_to);
        $this->assertSame('assigned', $fresh->status);
        $this->assertDatabaseHas('ticket_histories', ['ticket_id' => $ticket->id, 'action' => 'claimed']);
    }

    public function test_technician_cannot_claim_ticket_already_assigned(): void
    {
        $owner = User::factory()->technician()->create();
        $ticket = $this->makeTicket(User::factory()->create(), $owner, 'assigned');

        $this->actingAs(User::factory()->technician()->create())
            ->post('/tickets/' . $ticket->id . '/claim')->assertForbidden();
        $this->assertSame($owner->id, (int) $ticket->fresh()->assigned_to);
    }

    public function test_employee_cannot_claim_ticket(): void
    {
        $employee = User::factory()->create();
        $ticket = $this->makeTicket($employee);

        $this->actingAs($employee)->post('/tickets/' . $ticket->id . '/claim')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $ticket = $this->makeTicket(User::factory()->create());

        $this->post('/tickets/' . $ticket->id . '/claim')->assertRedirect('/login');
    }

    // ---- Tach quyen Admin / Technician ----

    public function test_technician_cannot_open_asset_create_form_or_store_asset(): void
    {
        $tech = User::factory()->technician()->create();

        $this->actingAs($tech)->get('/assets/create')->assertForbidden();
        $this->actingAs($tech)->post('/assets', ['name' => 'Laptop moi'])->assertForbidden();
    }

    public function test_technician_cannot_edit_asset(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs(User::factory()->technician()->create())
            ->get('/assets/' . $asset->id . '/edit')->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())
            ->put('/assets/' . $asset->id, ['name' => 'Doi ten'])->assertForbidden();
    }

    public function test_admin_cannot_assign_ticket_to_another_admin(): void
    {
        $ticket = $this->makeTicket(User::factory()->create());
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs(User::factory()->admin()->create())->patch('/tickets/' . $ticket->id, [
            'status' => 'assigned',
            'assigned_to' => $otherAdmin->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->assertNull($ticket->fresh()->assigned_to);
    }
}
