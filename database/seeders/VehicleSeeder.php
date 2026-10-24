<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Seed kendaraan untuk pelanggan demo.
     */
    public function run(): void
    {
        $siti = User::where('email', 'siti@demo.id')->first();
        $budi = User::where('email', 'budi@demo.id')->first();
        $rian = User::where('email', 'rian@demo.id')->first();

        if ($siti) {
            Vehicle::updateOrCreate(
                ['user_id' => $siti->id, 'plate_number' => 'B 3456 SKA'],
                [
                    'brand' => 'Honda',
                    'model' => 'BeAT Street CBS',
                    'year' => 2022,
                ]
            );

            Vehicle::updateOrCreate(
                ['user_id' => $siti->id, 'plate_number' => 'B 6789 TGH'],
                [
                    'brand' => 'Honda',
                    'model' => 'Scoopy Prestige',
                    'year' => 2023,
                ]
            );
        }

        if ($budi) {
            Vehicle::updateOrCreate(
                ['user_id' => $budi->id, 'plate_number' => 'B 4512 JKL'],
                [
                    'brand' => 'Yamaha',
                    'model' => 'NMAX 155 Connected',
                    'year' => 2021,
                ]
            );
        }

        if ($rian) {
            Vehicle::updateOrCreate(
                ['user_id' => $rian->id, 'plate_number' => 'B 1290 PQR'],
                [
                    'brand' => 'Honda',
                    'model' => 'Vario 160 eSP+',
                    'year' => 2024,
                ]
            );
        }
    }
}
