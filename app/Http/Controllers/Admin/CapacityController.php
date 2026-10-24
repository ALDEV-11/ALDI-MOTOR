<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DailyCapacity;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CapacityController extends Controller
{
    /**
     * Tampilkan kalender kapasitas per bulan dengan status kuota, keterisian, dan hari libur.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DailyCapacity::class);

        $monthParam = $request->input('month', now()->format('Y-m'));

        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } catch (\Exception $e) {
            $currentMonth = now()->startOfMonth();
        }

        $defaultDailyQuota = (int) Setting::get('daily_quota', 20);
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Ambil semua override kapasitas di bulan tersebut
        $capacities = DailyCapacity::whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($c) => $c->date->format('Y-m-d'));

        // Ambil semua booking aktif di bulan tersebut beserta relasinya
        $bookings = Booking::whereBetween('booking_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->with(['user', 'vehicle', 'service'])
            ->get()
            ->groupBy(fn ($b) => $b->booking_date->format('Y-m-d'));

        $days = [];
        $todayStr = now()->toDateString();
        $cursor = $startOfMonth->copy();

        while ($cursor->lte($endOfMonth)) {
            $dateStr = $cursor->toDateString();
            $override = $capacities->get($dateStr);
            $dayBookings = $bookings->get($dateStr, collect());

            $isOpen = $override ? (bool) $override->is_open : true;
            $quota = $override && $override->quota !== null ? (int) $override->quota : $defaultDailyQuota;
            $activeCount = $dayBookings->count();
            $remaining = $isOpen ? max(0, $quota - $activeCount) : 0;

            $days[] = [
                'date' => $dateStr,
                'day_number' => $cursor->day,
                'day_name' => $cursor->locale('id')->isoFormat('dddd'),
                'is_today' => $dateStr === $todayStr,
                'is_past' => $cursor->isPast() && $dateStr !== $todayStr,
                'is_open' => $isOpen,
                'quota' => $quota,
                'has_override' => $override !== null,
                'override_quota' => $override?->quota,
                'note' => $override?->note,
                'active_bookings_count' => $activeCount,
                'remaining_slots' => $remaining,
                'active_bookings' => $dayBookings->map(fn ($b) => [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'customer_name' => $b->user?->name ?? 'Pelanggan',
                    'customer_phone' => $b->user?->phone,
                    'vehicle' => $b->vehicle ? "{$b->vehicle->brand} {$b->vehicle->model} ({$b->vehicle->plate_number})" : '-',
                    'service_name' => $b->service?->name ?? 'Servis',
                    'status' => $b->status->value,
                    'status_label' => $b->status->label(),
                    'estimated_arrival_at' => $b->estimated_arrival_at,
                ])->values()->all(),
            ];

            $cursor->addDay();
        }

        return Inertia::render('Admin/Capacities/Index', [
            'month' => $currentMonth->format('Y-m'),
            'month_label' => $currentMonth->locale('id')->isoFormat('MMMM Y'),
            'prev_month' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'next_month' => $currentMonth->copy()->addMonth()->format('Y-m'),
            'default_quota' => $defaultDailyQuota,
            'days' => $days,
            'summary' => [
                'total_days' => count($days),
                'open_days' => collect($days)->where('is_open', true)->count(),
                'closed_days' => collect($days)->where('is_open', false)->count(),
                'total_active_bookings' => collect($days)->sum('active_bookings_count'),
            ],
        ]);
    }

    /**
     * Simpan pengaturan override kuota atau penutupan tanggal operasional bengkel.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage', DailyCapacity::class);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'is_open' => ['required', 'boolean'],
            'quota' => ['nullable', 'integer', 'min:0', 'max:200'],
            'note' => ['nullable', 'string', 'max:255'],
            'force_close' => ['nullable', 'boolean'],
        ]);

        $date = Carbon::parse($validated['date'])->toDateString();
        $isOpen = $request->boolean('is_open');
        $forceClose = $request->boolean('force_close');

        // PENCEGAHAN KRUSIAL: Tanggal dengan booking aktif tidak boleh ditutup tanpa persetujuan eksplisit
        if (! $isOpen) {
            $activeBookingsCount = Booking::whereDate('booking_date', $date)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->count();

            if ($activeBookingsCount > 0 && ! $forceClose) {
                throw ValidationException::withMessages([
                    'is_open' => "Tanggal {$date} memiliki {$activeBookingsCount} booking aktif. Anda tidak dapat menutup operasional tanpa mengonfirmasi tindakan darurat terhadap pelanggan terdampak.",
                ]);
            }
        }

        DailyCapacity::updateOrCreate(
            ['date' => $date],
            [
                'quota' => (isset($validated['quota']) && $validated['quota'] !== null && $validated['quota'] !== '') ? (int) $validated['quota'] : null,
                'is_open' => $isOpen,
                'note' => $validated['note'] ?? null,
            ]
        );

        $actionNote = $isOpen ? 'Kuota dan operasional berhasil diperbarui.' : 'Tanggal berhasil ditandai sebagai hari libur/tutup.';

        return redirect()->back()->with('success', $actionNote);
    }
}
