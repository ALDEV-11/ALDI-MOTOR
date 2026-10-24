<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\DailyCapacity;
use App\Models\Service;
use App\Models\Vehicle;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicReservationController extends Controller
{
    /**
     * Tampilkan halaman portal reservasi publik beserta ketersediaan slot per tanggal.
     * Dapat diakses baik oleh tamu (guest) maupun pengguna yang sudah masuk.
     */
    public function index(Request $request): Response
    {
        $today = Carbon::today('Asia/Jakarta');
        $bookingWindowDays = (int) Setting::get('booking_window_days', 14);
        $maxDate = $today->copy()->addDays($bookingWindowDays);

        // Ambil query parameter ?tanggal=YYYY-MM-DD (fallback ke 'date' atau hari ini)
        $dateParam = $request->query('tanggal') ?? $request->query('date');
        $selectedDate = $today->copy();

        if ($dateParam) {
            try {
                $parsed = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfDay();
                if ($parsed->greaterThanOrEqualTo($today) && $parsed->lessThanOrEqualTo($maxDate)) {
                    $selectedDate = $parsed;
                }
            } catch (\Throwable) {
                $selectedDate = $today->copy();
            }
        }

        $selectedDateString = $selectedDate->toDateString();

        // Ambil data kapasitas & status operasional bengkel
        $isOpen = DailyCapacity::isOpenOn($selectedDateString);
        $dailyCapacityRecord = DailyCapacity::whereDate('date', $selectedDateString)->first();
        $dailyQuota = DailyCapacity::getEffectiveQuota($selectedDateString);
        $operatingNote = $dailyCapacityRecord?->note;

        // Cek apakah tanggal hari ini sudah melewati jam cutoff booking H-1 / hari ini
        $cutoffTimeStr = Setting::get('booking_cutoff_time', '21:00');
        $isToday = $selectedDate->isToday();
        $isCutoffPassed = false;

        if ($isToday) {
            $currentTimeStr = Carbon::now('Asia/Jakarta')->format('H:i');
            if ($currentTimeStr >= $cutoffTimeStr) {
                $isCutoffPassed = true;
            }
        }

        // Hitung total booking aktif (selain dibatalkan) pada tanggal terpilih
        $totalBookedOnDate = Booking::whereDate('booking_date', $selectedDateString)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->count();

        // Tentukan kapasitas per slot berdasarkan jumlah mekanik
        $slotCapacity = (int) Setting::get('mechanics_count', 2);
        if ($slotCapacity < 1) {
            $slotCapacity = 1;
        }

        // Jam operasional bengkel (misal 08:00 - 17:00)
        $startHour = 8;
        $endHour = 17;
        try {
            $startHour = (int) explode(':', Setting::get('operating_hours_start', '08:00'))[0];
            $endHour = (int) explode(':', Setting::get('operating_hours_end', '17:00'))[0];
        } catch (\Throwable) {
            // fallback default
        }

        // Ambil pemesanan aktif tanggal terpilih untuk penghitungan slot
        $activeBookings = Booking::whereDate('booking_date', $selectedDateString)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->get(['id', 'estimated_arrival_at']);

        // Buat daftar slot waktu (jam kerja bengkel, jeda istirahat 12:00 diakomodasi atau dilewati)
        $slots = [];
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            // Lewati jam istirahat siang 12:00 - 13:00 untuk operasional mekanik
            if ($hour === 12) {
                continue;
            }

            $timeString = sprintf('%02d:00', $hour);
            $hourPrefix = sprintf('%02d', $hour);

            // Hitung booking yang masuk pada jam ini
            $bookedCount = $activeBookings->filter(function ($b) use ($hourPrefix) {
                return str_starts_with((string) $b->estimated_arrival_at, $hourPrefix);
            })->count();

            $remaining = max(0, $slotCapacity - $bookedCount);

            // Tentukan status ketersediaan slot: 'tersedia', 'hampir penuh', 'penuh'
            $slotStatus = 'tersedia';
            if (! $isOpen || $isCutoffPassed || $totalBookedOnDate >= $dailyQuota || $remaining <= 0) {
                $slotStatus = 'penuh';
            } elseif ($remaining === 1 || $remaining <= (int) ceil($slotCapacity / 2)) {
                $slotStatus = 'hampir penuh';
            }

            $slots[] = [
                'time' => $timeString,
                'label' => "{$timeString} WIB",
                'capacity' => $slotCapacity,
                'booked' => $bookedCount,
                'remaining' => $remaining,
                'status' => $slotStatus, // 'tersedia' | 'hampir penuh' | 'penuh'
            ];
        }

        // Ambil daftar layanan servis yang aktif
        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'duration_minutes', 'price_estimate']);

        // Ambil kendaraan pelanggan jika sudah login
        $user = $request->user();
        $vehicles = [];
        if ($user) {
            $vehicles = $user->vehicles()
                ->orderBy('plate_number')
                ->get(['id', 'plate_number', 'brand', 'model', 'year']);
        }

        return Inertia::render('Reservations/Index', [
            'selected_date' => $selectedDateString,
            'min_date' => $today->toDateString(),
            'max_date' => $maxDate->toDateString(),
            'is_open' => $isOpen,
            'is_cutoff_passed' => $isCutoffPassed,
            'note' => $operatingNote,
            'daily_quota' => $dailyQuota,
            'total_booked' => $totalBookedOnDate,
            'slots' => $slots,
            'services' => $services,
            'vehicles' => $vehicles,
            'workshop_info' => [
                'name' => Setting::get('workshop_name', 'ALDI-MOTOR'),
                'phone' => Setting::get('workshop_phone', '6281234567890'),
                'address' => Setting::get('workshop_address', 'Jl. Raya Otomotif No. 88, RT.02/RW.05, Jakarta'),
                'start_time' => Setting::get('operating_hours_start', '08:00'),
                'end_time' => Setting::get('operating_hours_end', '17:00'),
            ],
        ]);
    }

    /**
     * Simpan reservasi servis baru dari pelanggan.
     * Mencegah race condition dengan DB::transaction dan pessimistic locking (lockForUpdate).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('warning', 'Silakan login terlebih dahulu untuk menyelesaikan reservasi.');
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'booking_date' => 'Akun Anda sedang diblokir sementara karena akumulasi ketidakhadiran (no-show). Silakan hubungi admin bengkel.',
            ]);
        }

        $validated = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'estimated_arrival_at' => ['required', 'string'],
            'service_id' => ['required', 'exists:services,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            // Input kendaraan baru jika belum terdaftar
            'plate_number' => ['nullable', 'string', 'max:20'],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
            'complaint' => ['nullable', 'string', 'max:500'],
        ], [
            'booking_date.required' => 'Tanggal servis wajib dipilih.',
            'booking_date.after_or_equal' => 'Tanggal servis tidak boleh di masa lalu.',
            'estimated_arrival_at.required' => 'Slot jam kedatangan wajib dipilih.',
            'service_id.required' => 'Jenis layanan servis wajib dipilih.',
            'service_id.exists' => 'Layanan servis yang dipilih tidak valid.',
        ]);

        $bookingDate = Carbon::parse($validated['booking_date'])->toDateString();
        $arrivalSlot = substr($validated['estimated_arrival_at'], 0, 5); // Format: '09:00'
        $hourPrefix = substr($arrivalSlot, 0, 2);

        // Batasi jumlah booking aktif per pelanggan per hari
        $maxPerUser = (int) Setting::get('max_active_bookings_per_user', 2);
        $userActiveBookingsCount = Booking::where('user_id', $user->id)
            ->whereDate('booking_date', $bookingDate)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->count();

        if ($userActiveBookingsCount >= $maxPerUser) {
            throw ValidationException::withMessages([
                'booking_date' => "Anda telah mencapai batas maksimal ({$maxPerUser}) pemesanan aktif untuk tanggal tersebut.",
            ]);
        }

        // EKSEKUSI DI DALAM DB::TRANSACTION DENGAN PESSIMISTIC LOCKING
        $booking = DB::transaction(function () use ($user, $validated, $bookingDate, $arrivalSlot, $hourPrefix) {
            // 1. Lock baris kapasitas harian untuk tanggal ini agar proses konkuren terserialisasi
            $dailyCapacity = DailyCapacity::firstOrCreate(
                ['date' => $bookingDate],
                ['quota' => null, 'is_open' => true, 'note' => null]
            );

            $lockedCapacity = DailyCapacity::where('id', $dailyCapacity->id)->lockForUpdate()->first();

            if (! $lockedCapacity->is_open) {
                throw ValidationException::withMessages([
                    'booking_date' => 'Bengkel tidak beroperasi / libur pada tanggal terpilih'.($lockedCapacity->note ? " ({$lockedCapacity->note})" : '').'.',
                ]);
            }

            // 2. Periksa kuota harian total (selain yang dibatalkan)
            $effectiveQuota = $lockedCapacity->quota ?? (int) Setting::get('daily_quota', 20);
            $totalActive = Booking::whereDate('booking_date', $bookingDate)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->lockForUpdate()
                ->count();

            if ($totalActive >= $effectiveQuota) {
                throw ValidationException::withMessages([
                    'booking_date' => 'Mohon maaf, kuota servis untuk tanggal ini sudah penuh. Silakan pilih tanggal lain.',
                ]);
            }

            // 3. Periksa kapasitas spesifik pada jam slot ini (lockForUpdate)
            $slotCapacity = (int) Setting::get('mechanics_count', 2);
            $slotBooked = Booking::whereDate('booking_date', $bookingDate)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->where('estimated_arrival_at', 'like', "{$hourPrefix}:%")
                ->lockForUpdate()
                ->count();

            if ($slotBooked >= $slotCapacity) {
                throw ValidationException::withMessages([
                    'estimated_arrival_at' => "Mohon maaf, slot jam {$arrivalSlot} WIB baru saja terisi penuh oleh pelanggan lain. Silakan pilih jam lain yang masih tersedia.",
                ]);
            }

            // 4. Resolusi kendaraan pelanggan (pilih kendaraan terdaftar atau simpan baru)
            if (! empty($validated['vehicle_id'])) {
                $vehicle = Vehicle::where('id', $validated['vehicle_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (! $vehicle) {
                    throw ValidationException::withMessages([
                        'vehicle_id' => 'Data kendaraan yang dipilih tidak valid atau bukan milik Anda.',
                    ]);
                }
            } else {
                if (empty($validated['plate_number']) || empty($validated['brand']) || empty($validated['model'])) {
                    throw ValidationException::withMessages([
                        'plate_number' => 'Silakan pilih kendaraan yang sudah terdaftar atau lengkapi data motor baru.',
                    ]);
                }

                $cleanPlate = strtoupper(preg_replace('/\s+/', ' ', trim($validated['plate_number'])));

                // Cek atau buat kendaraan baru untuk user
                $vehicle = Vehicle::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'plate_number' => $cleanPlate,
                    ],
                    [
                        'brand' => $validated['brand'],
                        'model' => $validated['model'],
                        'year' => (int) ($validated['year'] ?? date('Y')),
                    ]
                );
            }

            // 5. Generate nomor antrean dan kode booking unik
            $nextQueue = Booking::getNextQueueOrder($bookingDate);
            $bookingCode = Booking::generateBookingCode($bookingDate, $nextQueue);

            // 6. Buat rekaman Booking dengan status terkonfirmasi
            $newBooking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'service_id' => $validated['service_id'],
                'booking_date' => $bookingDate,
                'queue_order' => $nextQueue,
                'estimated_arrival_at' => "{$arrivalSlot}:00",
                'status' => BookingStatus::Confirmed,
                'complaint' => $validated['complaint'] ?? null,
            ]);

            // 7. Catat log perubahan status awal
            $newBooking->statusLogs()->create([
                'from_status' => null,
                'to_status' => BookingStatus::Confirmed->value,
                'changed_by' => $user->id,
                'note' => 'Pemesanan dibuat secara mandiri melalui portal pelanggan.',
            ]);

            // 8. Log pesan WhatsApp jika pengguna mengaktifkan notifikasi
            if ($user->whatsapp_opt_in && $user->phone) {
                $serviceName = Service::find($validated['service_id'])?->name ?? 'Servis Motor';
                $template = Setting::get(
                    'wa_template_booking_confirmed',
                    'Halo {nama}, reservasi servis motor Anda ({plat}) di {nama_bengkel} telah DIKONFIRMASI dengan kode {kode_booking}. Mohon tiba jam {jam_tutup}. Terima kasih!'
                );

                $waMessage = strtr($template, [
                    '{nama}' => $user->name,
                    '{plat}' => $vehicle->plate_number,
                    '{layanan}' => $serviceName,
                    '{kode_booking}' => $bookingCode,
                    '{jam_tutup}' => $arrivalSlot.' WIB',
                    '{nama_bengkel}' => Setting::get('workshop_name', 'ALDI-MOTOR'),
                ]);

                $newBooking->notificationLogs()->create([
                    'type' => 'booking_confirmation',
                    'channel' => 'whatsapp',
                    'phone' => $user->phone,
                    'message' => $waMessage,
                    'sent_by' => null,
                ]);
            }

            return $newBooking;
        });

        return redirect()->route('customer.bookings.show', $booking->id)->with(
            'success',
            "Reservasi servis Anda berhasil dikonfirmasi! Kode booking: {$booking->booking_code}."
        );
    }
}
