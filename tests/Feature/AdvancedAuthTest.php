<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji registrasi berhasil dengan nomor seluler Indonesia dan normalisasi otomatis.
     */
    public function test_user_can_register_with_indonesian_phone_and_whatsapp_opt_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Bambang Sudirman',
            'email' => 'bambang@example.com',
            'phone' => '081234567899',
            'whatsapp_opt_in' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/beranda');

        $user = User::where('email', 'bambang@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('6281234567899', $user->phone);
        $this->assertEquals(Role::Customer, $user->role);
        $this->assertTrue($user->whatsapp_opt_in);
        $this->assertEquals(0, $user->no_show_count);
        $this->assertNull($user->blocked_until);
    }

    /**
     * Uji bahwa role selalu dipaksa menjadi customer dan tidak dapat dimanipulasi dari request.
     */
    public function test_registration_strictly_forces_customer_role_even_if_role_passed(): void
    {
        $response = $this->post('/register', [
            'name' => 'Hacker Wannabe',
            'email' => 'hacker@example.com',
            'phone' => '081299998888',
            'role' => 'admin', // Coba injeksi role admin
            'whatsapp_opt_in' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertEquals(Role::Customer, $user->role);
        $this->assertNotEquals(Role::Admin, $user->role);
    }

    /**
     * Uji penolakan nomor telepon yang tidak valid saat registrasi.
     */
    public function test_registration_fails_with_invalid_phone(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalidphone@example.com',
            'phone' => '0217654321', // Nomor rumah PSTN, bukan seluler
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertGuest();
    }

    /**
     * Uji admin berhasil mengakses panel /admin.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'phone' => '6281234567890',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    /**
     * Uji pelanggan ditolak dengan 403 saat mencoba masuk ke rute admin.
     */
    public function test_customer_cannot_access_admin_dashboard_receives_403(): void
    {
        $customer = User::factory()->create([
            'role' => Role::Customer,
            'phone' => '6281234567891',
            'aktif' => true,
        ]);

        $response = $this->actingAs($customer)->get('/admin');
        $response->assertStatus(403);
    }

    /**
     * Uji admin ditolak dengan 403 saat mencoba masuk ke rute pelanggan (/beranda).
     */
    public function test_admin_cannot_access_customer_dashboard_receives_403(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'phone' => '6281234567890',
            'aktif' => true,
        ]);

        $response = $this->actingAs($admin)->get('/beranda');
        $response->assertStatus(403);
    }

    /**
     * Uji pelanggan berhasil mengakses /beranda.
     */
    public function test_customer_can_access_customer_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => Role::Customer,
            'phone' => '6281234567891',
            'aktif' => true,
        ]);

        $response = $this->actingAs($customer)->get('/beranda');
        $response->assertStatus(200);
    }

    /**
     * Uji akun terblokir ditolak saat login dengan pesan tanggal pemblokiran.
     */
    public function test_blocked_user_cannot_login_and_receives_blocked_message_with_date(): void
    {
        $blockedUntil = Carbon::now()->addDays(3)->setTime(15, 30);

        $user = User::factory()->create([
            'email' => 'terblokir@example.com',
            'password' => bcrypt('password'),
            'role' => Role::Customer,
            'phone' => '6281234567892',
            'blocked_until' => $blockedUntil,
            'aktif' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'terblokir@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);

        $errors = session('errors')->get('email');
        $this->assertStringContainsString('diblokir', $errors[0]);
    }

    /**
     * Uji akun terblokir yang memiliki sesi aktif langsung dikeluarkan dan ditolak.
     */
    public function test_blocked_user_cannot_access_routes_and_is_logged_out(): void
    {
        $blockedUser = User::factory()->create([
            'role' => Role::Customer,
            'phone' => '6281234567893',
            'blocked_until' => Carbon::now()->addDays(2),
            'aktif' => true,
        ]);

        $response = $this->actingAs($blockedUser)->get('/beranda');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Uji pengguna dapat memperbarui nomor WhatsApp dan persetujuan notifikasi di halaman profil.
     */
    public function test_user_can_update_profile_phone_and_whatsapp_opt_in(): void
    {
        $user = User::factory()->create([
            'role' => Role::Customer,
            'phone' => '6281234567894',
            'whatsapp_opt_in' => true,
            'aktif' => true,
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru Pelanggan',
            'email' => $user->email,
            'phone' => '085712345678', // Format 0857 harus dinormalisasi
            'whatsapp_opt_in' => false,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $user->refresh();
        $this->assertEquals('Nama Baru Pelanggan', $user->name);
        $this->assertEquals('6285712345678', $user->phone);
        $this->assertFalse($user->whatsapp_opt_in);
    }
}
