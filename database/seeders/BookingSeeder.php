<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Seed riwayat pemesanan servis demo untuk berbagai tanggal dan status.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $siti = User::where('email', 'siti@demo.id')->first();
        $budi = User::where('email', 'budi@demo.id')->first();
        $rian = User::where('email', 'rian@demo.id')->first();

        if (! $siti || ! $budi || ! $rian) {
            return;
        }

        $sitiBeat = Vehicle::where('user_id', $siti->id)->where('plate_number', 'B 3456 SKA')->first();
        $sitiScoopy = Vehicle::where('user_id', $siti->id)->where('plate_number', 'B 6789 TGH')->first();
        $budiNmax = Vehicle::where('user_id', $budi->id)->first();
        $rianVario = Vehicle::where('user_id', $rian->id)->first();

        $tuneUp = Service::where('name', 'like', '%Tune-up%')->first();
        $gantiOli = Service::where('name', 'like', '%Ganti Oli%')->first();
        $cvt = Service::where('name', 'like', '%CVT%')->first();
        $injeksi = Service::where('name', 'like', '%Injeksi%')->first();
        $rem = Service::where('name', 'like', '%Rem%')->first();

        $yesterday = Carbon::yesterday();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Booking Kemarin - Selesai (Completed)
        if ($sitiBeat && $tuneUp) {
            $b1 = Booking::updateOrCreate(
                ['booking_code' => Booking::generateBookingCode($yesterday, 1)],
                [
                    'user_id' => $siti->id,
                    'vehicle_id' => $sitiBeat->id,
                    'service_id' => $tuneUp->id,
                    'booking_date' => $yesterday->toDateString(),
                    'queue_order' => 1,
                    'estimated_arrival_at' => '08:30:00',
                    'status' => BookingStatus::Completed,
                    'complaint' => 'Tarikan gas terasa agak berat di tanjakan.',
                    'checked_in_at' => $yesterday->copy()->setTime(8, 25),
                    'started_at' => $yesterday->copy()->setTime(8, 35),
                    'finished_at' => $yesterday->copy()->setTime(9, 20),
                ]
            );

            $b1->statusLogs()->createMany([
                ['from_status' => null, 'to_status' => BookingStatus::Pending->value, 'note' => 'Pemesanan dibuat via portal pelanggan', 'created_at' => $yesterday->copy()->subDay()],
                ['from_status' => BookingStatus::Pending->value, 'to_status' => BookingStatus::Confirmed->value, 'changed_by' => $admin?->id, 'note' => 'Disetujui admin bengkel', 'created_at' => $yesterday->copy()->subHours(12)],
                ['from_status' => BookingStatus::Confirmed->value, 'to_status' => BookingStatus::CheckedIn->value, 'changed_by' => $admin?->id, 'note' => 'Pelanggan tiba di bengkel', 'created_at' => $yesterday->copy()->setTime(8, 25)],
                ['from_status' => BookingStatus::CheckedIn->value, 'to_status' => BookingStatus::InProgress->value, 'changed_by' => $admin?->id, 'note' => 'Dikerjakan mekanik pit 1', 'created_at' => $yesterday->copy()->setTime(8, 35)],
                ['from_status' => BookingStatus::InProgress->value, 'to_status' => BookingStatus::Completed->value, 'changed_by' => $admin?->id, 'note' => 'Servis selesai dan motor siap diambil', 'created_at' => $yesterday->copy()->setTime(9, 20)],
            ]);
        }

        // 2. Booking Hari Ini #1 - Sedang Dikerjakan (In Progress)
        if ($sitiScoopy && $cvt) {
            $b2 = Booking::updateOrCreate(
                ['booking_code' => Booking::generateBookingCode($today, 1)],
                [
                    'user_id' => $siti->id,
                    'vehicle_id' => $sitiScoopy->id,
                    'service_id' => $cvt->id,
                    'booking_date' => $today->toDateString(),
                    'queue_order' => 1,
                    'estimated_arrival_at' => '09:00:00',
                    'status' => BookingStatus::InProgress,
                    'complaint' => 'Ada bunyi berdecit saat rpm rendah dari arah bak CVT.',
                    'checked_in_at' => $today->copy()->setTime(8, 55),
                    'started_at' => $today->copy()->setTime(9, 5),
                ]
            );

            $b2->statusLogs()->createMany([
                ['from_status' => null, 'to_status' => BookingStatus::Pending->value, 'note' => 'Pemesanan dibuat', 'created_at' => $today->copy()->subDay()],
                ['from_status' => BookingStatus::Pending->value, 'to_status' => BookingStatus::Confirmed->value, 'changed_by' => $admin?->id, 'note' => 'Dikonfirmasi', 'created_at' => $today->copy()->subHours(8)],
                ['from_status' => BookingStatus::Confirmed->value, 'to_status' => BookingStatus::CheckedIn->value, 'changed_by' => $admin?->id, 'note' => 'Pelanggan sudah check-in', 'created_at' => $today->copy()->setTime(8, 55)],
                ['from_status' => BookingStatus::CheckedIn->value, 'to_status' => BookingStatus::InProgress->value, 'changed_by' => $admin?->id, 'note' => 'Mulai pengerjaan pembersihan CVT', 'created_at' => $today->copy()->setTime(9, 5)],
            ]);

            $b2->notificationLogs()->create([
                'type' => 'booking_confirmation',
                'channel' => 'whatsapp',
                'phone' => $siti->phone,
                'message' => 'Halo Siti, booking servis CVT Anda telah dikonfirmasi untuk hari ini jam 09:00 WIB.',
                'sent_by' => $admin?->id,
                'created_at' => $today->copy()->setTime(8, 0),
            ]);
        }

        // 3. Booking Hari Ini #2 - Sudah Check-in di Kasir (Checked In)
        if ($rianVario && $injeksi) {
            $b3 = Booking::updateOrCreate(
                ['booking_code' => Booking::generateBookingCode($today, 2)],
                [
                    'user_id' => $rian->id,
                    'vehicle_id' => $rianVario->id,
                    'service_id' => $injeksi->id,
                    'booking_date' => $today->toDateString(),
                    'queue_order' => 2,
                    'estimated_arrival_at' => '10:00:00',
                    'status' => BookingStatus::CheckedIn,
                    'complaint' => 'Lampu indikator MIL sempat berkedip 1 kali.',
                    'checked_in_at' => $today->copy()->setTime(9, 50),
                ]
            );

            $b3->statusLogs()->createMany([
                ['from_status' => null, 'to_status' => BookingStatus::Pending->value, 'note' => 'Pemesanan online', 'created_at' => $today->copy()->subDay()],
                ['from_status' => BookingStatus::Pending->value, 'to_status' => BookingStatus::Confirmed->value, 'changed_by' => $admin?->id, 'note' => 'Dikonfirmasi admin', 'created_at' => $today->copy()->subHours(6)],
                ['from_status' => BookingStatus::Confirmed->value, 'to_status' => BookingStatus::CheckedIn->value, 'changed_by' => $admin?->id, 'note' => 'Menunggu mekanik tersedia', 'created_at' => $today->copy()->setTime(9, 50)],
            ]);
        }

        // 4. Booking Hari Ini #3 - Dibatalkan (Cancelled)
        if ($budiNmax && $gantiOli) {
            $b4 = Booking::updateOrCreate(
                ['booking_code' => Booking::generateBookingCode($today, 3)],
                [
                    'user_id' => $budi->id,
                    'vehicle_id' => $budiNmax->id,
                    'service_id' => $gantiOli->id,
                    'booking_date' => $today->toDateString(),
                    'queue_order' => 3,
                    'estimated_arrival_at' => '11:00:00',
                    'status' => BookingStatus::Cancelled,
                    'complaint' => 'Ganti oli berkala.',
                    'cancel_reason' => 'Pelanggan ada dinas luar kota mendadak.',
                    'cancelled_at' => $today->copy()->setTime(8, 15),
                ]
            );

            $b4->statusLogs()->createMany([
                ['from_status' => null, 'to_status' => BookingStatus::Pending->value, 'note' => 'Pemesanan dibuat', 'created_at' => $today->copy()->subDay()],
                ['from_status' => BookingStatus::Pending->value, 'to_status' => BookingStatus::Cancelled->value, 'changed_by' => $budi->id, 'note' => 'Dibatalkan oleh pelanggan: dinas luar kota', 'created_at' => $today->copy()->setTime(8, 15)],
            ]);
        }

        // 5. Booking Besok - Dikonfirmasi (Confirmed)
        if ($budiNmax && $rem) {
            $b5 = Booking::updateOrCreate(
                ['booking_code' => Booking::generateBookingCode($tomorrow, 1)],
                [
                    'user_id' => $budi->id,
                    'vehicle_id' => $budiNmax->id,
                    'service_id' => $rem->id,
                    'booking_date' => $tomorrow->toDateString(),
                    'queue_order' => 1,
                    'estimated_arrival_at' => '08:30:00',
                    'status' => BookingStatus::Confirmed,
                    'complaint' => 'Kampas rem depan bunyi mendecit keras.',
                ]
            );

            $b5->statusLogs()->createMany([
                ['from_status' => null, 'to_status' => BookingStatus::Pending->value, 'note' => 'Booking awal', 'created_at' => $today->copy()->setTime(7, 30)],
                ['from_status' => BookingStatus::Pending->value, 'to_status' => BookingStatus::Confirmed->value, 'changed_by' => $admin?->id, 'note' => 'Disetujui untuk antrean pertama besok', 'created_at' => $today->copy()->setTime(9, 0)],
            ]);
        }
    }
}
