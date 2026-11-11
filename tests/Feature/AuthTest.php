<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_route_exists(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
