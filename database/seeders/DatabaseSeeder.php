<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengisi akun admin (.env), pelanggan demo, setting bengkel, layanan, kendaraan, dan booking.
     */
    public function run(): void
    {
        // 1. Akun Administrator Bengkel (Kredensial wajib dari .env)
        $adminEmail = env('ADMIN_EMAIL', 'admin@aldimotor.com');
        $adminPassword = env('ADMIN_PASSWORD', 'AdminPassword2026!');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator ALDI-MOTOR',
                'phone' => '6281234567890',
                'role' => Role::Admin,
                'whatsapp_opt_in' => true,
                'no_show_count' => 0,
                'blocked_until' => null,
                'aktif' => true,
                'password' => Hash::make($adminPassword),
            ]
        );

        // 2. Tiga Akun Pelanggan Demo dengan nomor fiktif aman (6280000000001, dst)
        $customers = [
            [
                'email' => 'siti@demo.id',
                'name' => 'Siti Rahmawati (Demo)',
                'phone' => '6280000000001',
                'whatsapp_opt_in' => true,
            ],
            [
                'email' => 'budi@demo.id',
                'name' => 'Budi Santoso (Demo)',
                'phone' => '6280000000002',
                'whatsapp_opt_in' => true,
            ],
            [
                'email' => 'rian@demo.id',
                'name' => 'Rian Hidayat (Demo)',
                'phone' => '6280000000003',
                'whatsapp_opt_in' => false,
            ],
        ];

        foreach ($customers as $customer) {
            User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'role' => Role::Customer,
                    'whatsapp_opt_in' => $customer['whatsapp_opt_in'],
                    'no_show_count' => 0,
                    'blocked_until' => null,
                    'aktif' => true,
                    'password' => Hash::make('password'),
                ]
            );
        }

        // 3. Jalankan Seeder Modul Inti Bengkel
        $this->call([
            WorkshopSettingSeeder::class,
            ServiceSeeder::class,
            VehicleSeeder::class,
            DailyCapacitySeeder::class,
            BookingSeeder::class,
        ]);
    }
}
