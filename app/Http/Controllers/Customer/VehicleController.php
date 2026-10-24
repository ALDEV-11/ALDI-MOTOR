<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Tampilkan daftar garasi kendaraan milik pelanggan.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = $request->user()->vehicles()
            ->withCount(['bookings' => function ($q) {
                $q->where('status', '!=', 'cancelled');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Customer/Vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Daftarkan kendaraan baru ke garasi pelanggan.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);

        $user = $request->user();

        $validated = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')->where('user_id', $user->id),
            ],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
        ], [
            'plate_number.required' => 'Nomor plat kendaraan wajib diisi.',
            'plate_number.unique' => 'Plat nomor ini sudah terdaftar di akun Anda.',
            'brand.required' => 'Merk motor wajib diisi (misal: Honda, Yamaha).',
            'model.required' => 'Model atau tipe motor wajib diisi (misal: Vario 160).',
            'year.required' => 'Tahun perakitan kendaraan wajib diisi.',
        ]);

        $cleanPlate = strtoupper(preg_replace('/\s+/', ' ', trim($validated['plate_number'])));

        $user->vehicles()->create([
            'plate_number' => $cleanPlate,
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'year' => $validated['year'],
        ]);

        return redirect()->back()->with('success', "Kendaraan {$cleanPlate} berhasil ditambahkan ke garasi Anda.");
    }

    /**
     * Perbarui data kendaraan milik pelanggan.
     */
    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $user = $request->user();

        $validated = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')
                    ->where('user_id', $user->id)
                    ->ignore($vehicle->id),
            ],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
        ], [
            'plate_number.required' => 'Nomor plat kendaraan wajib diisi.',
            'plate_number.unique' => 'Plat nomor ini sudah terdaftar di akun Anda.',
            'brand.required' => 'Merk motor wajib diisi.',
            'model.required' => 'Model motor wajib diisi.',
            'year.required' => 'Tahun kendaraan wajib diisi.',
        ]);

        $cleanPlate = strtoupper(preg_replace('/\s+/', ' ', trim($validated['plate_number'])));

        $vehicle->update([
            'plate_number' => $cleanPlate,
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'year' => $validated['year'],
        ]);

        return redirect()->back()->with('success', "Data motor {$cleanPlate} berhasil diperbarui.");
    }

    /**
     * Hapus kendaraan dari garasi pelanggan.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $plate = $vehicle->plate_number;
        $vehicle->delete();

        return redirect()->back()->with('success', "Kendaraan {$plate} berhasil dihapus dari garasi.");
    }
}
