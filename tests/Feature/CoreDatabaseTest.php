<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\DailyCapacity;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkshopSetting;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CoreDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji helper Setting::get() dengan caching dan casting tipe data.
     */
    public function test_setting_helper_retrieves_cached_values_with_proper_types(): void
    {
        WorkshopSetting::create([
            'key' => 'daily_quota',
            'value' => '15',
            'type' => 'integer',
        ]);

        // Uji pemanggilan pertama (membaca dari database dan menyimpan ke cache)
        $quota = Setting::get('daily_quota');
        $this->assertIsInt($quota);
        $this->assertEquals(15, $quota);
        $this->assertTrue(Cache::has('workshop_setting_daily_quota'));

        // Ubah langsung di database tanpa lewat helper
        WorkshopSetting::where('key', 'daily_quota')->update(['value' => '20']);

        // Nilai tetap 15 karena ter-cache
        $this->assertEquals(15, Setting::get('daily_quota'));

        // Ubah lewat Setting::set(), cache otomatis dibersihkan
        Setting::set('daily_quota', 25, 'integer');
        $this->assertEquals(25, Setting::get('daily_quota'));
    }

    /**
     * Uji penghitungan kuota efektif di DailyCapacity.
     */
    public function test_daily_capacity_calculates_effective_quota_and_detects_holidays(): void
    {
        Setting::set('daily_quota', 10, 'integer');

        $tomorrow = Carbon::tomorrow();
        $nextWeek = Carbon::today()->addDays(7);

        // Override kuota esok hari menjadi 14
        DailyCapacity::create([
            'date' => $tomorrow->toDateString(),
            'quota' => 14,
            'is_open' => true,
        ]);

        // Set libur minggu depan
        DailyCapacity::create([
            'date' => $nextWeek->toDateString(),
            'quota' => null,
            'is_open' => false,
            'note' => 'Hari Libur Nasional',
        ]);

        $this->assertEquals(14, DailyCapacity::getEffectiveQuota($tomorrow));
        $this->assertTrue(DailyCapacity::isOpenOn($tomorrow));

        $this->assertEquals(0, DailyCapacity::getEffectiveQuota($nextWeek));
        $this->assertFalse(DailyCapacity::isOpenOn($nextWeek));

        // Tanggal tanpa catatan di DailyCapacity menggunakan default Setting
        $futureDate = Carbon::today()->addDays(3);
        $this->assertEquals(10, DailyCapacity::getEffectiveQuota($futureDate));
        $this->assertTrue(DailyCapacity::isOpenOn($futureDate));
    }

    /**
     * Uji constraint unik parsial: Nomor urut antrean aktif tidak boleh ganda di tanggal yang sama.
     */
    public function test_duplicate_active_queue_order_on_same_date_violates_unique_constraint(): void
    {
        $user = User::factory()->create(['role' => Role::Customer]);
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 1234 ABC',
            'brand' => 'Honda',
            'model' => 'Beat',
            'year' => 2022,
        ]);
        $service = Service::create([
            'name' => 'Ganti Oli',
            'duration_minutes' => 20,
            'price_estimate' => 50000,
        ]);

        $date = Carbon::today()->toDateString();

        // Buat booking aktif antrean 1
        Booking::create([
            'booking_code' => 'AM-TEST-001',
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'queue_order' => 1,
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Confirmed,
        ]);

        // Mencoba membuat booking aktif lain dengan tanggal dan antrean 1 yang sama harus gagal
        $this->expectException(QueryException::class);

        Booking::create([
            'booking_code' => 'AM-TEST-002',
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'queue_order' => 1, // Duplikat antrean aktif
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Pending,
        ]);
    }

    /**
     * Uji bahwa booking yang dibatalkan (Cancelled) membebaskan slot nomor antrean.
     */
    public function test_cancelled_booking_does_not_block_queue_order(): void
    {
        $user = User::factory()->create(['role' => Role::Customer]);
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'plate_number' => 'B 9999 XYZ',
            'brand' => 'Yamaha',
            'model' => 'NMAX',
            'year' => 2023,
        ]);
        $service = Service::create([
            'name' => 'Tune Up',
            'duration_minutes' => 45,
            'price_estimate' => 75000,
        ]);

        $date = Carbon::today()->toDateString();

        // 1. Buat booking pertama dengan status Cancelled
        $b1 = Booking::create([
            'booking_code' => 'AM-TEST-CANCEL-01',
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'queue_order' => 1,
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Cancelled,
            'cancel_reason' => 'Batal hadir',
        ]);

        // 2. Buat booking kedua dengan status Cancelled di antrean yang sama (keduanya NULL pada active_queue_order)
        $b2 = Booking::create([
            'booking_code' => 'AM-TEST-CANCEL-02',
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'queue_order' => 1,
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Cancelled,
            'cancel_reason' => 'Batal hadir juga',
        ]);

        // 3. Buat booking baru yang AKTIF (Confirmed) menggunakan nomor antrean 1 yang sama
        $b3 = Booking::create([
            'booking_code' => 'AM-TEST-ACTIVE-01',
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'queue_order' => 1, // Mengambil kembali nomor antrean 1
            'estimated_arrival_at' => '08:30:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $this->assertDatabaseHas('bookings', ['id' => $b1->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('bookings', ['id' => $b2->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('bookings', ['id' => $b3->id, 'status' => 'confirmed']);
    }

    /**
     * Uji transisi status dan pencatatan audit log otomatis ke booking_status_logs.
     */
    public function test_booking_transition_records_status_logs_and_updates_timestamps(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $customer = User::factory()->create(['role' => Role::Customer]);
        $vehicle = Vehicle::create([
            'user_id' => $customer->id,
            'plate_number' => 'B 5555 KL',
            'brand' => 'Honda',
            'model' => 'Scoopy',
            'year' => 2022,
        ]);
        $service = Service::create([
            'name' => 'CVT Service',
            'duration_minutes' => 60,
            'price_estimate' => 95000,
        ]);

        $booking = Booking::create([
            'booking_code' => 'AM-TRANSITION-001',
            'user_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $service->id,
            'booking_date' => Carbon::today()->toDateString(),
            'queue_order' => 1,
            'estimated_arrival_at' => '09:00:00',
            'status' => BookingStatus::Pending,
        ]);

        // Transisi ke Confirmed
        $booking->transitionTo(BookingStatus::Confirmed, $admin->id, 'Dikonfirmasi admin');
        $this->assertEquals(BookingStatus::Confirmed, $booking->status);

        // Transisi ke CheckedIn
        $booking->transitionTo(BookingStatus::CheckedIn, $admin->id, 'Pelanggan tiba di bengkel');
        $this->assertNotNull($booking->checked_in_at);

        // Transisi ke InProgress
        $booking->transitionTo(BookingStatus::InProgress, $admin->id, 'Mekanik mulai servis');
        $this->assertNotNull($booking->started_at);

        // Transisi ke Completed
        $booking->transitionTo(BookingStatus::Completed, $admin->id, 'Servis selesai');
        $this->assertNotNull($booking->finished_at);

        // Verifikasi log tercatat 4 kali
        $this->assertCount(4, $booking->statusLogs);
    }
}
