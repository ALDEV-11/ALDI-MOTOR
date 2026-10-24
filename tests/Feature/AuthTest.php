<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_users_can_register_as_customer_with_normalized_phone(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ahmad Pelanggan',
            'email' => 'ahmad@example.com',
            'phone' => '081298765432',
            'whatsapp_opt_in' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/beranda');

        // Pastikan role dipaksa menjadi customer dan phone berawalan 62
        $user = User::where('email', 'ahmad@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(Role::Customer, $user->role);
        $this->assertEquals('6281298765432', $user->phone);
        $this->assertTrue($user->aktif);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@aldimotor.com',
            'password' => bcrypt('password'),
            'role' => Role::Admin,
            'phone' => '6281234567890',
            'aktif' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@aldimotor.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@aldimotor.com',
            'password' => bcrypt('password'),
            'phone' => '6281234567891',
        ]);

        $this->post('/login', [
            'email' => 'user@aldimotor.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_user_accessing_dashboard_is_redirected_by_role(): void
    {
        $user = User::factory()->create([
            'role' => Role::Admin,
            'phone' => '6281234567890',
            'aktif' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect('/admin');
    }
}
