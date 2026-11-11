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

/** K1/K2/K4: quản trị người dùng (khóa/mở khóa, đổi vai trò) và CRUD danh mục - chỉ Admin. */
class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private function roleId(string $name): int
    {
        return Role::firstOrCreate(['name' => $name], ['description' => $name])->id;
    }

    public function test_non_admin_cannot_reach_admin_pages(): void
    {
        foreach ([User::factory()->create(), User::factory()->technician()->create()] as $user) {
            $this->actingAs($user)->get('/admin/users')->assertForbidden();
            $this->actingAs($user)->get('/admin/users/create')->assertForbidden();
            $this->actingAs($user)->get('/admin/catalog/departments')->assertForbidden();
            $this->actingAs($user)->post('/admin/catalog/departments', ['name' => 'X'])->assertForbidden();
        }
    }

    public function test_admin_creates_user_with_hashed_password_and_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $dept = Department::create(['name' => 'Kế toán', 'description' => 'KT']);

        $this->actingAs($admin)->post('/admin/users', [
            'employee_code' => 'NV900', 'name' => 'Người mới', 'email' => 'moi@example.com', 'role_id' => $this->roleId('Technician'),
            'department_id' => $dept->id, 'is_active' => 1, 'password' => 'MatKhau123',
        ])->assertRedirect('/admin/users');

        $user = User::where('email', 'moi@example.com')->firstOrFail();
        $this->assertNotSame('MatKhau123', $user->password);
        $this->assertTrue(password_verify('MatKhau123', $user->password));
        $this->assertTrue($user->isTechnician());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'entity_id' => $user->id]);
    }

    public function test_weak_password_and_duplicate_email_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();
        $base = ['employee_code' => 'NV901', 'name' => 'A', 'role_id' => $this->roleId('Employee')];

        $this->actingAs($admin)->post('/admin/users', $base + ['email' => 'a@example.com', 'password' => 'abcdefgh'])->assertSessionHasErrors('password');
        $this->actingAs($admin)->post('/admin/users', $base + ['email' => $existing->email, 'password' => 'MatKhau123'])->assertSessionHasErrors('email');
    }

    public function test_deactivating_user_blocks_login_and_revokes_api_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'khoa@example.com']);
        $token = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])->json('data.token');

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name, 'role_id' => $user->role_id, 'department_id' => $user->department_id, 'is_active' => 0,
        ])->assertRedirect('/admin/users');

        $this->assertFalse((bool) $user->fresh()->is_active);
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.locked', 'entity_id' => $user->id]);

        auth()->logout();
        $this->post('/login', ['email' => 'khoa@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Mở khóa lại
        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name, 'role_id' => $user->role_id, 'department_id' => $user->department_id, 'is_active' => 1,
        ]);
        $this->assertTrue((bool) $user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.unlocked', 'entity_id' => $user->id]);
    }

    public function test_role_change_is_logged_and_takes_effect(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name, 'role_id' => $this->roleId('Technician'), 'is_active' => 1,
        ])->assertRedirect();

        $this->assertTrue($user->fresh()->isTechnician());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed', 'entity_id' => $user->id]);
    }

    public function test_admin_cannot_demote_or_lock_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", ['name' => $admin->name, 'role_id' => $this->roleId('Employee'), 'is_active' => 1])
            ->assertSessionHasErrors('role_id');
        $this->actingAs($admin)->put("/admin/users/{$admin->id}", ['name' => $admin->name, 'role_id' => $admin->role_id, 'is_active' => 0])
            ->assertSessionHasErrors('role_id');

        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    public function test_email_cannot_be_changed_through_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->put("/admin/users/{$user->id}", ['name' => 'X', 'email' => 'hacker@example.com', 'role_id' => $user->role_id])
            ->assertSessionHasErrors('email');
    }

    public function test_user_list_search_and_role_filter(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Nguyễn Tìm Thấy']);
        User::factory()->technician()->create(['name' => 'Kỹ thuật viên Z']);

        $this->actingAs($admin)->get('/admin/users?q=Tìm Thấy')->assertOk()->assertSee('Nguyễn Tìm Thấy')->assertDontSee('Kỹ thuật viên Z');
        $this->actingAs($admin)->get('/admin/users?role=' . $this->roleId('Technician'))->assertOk()->assertSee('Kỹ thuật viên Z')->assertDontSee('Nguyễn Tìm Thấy');
    }

    public function test_catalog_crud_with_validation_and_in_use_protection(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/catalog/departments', ['name' => 'Phòng IT', 'description' => 'CNTT'])->assertRedirect('/admin/catalog/departments');
        $dept = Department::where('name', 'Phòng IT')->firstOrFail();

        // trùng tên / thiếu tên bị chặn ở máy chủ
        $this->actingAs($admin)->post('/admin/catalog/departments', ['name' => 'Phòng IT'])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/admin/catalog/departments', ['name' => ''])->assertSessionHasErrors('name');

        $this->actingAs($admin)->put("/admin/catalog/departments/{$dept->id}", ['name' => 'Phòng CNTT'])->assertRedirect();
        $this->assertSame('Phòng CNTT', $dept->fresh()->name);

        // đang có người dùng thuộc phòng => không xóa được
        $user = User::factory()->create(['department_id' => $dept->id]);
        $this->actingAs($admin)->delete("/admin/catalog/departments/{$dept->id}")->assertSessionHasErrors('item');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);

        $user->update(['department_id' => Department::firstOrCreate(['name' => 'General'], ['description' => 'g'])->id]);
        $this->actingAs($admin)->delete("/admin/catalog/departments/{$dept->id}")->assertRedirect();
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_ticket_category_flag_and_asset_category_in_use(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/catalog/ticket-categories', ['name' => 'Email', 'is_active' => 0])->assertRedirect();
        $this->assertFalse((bool) TicketCategory::where('name', 'Email')->firstOrFail()->is_active);

        $cat = AssetCategory::create(['name' => 'Switch', 'description' => 's']);
        Asset::create(['asset_code' => 'SW1', 'name' => 'Switch 1', 'category_id' => $cat->id, 'status' => 'available']);
        $this->actingAs($admin)->delete("/admin/catalog/asset-categories/{$cat->id}")->assertSessionHasErrors('item');

        $email = TicketCategory::where('name', 'Email')->firstOrFail();
        Ticket::create(['ticket_code' => 'HD-C1', 'created_by' => User::factory()->create()->id, 'category_id' => $email->id, 'title' => 't', 'description' => 'd', 'priority' => 'low', 'status' => 'open']);
        $this->actingAs($admin)->delete("/admin/catalog/ticket-categories/{$email->id}")->assertSessionHasErrors('item');

        $this->actingAs($admin)->get('/admin/catalog/khong-co')->assertNotFound();
    }
}
