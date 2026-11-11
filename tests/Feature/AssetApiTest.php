<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API tài sản: xác thực bằng Bearer token (stateless) + phân quyền theo vai trò. */
class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    private function category(): AssetCategory
    {
        return AssetCategory::firstOrCreate(['name' => 'Laptop'], ['description' => 'Laptop']);
    }

    private function asset(string $code = 'LT-001'): Asset
    {
        return Asset::create([
            'asset_code' => $code,
            'name' => 'Laptop test',
            'category_id' => $this->category()->id,
            'status' => 'available',
        ]);
    }

    /** Đăng nhập qua API và trả về token (mật khẩu mặc định của UserFactory là "password"). */
    private function tokenFor(User $user): string
    {
        return $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->json('data.token');
    }

    public function test_login_returns_bearer_token_and_user_info(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/v1/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.role', 'Admin');

        $this->assertDatabaseCount('api_tokens', 1);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/v1/login', ['email' => $admin->email, 'password' => 'sai-mat-khau'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_inactive_user_cannot_login_via_api(): void
    {
        $user = User::factory()->admin()->create(['is_active' => false]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422);
    }

    public function test_request_without_token_is_unauthorized(): void
    {
        $this->getJson('/api/v1/assets')->assertUnauthorized();
    }

    public function test_request_with_invalid_token_is_unauthorized(): void
    {
        $this->withToken('khong-phai-token')->getJson('/api/v1/assets')->assertUnauthorized();
    }

    public function test_session_cookie_alone_no_longer_authenticates_api(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/assets')->assertUnauthorized();
    }

    public function test_employee_cannot_list_assets(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->withToken($token)->getJson('/api/v1/assets')->assertForbidden();
    }

    public function test_technician_can_list_and_show_but_not_create_assets(): void
    {
        $asset = $this->asset();
        $token = $this->tokenFor(User::factory()->technician()->create());

        $this->withToken($token)->getJson('/api/v1/assets')->assertOk()->assertJsonPath('data.0.asset_code', 'LT-001');
        $this->withToken($token)->getJson('/api/v1/assets/' . $asset->id)->assertOk()->assertJsonPath('data.id', $asset->id);
        $this->withToken($token)->postJson('/api/v1/assets', [])->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_asset(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $created = $this->withToken($token)->postJson('/api/v1/assets', [
            'asset_code' => 'lt-777',
            'name' => 'Laptop Dell',
            'category_id' => $this->category()->id,
            'status' => 'available',
        ])->assertCreated()->assertJsonPath('data.asset_code', 'LT-777');

        $id = $created->json('data.id');

        $this->withToken($token)->putJson('/api/v1/assets/' . $id, [
            'asset_code' => 'LT-777',
            'name' => 'Laptop Dell XPS',
            'category_id' => $this->category()->id,
            'status' => 'in_use',
        ])->assertOk()->assertJsonPath('data.status', 'in_use');

        $this->withToken($token)->deleteJson('/api/v1/assets/' . $id)->assertOk();
        $this->assertDatabaseMissing('assets', ['id' => $id]);
    }

    public function test_admin_gets_validation_errors_as_json(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)->postJson('/api/v1/assets', ['name' => 'Thiếu mã'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['asset_code', 'category_id', 'status']);
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->tokenFor(User::factory()->admin()->create());

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        $this->assertDatabaseCount('api_tokens', 0);
        $this->withToken($token)->getJson('/api/v1/assets')->assertUnauthorized();
    }
}
