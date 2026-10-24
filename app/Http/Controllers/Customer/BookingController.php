<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    /**
     * Tampilkan daftar "Booking Saya" milik pelanggan.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $user = $request->user();
        $statusFilter = $request->query('status'); // 'active', 'completed', 'cancelled'

        $query = $user->bookings()
            ->with(['vehicle', 'service'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('queue_order', 'asc');

        if ($statusFilter === 'active') {
            $query->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::CheckedIn->value,
                BookingStatus::InProgress->value,
            ]);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', BookingStatus::Completed->value);
        } elseif ($statusFilter === 'cancelled') {
            $query->where('status', BookingStatus::Cancelled->value);
        }

        $bookings = $query->paginate(10)->withQueryString();

        // Statistik ringkas untuk tab filter
        $counts = [
            'all' => $user->bookings()->count(),
            'active' => $user->bookings()->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::CheckedIn->value,
                BookingStatus::InProgress->value,
            ])->count(),
            'completed' => $user->bookings()->where('status', BookingStatus::Completed->value)->count(),
            'cancelled' => $user->bookings()->where('status', BookingStatus::Cancelled->value)->count(),
        ];

        return Inertia::render('Customer/Bookings/Index', [
            'bookings' => $bookings,
            'counts' => $counts,
            'current_filter' => $statusFilter ?? 'all',
            'cancellation_threshold_hours' => (int) Setting::get('cancellation_threshold_hours', 2),
        ]);
    }

    /**
     * Tampilkan detail lengkap pemesanan servis beserta riwayat status dan timeline.
     */
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load([
            'vehicle',
            'service',
            'statusLogs.changedBy:id,name,role',
            'notificationLogs',
        ]);

        $cancellationThresholdHours = (int) Setting::get('cancellation_threshold_hours', 2);
        $bookingDateTime = Carbon::parse("{$booking->booking_date->toDateString()} {$booking->estimated_arrival_at}", 'Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');

        // Batal hanya bisa jika status Confirmed dan masih sebelum batas waktu toleransi
        $hoursRemaining = $now->diffInHours($bookingDateTime, false);
        $canCancel = ($booking->status === BookingStatus::Confirmed) && ($hoursRemaining >= $cancellationThresholdHours);

        return Inertia::render('Customer/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'booking_date' => $booking->booking_date->toDateString(),
                'booking_date_formatted' => $booking->booking_date->locale('id')->isoFormat('dddd, D MMMM Y'),
                'queue_order' => $booking->queue_order,
                'estimated_arrival_at' => substr((string) $booking->estimated_arrival_at, 0, 5).' WIB',
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'status_color' => $booking->status->color(),
                'complaint' => $booking->complaint,
                'cancel_reason' => $booking->cancel_reason,
                'checked_in_at' => $booking->checked_in_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'started_at' => $booking->started_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'finished_at' => $booking->finished_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'cancelled_at' => $booking->cancelled_at?->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'vehicle' => $booking->vehicle ? [
                    'id' => $booking->vehicle->id,
                    'plate_number' => $booking->vehicle->plate_number,
                    'brand' => $booking->vehicle->brand,
                    'model' => $booking->vehicle->model,
                    'year' => $booking->vehicle->year,
                ] : null,
                'service' => $booking->service ? [
                    'id' => $booking->service->id,
                    'name' => $booking->service->name,
                    'description' => $booking->service->description,
                    'duration_minutes' => $booking->service->duration_minutes,
                    'price_estimate' => $booking->service->price_estimate,
                ] : null,
                'timeline' => $booking->statusLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'from_status' => $log->from_status,
                    'to_status' => $log->to_status,
                    'to_status_label' => $log->to_status ? BookingStatus::tryFrom($log->to_status)?->label() : 'Dibuat',
                    'note' => $log->note,
                    'actor_name' => $log->changedBy?->name ?? 'Sistem Reservasi',
                    'created_at' => $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB',
                ]),
                'notifications' => $booking->notificationLogs->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'phone' => $n->phone,
                    'message' => $n->message,
                    'sent_at' => $n->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB',
                ]),
            ],
            'can_cancel' => $canCancel,
            'cancellation_threshold_hours' => $cancellationThresholdHours,
            'workshop_info' => [
                'name' => Setting::get('workshop_name', 'ALDI-MOTOR'),
                'phone' => Setting::get('workshop_phone', '6281234567890'),
                'address' => Setting::get('workshop_address', 'Jl. Raya Otomotif No. 88, RT.02/RW.05, Jakarta'),
            ],
        ]);
    }

    /**
     * Batalkan pemesanan servis oleh pelanggan (hanya status terkonfirmasi).
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancel_reason.min' => 'Alasan pembatalan minimal 3 karakter.',
            'cancel_reason.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ]);

        // Cek ambang batas jam pembatalan
        $cancellationThresholdHours = (int) Setting::get('cancellation_threshold_hours', 2);
        $bookingDateTime = Carbon::parse("{$booking->booking_date->toDateString()} {$booking->estimated_arrival_at}", 'Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');

        if ($now->diffInHours($bookingDateTime, false) < $cancellationThresholdHours) {
            throw ValidationException::withMessages([
                'cancel_reason' => "Pembatalan mandiri tidak dapat dilakukan kurang dari {$cancellationThresholdHours} jam sebelum jadwal servis. Silakan hubungi bengkel langsung.",
            ]);
        }

        // Jalankan transisi status ke Cancelled secara aman
        $booking->transitionTo(
            BookingStatus::Cancelled,
            $request->user()->id,
            $validated['cancel_reason']
        );

        return redirect()->back()->with(
            'success',
            "Pemesanan {$booking->booking_code} telah dibatalkan. Slot antrean telah dibebaskan kembali."
        );
    }
}
