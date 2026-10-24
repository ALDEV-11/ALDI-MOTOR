<?php

namespace Database\Seeders;

use App\Models\DailyCapacity;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DailyCapacitySeeder extends Seeder
{
    /**
     * Seed kapasitas harian dan hari libur bengkel.
     */
    public function run(): void
    {
        $today = Carbon::today();

        $capacities = [
            [
                'date' => $today->toDateString(),
                'quota' => 10,
                'is_open' => true,
                'note' => 'Operasional normal',
            ],
            [
                'date' => $today->copy()->addDay()->toDateString(),
                'quota' => 12,
                'is_open' => true,
                'note' => 'Penambahan kuota mekanik piket lengkap',
            ],
            [
                'date' => $today->copy()->addDays(2)->toDateString(),
                'quota' => 10,
                'is_open' => true,
                'note' => 'Operasional normal',
            ],
            [
                'date' => $today->copy()->addDays(5)->toDateString(),
                'quota' => null,
                'is_open' => false,
                'note' => 'Tutup: Libur Nasional / Kalibrasi Mesin Dyno',
            ],
        ];

        foreach ($capacities as $capacity) {
            DailyCapacity::updateOrCreate(
                ['date' => $capacity['date']],
                $capacity
            );
        }
    }
}
