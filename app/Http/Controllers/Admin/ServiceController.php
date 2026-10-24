<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Tampilkan daftar master layanan servis bengkel dengan pencarian, pengurutan, dan paginasi.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);

        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $validSorts = ['name', 'duration_minutes', 'price_estimate', 'is_active', 'created_at'];
        if (! in_array($sortBy, $validSorts)) {
            $sortBy = 'created_at';
        }
        $sortDirection = $sortDirection === 'asc' ? 'asc' : 'desc';

        $services = Service::query()
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price_estimate' => $service->price_estimate,
                'formatted_price' => $service->formatted_price,
                'reminder_interval_days' => $service->reminder_interval_days,
                'is_active' => (bool) $service->is_active,
                'has_bookings' => $service->bookings()->exists(),
                'created_at' => $service->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Simpan layanan servis baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price_estimate' => ['required', 'integer', 'min:0'],
            'reminder_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama layanan wajib diisi.',
            'duration_minutes.required' => 'Durasi pengerjaan wajib diisi.',
            'duration_minutes.min' => 'Durasi pengerjaan minimal 5 menit.',
            'duration_minutes.max' => 'Durasi pengerjaan maksimal 480 menit (8 jam).',
            'price_estimate.required' => 'Estimasi biaya wajib diisi.',
            'price_estimate.min' => 'Estimasi biaya tidak boleh bernilai negatif.',
        ]);

        Service::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'price_estimate' => $validated['price_estimate'],
            'reminder_interval_days' => $validated['reminder_interval_days'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil ditambahkan.');
    }

    /**
     * Perbarui data layanan servis yang sudah ada.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price_estimate' => ['required', 'integer', 'min:0'],
            'reminder_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama layanan wajib diisi.',
            'duration_minutes.required' => 'Durasi pengerjaan wajib diisi.',
            'price_estimate.required' => 'Estimasi biaya wajib diisi.',
        ]);

        $service->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'price_estimate' => $validated['price_estimate'],
            'reminder_interval_days' => $validated['reminder_interval_days'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Toggle status aktif/nonaktif layanan secara cepat.
     */
    public function toggleActive(Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        $service->update([
            'is_active' => ! $service->is_active,
        ]);

        $statusText = $service->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Layanan '{$service->name}' berhasil {$statusText}.");
    }

    /**
     * Hapus layanan servis.
     * Jika layanan sudah memiliki relasi booking, lakukan soft delete agar riwayat tidak rusak.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        if ($service->bookings()->exists()) {
            $service->delete();

            return redirect()->route('admin.services.index')->with(
                'success',
                "Layanan '{$service->name}' berhasil diarsipkan (soft delete) karena memiliki riwayat booking."
            );
        }

        $service->forceDelete();

        return redirect()->route('admin.services.index')->with(
            'success',
            "Layanan '{$service->name}' berhasil dihapus secara permanen."
        );
    }
}
