<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\DailyCapacity;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'phone' => '6281234567890',
            'aktif' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => Role::Customer,
            'phone' => '6281234567891',
            'aktif' => true,
        ]);
    }

    /**
     * Uji otorisasi: Pelanggan tidak boleh mengakses modul admin (403 Forbidden).
     */
    public function test_customer_cannot_access_any_admin_modules(): void
    {
        $this->actingAs($this->customer)->get('/admin/services')->assertStatus(403);
        $this->actingAs($this->customer)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($this->customer)->get('/admin/capacities')->assertStatus(403);
    }

    /**
     * Uji admin berhasil mengakses seluruh halaman modul admin.
     */
    public function test_admin_can_access_all_admin_modules(): void
    {
        $this->actingAs($this->admin)->get('/admin/services')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/settings')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/capacities')->assertStatus(200);
    }

    /**
     * Uji admin dapat membuat layanan servis baru.
     */
    public function test_admin_can_create_service(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/services', [
            'name' => 'Kuras Minyak Rem Racing DOT 5.1',
            'description' => 'Pengurasan total minyak rem dengan cairan berkinerja tinggi.',
            'duration_minutes' => 30,
            'price_estimate' => 60000,
            'reminder_interval_days' => 180,
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/services');
        $this->assertDatabaseHas('services', [
            'name' => 'Kuras Minyak Rem Racing DOT 5.1',
            'duration_minutes' => 30,
            'price_estimate' => 60000,
        ]);
    }

    /**
     * Uji admin dapat memperbarui dan mengubah status aktif/nonaktif layanan.
     */
    public function test_admin_can_update_and_toggle_service(): void
    {
        $service = Service::create([
            'name' => 'Layanan Uji Coba',
            'duration_minutes' => 40,
            'price_estimate' => 50000,
            'is_active' => true,
        ]);

        // 1. Update data layanan
        $this->actingAs($this->admin)->put("/admin/services/{$service->id}", [
            'name' => 'Layanan Uji Coba Diperbarui',
            'duration_minutes' => 50,
            'price_estimate' => 55000,
            'is_active' => true,
        ])->assertRedirect('/admin/services');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Layanan Uji Coba Diperbarui',
            'duration_minutes' => 50,
        ]);

        // 2. Toggle status aktif
        $this->actingAs($this->admin)->patch("/admin/services/{$service->id}/toggle");
        $this->assertFalse($service->fresh()->is_active);
    }

    /**
     * Uji penghapusan layanan: Soft delete jika ada booking, permanen jika belum ada booking.
     */
    public function test_service_is_soft_deleted_if_has_bookings_or_permanently_deleted(): void
    {
        $serviceWithoutBookings = Service::create([
            'name' => 'Layanan Tanpa Booking',
            'duration_minutes' => 30,
            'price_estimate' => 40000,
        ]);

        $serviceWithBookings = Service::create([
            'name' => 'Layanan Dengan Booking',
            'duration_minutes' => 30,
            'price_estimate' => 40000,
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $this->customer->id,
            'plate_number' => 'B 1111 TST',
            'brand' => 'Honda',
            'model' => 'Beat',
            'year' => 2022,
        ]);

        Booking::create([
            'booking_code' => 'AM-TST-SOFT-01',
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $serviceWithBookings->id,
            'booking_date' => Carbon::today()->toDateString(),
            'queue_order' => 1,
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Confirmed,
        ]);

        // 1. Hapus layanan tanpa booking (harus terhapus permanen)
        $this->actingAs($this->admin)->delete("/admin/services/{$serviceWithoutBookings->id}");
        $this->assertDatabaseMissing('services', ['id' => $serviceWithoutBookings->id]);

        // 2. Hapus layanan dengan booking (harus ter-soft delete)
        $this->actingAs($this->admin)->delete("/admin/services/{$serviceWithBookings->id}");
        $this->assertSoftDeleted('services', ['id' => $serviceWithBookings->id]);
    }

    /**
     * Uji pembaruan pengaturan bengkel dan pembersihan cache.
     */
    public function test_admin_can_update_workshop_settings_and_bust_cache(): void
    {
        $payload = [
            'workshop_name' => 'ALDI-MOTOR Grand Workshop',
            'workshop_phone' => '6289998887776',
            'workshop_address' => 'Kawasan Industri Otomotif Blok A No. 12',
            'daily_quota' => 25,
            'operating_hours_start' => '07:30',
            'operating_hours_end' => '17:30',
            'mechanics_count' => 6,
            'booking_window_days' => 21,
            'booking_cutoff_time' => '20:00',
            'cancellation_threshold_hours' => 3,
            'late_tolerance_minutes' => 20,
            'max_active_bookings_per_user' => 3,
            'max_waiting_list_slots' => 10,
            'no_show_threshold' => 2,
            'block_duration_days' => 14,
            'wa_template_booking_confirmed' => 'Konfirmasi {nama} {kode_booking}',
            'wa_template_service_completed' => 'Selesai {nama} {plat}',
            'wa_template_waiting_list_promoted' => 'Daftar tunggu {nama}',
            'wa_template_service_reminder' => 'Pengingat {nama}',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/settings', $payload);
        $response->assertRedirect('/admin/settings');

        $this->assertEquals(25, Setting::get('daily_quota'));
        $this->assertEquals('ALDI-MOTOR Grand Workshop', Setting::get('workshop_name'));
        $this->assertEquals(6, Setting::get('mechanics_count'));
    }

    /**
     * Uji override kuota kalender dan proteksi penutupan tanggal dengan booking aktif.
     */
    public function test_capacity_calendar_prevents_closing_date_with_active_bookings_unless_forced(): void
    {
        $targetDate = Carbon::today()->addDays(2)->toDateString();

        $service = Service::create([
            'name' => 'Tune Up',
            'duration_minutes' => 45,
            'price_estimate' => 75000,
        ]);
        $vehicle = Vehicle::create([
            'user_id' => $this->customer->id,
            'plate_number' => 'B 7777 ACT',
            'brand' => 'Yamaha',
            'model' => 'Aerox',
            'year' => 2023,
        ]);

        Booking::create([
            'booking_code' => 'AM-TST-CAP-01',
            'user_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $targetDate,
            'queue_order' => 1,
            'estimated_arrival_at' => '09:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        // 1. Coba tutup tanggal tanpa force_close -> GAGAL dengan pesan validasi
        $response = $this->actingAs($this->admin)->post('/admin/capacities', [
            'date' => $targetDate,
            'is_open' => false,
            'note' => 'Cuti mendadak',
            'force_close' => false,
        ]);

        $response->assertSessionHasErrors(['is_open']);

        // 2. Tutup tanggal dengan persetujuan eksplisit force_close: true -> BERHASIL
        $responseSuccess = $this->actingAs($this->admin)->post('/admin/capacities', [
            'date' => $targetDate,
            'is_open' => false,
            'note' => 'Penutupan darurat disetujui',
            'force_close' => true,
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $this->assertFalse(DailyCapacity::isOpenOn($targetDate));
    }
}
