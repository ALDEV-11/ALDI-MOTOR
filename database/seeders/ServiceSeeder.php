<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Seed 8 layanan servis motor realistis dengan estimasi biaya dan durasi.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Servis Ringan / Tune-up Rutin',
                'description' => 'Pembersihan saringan udara, cek busi, cek sistem pengereman, pelumasan kabel gas/kopling, serta penyetelan celah klep & rpm stasioner.',
                'duration_minutes' => 45,
                'price_estimate' => 75000,
                'reminder_interval_days' => 60, // 2 bulan
                'is_active' => true,
            ],
            [
                'name' => 'Ganti Oli Mesin & Transmisi',
                'description' => 'Penggantian oli mesin 4T/Matic dan kuras oli gardan transmisi final gear, termasuk pembersihan baut pembuangan magnetik.',
                'duration_minutes' => 20,
                'price_estimate' => 55000,
                'reminder_interval_days' => 30, // 1 bulan
                'is_active' => true,
            ],
            [
                'name' => 'Servis CVT Komplit & Pembersihan',
                'description' => 'Pembongkaran pulley depan & belakang, amplas mangkok kopling ganda, inspeksi roller & v-belt, serta pemberian grease khusus CVT tahan panas.',
                'duration_minutes' => 60,
                'price_estimate' => 95000,
                'reminder_interval_days' => 90, // 3 bulan
                'is_active' => true,
            ],
            [
                'name' => 'Servis Injeksi & Pembersihan Throttle Body',
                'description' => 'Pembersihan kerak injector dengan mesin ultrasonic cleaner, kalibrasi sensor TPS, dan reset ECU via diagnostic scanner.',
                'duration_minutes' => 45,
                'price_estimate' => 85000,
                'reminder_interval_days' => 90, // 3 bulan
                'is_active' => true,
            ],
            [
                'name' => 'Servis Sistem Rem (Depan & Belakang)',
                'description' => 'Pembersihan kaliper rem, kuras minyak rem DOT 4, penggantian kampas rem (dispad/tromol), dan bleeding angin palsu.',
                'duration_minutes' => 40,
                'price_estimate' => 65000,
                'reminder_interval_days' => 120, // 4 bulan
                'is_active' => true,
            ],
            [
                'name' => 'Ganti Ban Luar / Dalam & Balancing',
                'description' => 'Pemasangan ban menggunakan mesin tyre changer hidrolik (anti lecet velg), penggantian pentil tubeless, dan balancing roda.',
                'duration_minutes' => 30,
                'price_estimate' => 45000,
                'reminder_interval_days' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Servis Berat / Skir Klep & Kuras Ruang Bakar',
                'description' => 'Pembongkaran head silinder, pembersihan deposit karbon piston, skir ulang payung klep, dan penggantian paking silinder.',
                'duration_minutes' => 180,
                'price_estimate' => 350000,
                'reminder_interval_days' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Pemeriksaan Kelistrikan, Aki & Lampu',
                'description' => 'Uji daya tampung aki dengan battery load analyzer, pengecekan regulator/kiprok, ganti bohlam putus, dan jalur kabel utama.',
                'duration_minutes' => 30,
                'price_estimate' => 40000,
                'reminder_interval_days' => 180, // 6 bulan
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
