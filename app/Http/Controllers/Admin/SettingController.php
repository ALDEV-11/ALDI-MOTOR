<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkshopSetting;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Tampilkan formulir pengaturan operasional dan template notifikasi bengkel.
     */
    public function edit(): Response
    {
        $this->authorize('viewAny', WorkshopSetting::class);

        $settings = [
            'workshop_name' => Setting::get('workshop_name', 'ALDI-MOTOR'),
            'workshop_phone' => Setting::get('workshop_phone', '6281234567890'),
            'workshop_address' => Setting::get('workshop_address', 'Jl. Raya Otomotif No. 88, RT.02/RW.05, Jakarta'),
            'daily_quota' => (int) Setting::get('daily_quota', 20),
            'operating_hours_start' => Setting::get('operating_hours_start', '08:00'),
            'operating_hours_end' => Setting::get('operating_hours_end', '17:00'),
            'mechanics_count' => (int) Setting::get('mechanics_count', 4),
            'booking_window_days' => (int) Setting::get('booking_window_days', 14),
            'booking_cutoff_time' => Setting::get('booking_cutoff_time', '21:00'),
            'cancellation_threshold_hours' => (int) Setting::get('cancellation_threshold_hours', 2),
            'late_tolerance_minutes' => (int) Setting::get('late_tolerance_minutes', 15),
            'max_active_bookings_per_user' => (int) Setting::get('max_active_bookings_per_user', 2),
            'max_waiting_list_slots' => (int) Setting::get('max_waiting_list_slots', 5),
            'no_show_threshold' => (int) Setting::get('no_show_threshold', 3),
            'block_duration_days' => (int) Setting::get('block_duration_days', 7),

            // Template WhatsApp dengan placeholder dinamis
            'wa_template_booking_confirmed' => Setting::get(
                'wa_template_booking_confirmed',
                'Halo {nama}, reservasi servis motor Anda ({plat}) di {nama_bengkel} telah DIKONFIRMASI dengan kode {kode_booking}. Mohon tiba sebelum {jam_tutup}. Terima kasih!'
            ),
            'wa_template_service_completed' => Setting::get(
                'wa_template_service_completed',
                'Halo {nama}, pengerjaan servis ({layanan}) untuk motor Anda ({plat}) telah SELESAI. Anda dapat mengambil motor di {nama_bengkel} sebelum jam {jam_tutup}.'
            ),
            'wa_template_waiting_list_promoted' => Setting::get(
                'wa_template_waiting_list_promoted',
                'Kabar gembira {nama}! Slot antrean telah tersedia untuk motor {plat}. Kode booking Anda: {kode_booking}. Mohon segera konfirmasi kehadiran Anda.'
            ),
            'wa_template_service_reminder' => Setting::get(
                'wa_template_service_reminder',
                'Halo {nama}, motor Anda ({plat}) sudah waktunya servis berkala ({layanan}). Segera reservasi slot servis di {nama_bengkel} untuk menjaga performa optimal mesin Anda.'
            ),
        ];

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
        ]);
    }

    /**
     * Simpan pembaruan pengaturan bengkel dan segarkan cache.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', WorkshopSetting::class);

        $validated = $request->validate([
            'workshop_name' => ['required', 'string', 'max:100'],
            'workshop_phone' => ['required', 'string', 'max:25'],
            'workshop_address' => ['required', 'string', 'max:255'],
            'daily_quota' => ['required', 'integer', 'min:1', 'max:200'],
            'operating_hours_start' => ['required', 'date_format:H:i'],
            'operating_hours_end' => ['required', 'date_format:H:i', 'after:operating_hours_start'],
            'mechanics_count' => ['required', 'integer', 'min:1', 'max:50'],
            'booking_window_days' => ['required', 'integer', 'min:1', 'max:90'],
            'booking_cutoff_time' => ['required', 'date_format:H:i'],
            'cancellation_threshold_hours' => ['required', 'integer', 'min:0', 'max:48'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'max_active_bookings_per_user' => ['required', 'integer', 'min:1', 'max:10'],
            'max_waiting_list_slots' => ['required', 'integer', 'min:0', 'max:50'],
            'no_show_threshold' => ['required', 'integer', 'min:1', 'max:10'],
            'block_duration_days' => ['required', 'integer', 'min:1', 'max:90'],

            'wa_template_booking_confirmed' => ['required', 'string', 'max:1000'],
            'wa_template_service_completed' => ['required', 'string', 'max:1000'],
            'wa_template_waiting_list_promoted' => ['required', 'string', 'max:1000'],
            'wa_template_service_reminder' => ['required', 'string', 'max:1000'],
        ], [
            'daily_quota.min' => 'Kuota harian default minimal 1 motor.',
            'operating_hours_end.after' => 'Jam tutup bengkel harus setelah jam buka.',
            'mechanics_count.min' => 'Jumlah mekanik minimal 1 orang.',
        ]);

        $integerKeys = [
            'daily_quota',
            'mechanics_count',
            'booking_window_days',
            'cancellation_threshold_hours',
            'late_tolerance_minutes',
            'max_active_bookings_per_user',
            'max_waiting_list_slots',
            'no_show_threshold',
            'block_duration_days',
        ];

        foreach ($validated as $key => $value) {
            $type = in_array($key, $integerKeys, true) ? 'integer' : 'string';
            Setting::set($key, $value, $type);
        }

        return redirect()->route('admin.settings.edit')->with(
            'success',
            'Pengaturan bengkel dan template pesan berhasil diperbarui serta cache telah disinkronkan.'
        );
    }
}
