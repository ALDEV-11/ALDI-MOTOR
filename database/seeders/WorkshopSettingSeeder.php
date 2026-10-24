<?php

namespace Database\Seeders;

use App\Support\Setting;
use Illuminate\Database\Seeder;

class WorkshopSettingSeeder extends Seeder
{
    /**
     * Seed konfigurasi awal bengkel ALDI-MOTOR.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'daily_quota',
                'value' => 10,
                'type' => 'integer',
            ],
            [
                'key' => 'operating_hours_start',
                'value' => '08:00',
                'type' => 'string',
            ],
            [
                'key' => 'operating_hours_end',
                'value' => '17:00',
                'type' => 'string',
            ],
            [
                'key' => 'workshop_name',
                'value' => 'ALDI-MOTOR',
                'type' => 'string',
            ],
            [
                'key' => 'workshop_phone',
                'value' => '6281234567890',
                'type' => 'string',
            ],
            [
                'key' => 'workshop_address',
                'value' => 'Jl. Raya Otomotif No. 88, RT.02/RW.05, Jakarta',
                'type' => 'string',
            ],
            [
                'key' => 'max_no_show_limit',
                'value' => 3,
                'type' => 'integer',
            ],
            [
                'key' => 'block_duration_days',
                'value' => 7,
                'type' => 'integer',
            ],
            [
                'key' => 'whatsapp_reminder_template',
                'value' => 'Halo {nama}, kami mengingatkan jadwal servis motor Anda ({plat_nomor}) di ALDI-MOTOR pada {tanggal} estimasi jam {jam}. Mohon hadir 10 menit sebelum jadwal. Terima kasih!',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::set($setting['key'], $setting['value'], $setting['type']);
        }
    }
}
