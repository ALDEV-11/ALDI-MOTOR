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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private User $customerA;

    private User $customerB;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerA = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => Role::Customer,
            'phone' => '6281234567891',
            'aktif' => true,
        ]);

        $this->customerB = User::factory()->create([
            'name' => 'Siti Aminah',
            'role' => Role::Customer,
            'phone' => '6281234567892',
            'aktif' => true,
        ]);

        $this->service = Service::create([
            'name' => 'Servis Berkala & Ganti Oli',
            'description' => 'Servis tune up mesin, pembersihan CVT, dan oli mesin',
            'duration_minutes' => 45,
            'price_estimate' => 85000,
            'is_active' => true,
        ]);

        Setting::set('daily_quota', 10, 'integer');
        Setting::set('mechanics_count', 2, 'integer');
        Setting::set('operating_hours_start', '08:00', 'string');
        Setting::set('operating_hours_end', '17:00', 'string');
        Setting::set('cancellation_threshold_hours', 2, 'integer');
    }

    /**
     * 1. Halaman publik (tanpa login) menampilkan data slot per tanggal (?tanggal=YYYY-MM-DD),
     * dan booking yang dibatalkan (cancelled) TIDAK dihitung dalam pemakaian slot.
     */
    public function test_public_can_view_slots_per_date_and_cancelled_bookings_not_counted(): void
    {
        $targetDate = Carbon::today()->addDays(2)->toDateString();

        $vehicle = Vehicle::create([
            'user_id' => $this->customerA->id,
            'plate_number' => 'B 1111 TST',
            'brand' => 'Honda',
            'model' => 'Vario 160',
            'year' => 2022,
        ]);

        // Buat 1 booking aktif pada jam 09:00
        Booking::create([
            'booking_code' => 'AM-TST-09-01',
            'user_id' => $this->customerA->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $this->service->id,
            'booking_date' => $targetDate,
            'queue_order' => 1,
            'estimated_arrival_at' => '09:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        // Buat 1 booking DIBATALKAN pada jam 09:00 (seharusnya tidak mengurangi sisa kuota)
        Booking::create([
            'booking_code' => 'AM-TST-09-02',
            'user_id' => $this->customerB->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $this->service->id,
            'booking_date' => $targetDate,
            'queue_order' => 2,
            'estimated_arrival_at' => '09:00:00',
            'status' => BookingStatus::Cancelled,
        ]);

        // Akses tanpa login (tamu)
        $response = $this->get("/reservasi?tanggal={$targetDate}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reservations/Index')
            ->where('selected_date', $targetDate)
            ->where('is_open', true)
            ->has('slots')
            ->where('slots.1.time', '09:00') // slot ke-2 (08:00, 09:00)
            ->where('slots.1.booked', 1)     // Hanya 1 booking aktif yang terhitung!
            ->where('slots.1.remaining', 1)  // Kapasitas 2 - 1 = 1 sisa
            ->where('slots.1.status', 'hampir penuh')
        );
    }

    /**
     * 2. CRUD kendaraan milik pelanggan dengan otorisasi Policy (hanya milik sendiri).
     */
    public function test_customer_can_crud_own_vehicles_and_cannot_access_others(): void
    {
        // 1. Tambah kendaraan baru
        $responseCreate = $this->actingAs($this->customerA)->post('/beranda/vehicles', [
            'plate_number' => 'B 1234 ABC',
            'brand' => 'Honda',
            'model' => 'Beat Deluxe',
            'year' => 2023,
        ]);

        $responseCreate->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'user_id' => $this->customerA->id,
            'plate_number' => 'B 1234 ABC',
        ]);

        $vehicleA = Vehicle::where('plate_number', 'B 1234 ABC')->first();

        // 2. Pemilik memperbarui data kendaraannya
        $responseUpdate = $this->actingAs($this->customerA)->put("/beranda/vehicles/{$vehicleA->id}", [
            'plate_number' => 'B 1234 ABC',
            'brand' => 'Honda',
            'model' => 'Beat Street',
            'year' => 2024,
        ]);
        $responseUpdate->assertRedirect();
        $this->assertEquals('Beat Street', $vehicleA->fresh()->model);

        // 3. Pelanggan B DITOLAK (403) saat mencoba mengubah atau menghapus kendaraan Pelanggan A
        $responseForbiddenUpdate = $this->actingAs($this->customerB)->put("/beranda/vehicles/{$vehicleA->id}", [
            'plate_number' => 'B 9999 HCK',
            'brand' => 'Yamaha',
            'model' => 'Mio',
            'year' => 2020,
        ]);
        $responseForbiddenUpdate->assertStatus(403);

        $responseForbiddenDelete = $this->actingAs($this->customerB)->delete("/beranda/vehicles/{$vehicleA->id}");
        $responseForbiddenDelete->assertStatus(403);

        // 4. Pemilik sah menghapus kendaraannya sendiri
        $responseDelete = $this->actingAs($this->customerA)->delete("/beranda/vehicles/{$vehicleA->id}");
        $responseDelete->assertRedirect();
        $this->assertSoftDeleted('vehicles', ['id' => $vehicleA->id]);
    }

    /**
     * 3. Pembuatan reservasi dengan validasi, generate kode booking unik, dan pencatatan riwayat.
     */
    public function test_customer_can_create_reservation_with_unique_code_and_queue(): void
    {
        $targetDate = Carbon::today()->addDays(3)->toDateString();

        $vehicle = Vehicle::create([
            'user_id' => $this->customerA->id,
            'plate_number' => 'B 5555 XYZ',
            'brand' => 'Yamaha',
            'model' => 'NMAX 155',
            'year' => 2023,
        ]);

        $response = $this->actingAs($this->customerA)->post('/reservasi', [
            'booking_date' => $targetDate,
            'estimated_arrival_at' => '10:00',
            'service_id' => $this->service->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'CVT bunyi berdecit di tarikan awal.',
        ]);

        $response->assertRedirect();

        $expectedCode = Booking::generateBookingCode($targetDate, 1);
        $this->assertDatabaseHas('bookings', [
            'booking_code' => $expectedCode,
            'user_id' => $this->customerA->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $this->service->id,
            'booking_date' => $targetDate,
            'queue_order' => 1,
            'status' => BookingStatus::Confirmed->value,
            'complaint' => 'CVT bunyi berdecit di tarikan awal.',
        ]);

        $createdBooking = Booking::where('booking_code', $expectedCode)->first();
        $this->assertNotNull($createdBooking);
        $this->assertDatabaseHas('booking_status_logs', [
            'booking_id' => $createdBooking->id,
            'to_status' => BookingStatus::Confirmed->value,
        ]);
    }

    /**
     * 4. SKENARIO UJI KONKURENSI: Dua pengguna mem-booking slot terakhir secara bersamaan.
     * Pengguna pertama berhasil, pengguna kedua ditolak dengan pesan validasi kapasitas slot penuh.
     */
    public function test_concurrency_two_users_booking_last_slot_simultaneously(): void
    {
        $targetDate = Carbon::today()->addDays(4)->toDateString();

        // Setel kapasitas slot hanya 1 mekanik untuk memicu perebutan slot terakhir
        Setting::set('mechanics_count', 1, 'integer');

        $vehicleA = Vehicle::create([
            'user_id' => $this->customerA->id,
            'plate_number' => 'B 1111 SUC',
            'brand' => 'Honda',
            'model' => 'PCX 160',
            'year' => 2023,
        ]);

        $vehicleB = Vehicle::create([
            'user_id' => $this->customerB->id,
            'plate_number' => 'B 2222 GGL',
            'brand' => 'Yamaha',
            'model' => 'Aerox',
            'year' => 2024,
        ]);

        // Permintaan 1: Pengguna A mem-booking slot 10:00 (Slot Terakhir yang Tersedia)
        $responseA = $this->actingAs($this->customerA)->post('/reservasi', [
            'booking_date' => $targetDate,
            'estimated_arrival_at' => '10:00',
            'service_id' => $this->service->id,
            'vehicle_id' => $vehicleA->id,
            'complaint' => 'Pelanggan pertama yang memesan.',
        ]);

        $responseA->assertSessionHasNoErrors();
        $responseA->assertRedirect();

        // Permintaan 2: Pengguna B mencoba mem-booking slot jam yang sama persis (10:00)
        // Di dalam DB::transaction dengan lockForUpdate, sistem mendeteksi slot sudah penuh!
        $responseB = $this->actingAs($this->customerB)->post('/reservasi', [
            'booking_date' => $targetDate,
            'estimated_arrival_at' => '10:00',
            'service_id' => $this->service->id,
            'vehicle_id' => $vehicleB->id,
            'complaint' => 'Pelanggan kedua mencoba merebut slot.',
        ]);

        // Hasil: Pengguna kedua gagal dengan error validasi ketersediaan slot yang informatif
        $responseB->assertSessionHasErrors(['estimated_arrival_at']);

        // Pastikan hanya tepat 1 pemesanan aktif yang tersimpan untuk slot tersebut
        $activeCount = Booking::whereDate('booking_date', $targetDate)
            ->where('estimated_arrival_at', 'like', '10:%')
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->count();

        $this->assertEquals(1, $activeCount);
    }

    /**
     * 5. Daftar "Booking Saya", detail, dan pembatalan mandiri oleh pelanggan (hanya status Confirmed).
     */
    public function test_customer_can_view_my_bookings_and_cancel_confirmed_booking(): void
    {
        // Tetapkan tanggal 3 hari ke depan agar melebihi ambang batas pembatalan (threshold 2 jam)
        $targetDate = Carbon::today()->addDays(3)->toDateString();

        $vehicle = Vehicle::create([
            'user_id' => $this->customerA->id,
            'plate_number' => 'B 7777 BTL',
            'brand' => 'Honda',
            'model' => 'Scoopy',
            'year' => 2022,
        ]);

        $booking = Booking::create([
            'booking_code' => 'AM-TST-CANCEL-01',
            'user_id' => $this->customerA->id,
            'vehicle_id' => $vehicle->id,
            'service_id' => $this->service->id,
            'booking_date' => $targetDate,
            'queue_order' => 1,
            'estimated_arrival_at' => '14:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        // 1. Tampilkan halaman daftar "Booking Saya"
        $responseIndex = $this->actingAs($this->customerA)->get('/beranda/bookings');
        $responseIndex->assertStatus(200);
        $responseIndex->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Bookings/Index')
            ->has('bookings.data', 1)
        );

        // 2. Tampilkan halaman detail booking
        $responseShow = $this->actingAs($this->customerA)->get("/beranda/bookings/{$booking->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Bookings/Show')
            ->where('booking.booking_code', 'AM-TST-CANCEL-01')
            ->where('can_cancel', true)
        );

        // 3. Batalkan booking dengan alasan pembatalan
        $responseCancel = $this->actingAs($this->customerA)->post("/beranda/bookings/{$booking->id}/cancel", [
            'cancel_reason' => 'Ada acara mendadak di luar kota.',
        ]);

        $responseCancel->assertRedirect();

        $freshBooking = $booking->fresh();
        $this->assertEquals(BookingStatus::Cancelled, $freshBooking->status);
        $this->assertEquals('Ada acara mendadak di luar kota.', $freshBooking->cancel_reason);
        $this->assertNotNull($freshBooking->cancelled_at);

        // Pastikan transisi status tercatat di booking_status_logs
        $this->assertDatabaseHas('booking_status_logs', [
            'booking_id' => $booking->id,
            'to_status' => BookingStatus::Cancelled->value,
            'note' => 'Ada acara mendadak di luar kota.',
        ]);

        // 4. Booking yang sudah dibatalkan tidak boleh dibatalkan ulang
        $responseCancelAgain = $this->actingAs($this->customerA)->post("/beranda/bookings/{$booking->id}/cancel", [
            'cancel_reason' => 'Coba batalkan lagi',
        ]);
        $responseCancelAgain->assertStatus(403);
    }
}
