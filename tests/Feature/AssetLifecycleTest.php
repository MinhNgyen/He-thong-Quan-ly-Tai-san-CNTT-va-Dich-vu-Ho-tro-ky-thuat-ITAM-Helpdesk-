<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cây tài sản (truy vấn đệ quy), bàn giao / thu hồi, bảo trì. */
class AssetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function asset(string $code, ?Asset $parent = null, string $status = 'available'): Asset
    {
        $cat = AssetCategory::firstOrCreate(['name' => 'Server'], ['description' => 's']);

        return Asset::create(['asset_code' => $code, 'name' => "Tài sản {$code}", 'category_id' => $cat->id, 'status' => $status, 'parent_id' => $parent?->id]);
    }

    private function token(User $user): string
    {
        return $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');
    }

    public function test_recursive_query_returns_all_descendants_with_depth(): void
    {
        $root = $this->asset('SRV');
        $mid = $this->asset('SRV-ST', $root);
        $leaf1 = $this->asset('SRV-D1', $mid);
        $leaf2 = $this->asset('SRV-D2', $mid);
        $nic = $this->asset('SRV-NIC', $root);
        $other = $this->asset('OTHER'); // không thuộc cây
        $deep = $this->asset('SRV-D1-X', $leaf1);

        $admin = User::factory()->admin()->create();
        $data = $this->withToken($this->token($admin))->getJson("/api/v1/assets/{$root->id}/descendants")->assertOk()->json('data');

        $codes = collect($data)->pluck('asset_code')->all();
        $this->assertEqualsCanonicalizing(['SRV-ST', 'SRV-NIC', 'SRV-D1', 'SRV-D2', 'SRV-D1-X'], $codes);
        $this->assertNotContains('OTHER', $codes);
        $this->assertSame(3, collect($data)->firstWhere('asset_code', 'SRV-D1-X')['depth']);
        $this->assertSame(1, collect($data)->firstWhere('asset_code', 'SRV-NIC')['depth']);

        $leafResult = $this->withToken($this->token($admin))->getJson("/api/v1/assets/{$nic->id}/descendants")->assertOk()->json('data');
        $this->assertSame([], $leafResult);
    }

    public function test_descendants_endpoint_is_forbidden_for_employee(): void
    {
        $root = $this->asset('SRV');
        $this->withToken($this->token(User::factory()->create()))->getJson("/api/v1/assets/{$root->id}/descendants")->assertForbidden();
    }

    public function test_cannot_create_cycle_in_asset_tree(): void
    {
        $a = $this->asset('A');
        $b = $this->asset('B', $a);
        $c = $this->asset('C', $b);
        $admin = User::factory()->admin()->create();

        $payload = fn (Asset $asset, $parent) => [
            'asset_code' => $asset->asset_code, 'name' => $asset->name, 'category_id' => $asset->category_id, 'status' => 'available', 'parent_id' => $parent,
        ];

        // A làm con của C (cháu của A) => vòng lặp; A làm con của chính nó => vòng lặp
        $this->actingAs($admin)->put("/assets/{$a->id}", $payload($a, $c->id))->assertSessionHasErrors('parent_id');
        $this->actingAs($admin)->put("/assets/{$a->id}", $payload($a, $a->id))->assertSessionHasErrors('parent_id');
        $this->assertNull($a->fresh()->parent_id);

        // Chuyển C lên làm con trực tiếp của A là hợp lệ
        $this->actingAs($admin)->put("/assets/{$c->id}", $payload($c, $a->id))->assertRedirect();
        $this->assertSame($a->id, $c->fresh()->parent_id);
    }

    public function test_asset_with_children_cannot_be_deleted(): void
    {
        $parent = $this->asset('P');
        $this->asset('CH', $parent);

        $this->actingAs(User::factory()->admin()->create())->delete("/assets/{$parent->id}");

        $this->assertDatabaseHas('assets', ['id' => $parent->id]);
    }

    public function test_assign_and_return_asset(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create();
        $asset = $this->asset('LT1');

        $this->actingAs($admin)->post("/assets/{$asset->id}/assign", ['user_id' => $employee->id, 'note' => 'Cấp mới'])->assertRedirect();

        $this->assertSame('in_use', $asset->fresh()->status);
        $this->assertDatabaseHas('asset_assignments', ['asset_id' => $asset->id, 'user_id' => $employee->id, 'assigned_by' => $admin->id, 'returned_at' => null]);

        // Không thể bàn giao lần hai khi đang được sử dụng
        $other = User::factory()->create();
        $this->actingAs($admin)->post("/assets/{$asset->id}/assign", ['user_id' => $other->id])->assertSessionHasErrors('asset');
        $this->assertDatabaseCount('asset_assignments', 1);

        $this->actingAs($admin)->post("/assets/{$asset->id}/return", ['note' => 'Trả máy'])->assertRedirect();
        $this->assertSame('available', $asset->fresh()->status);
        $this->assertDatabaseMissing('asset_assignments', ['asset_id' => $asset->id, 'returned_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'asset.assigned']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'asset.returned']);
    }

    public function test_only_admin_can_assign_but_technician_can_view_asset(): void
    {
        $asset = $this->asset('LT2');
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create();

        $this->actingAs($tech)->post("/assets/{$asset->id}/assign", ['user_id' => $employee->id])->assertForbidden();
        $this->actingAs($employee)->get("/assets/{$asset->id}")->assertForbidden();
        $this->actingAs($tech)->get("/assets/{$asset->id}")->assertOk()->assertSee('LT2');
    }

    public function test_maintenance_flow_changes_asset_status_and_restores_it(): void
    {
        $tech = User::factory()->technician()->create();
        $asset = $this->asset('LT3');

        $this->actingAs($tech)->post("/assets/{$asset->id}/maintenances", [
            'title' => 'Thay quạt tản nhiệt', 'maintenance_date' => now()->toDateString(), 'cost' => 300000,
        ])->assertRedirect();

        $this->assertSame('maintenance', $asset->fresh()->status);
        $maintenance = \App\Models\Maintenance::firstOrFail();
        $this->assertSame('in_progress', $maintenance->status);

        // Tài sản đang bảo trì không thể lập thêm bảo trì
        $this->actingAs($tech)->post("/assets/{$asset->id}/maintenances", ['title' => 'Khác', 'maintenance_date' => now()->toDateString()])->assertSessionHasErrors('asset');

        $this->actingAs($tech)->patch("/assets/maintenances/{$maintenance->id}", ['outcome' => 'completed', 'cost' => 350000])->assertRedirect();

        $this->assertSame('available', $asset->fresh()->status);
        $this->assertSame('completed', $maintenance->fresh()->status);
        $this->assertEquals(350000, (float) $maintenance->fresh()->cost);

        // Đợt đã kết thúc không thể kết thúc lần nữa
        $this->actingAs($tech)->patch("/assets/maintenances/{$maintenance->id}", ['outcome' => 'cancelled'])->assertSessionHasErrors('status');
    }

    public function test_cannot_schedule_maintenance_for_asset_in_use(): void
    {
        $tech = User::factory()->technician()->create();
        $asset = $this->asset('LT4', null, 'in_use');

        $this->actingAs($tech)->post("/assets/{$asset->id}/maintenances", ['title' => 'X', 'maintenance_date' => now()->toDateString()])->assertSessionHasErrors('asset');
        $this->assertSame('in_use', $asset->fresh()->status);
    }
}
