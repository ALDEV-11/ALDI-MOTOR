<?php

use App\Http\Controllers\Admin\CapacityController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Customer\VehicleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicReservationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - ALDI-MOTOR
|--------------------------------------------------------------------------
*/

// Halaman Beranda Publik
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Rute Tamu (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Rute Pengguna Terautentikasi (Umum)
Route::middleware('auth')->group(function () {
    // Pengalihan umum /dashboard sesuai peran
    Route::get('/dashboard', function () {
        if (request()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('customer.dashboard');
    })->name('dashboard');

    // Pengaturan Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Rute Khusus Administrator Bengkel (FASE 6: Layanan, Pengaturan, & Kapasitas)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // 1. Master Layanan Servis
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggleActive'])->name('services.toggle');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // 2. Pengaturan Bengkel
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // 3. Kalender Kapasitas & Operasional
    Route::get('/capacities', [CapacityController::class, 'index'])->name('capacities.index');
    Route::post('/capacities', [CapacityController::class, 'update'])->name('capacities.update');
});

// Rute Publik Portal Reservasi
Route::get('/reservasi', [PublicReservationController::class, 'index'])->name('reservations.index');
Route::post('/reservasi', [PublicReservationController::class, 'store'])->middleware('auth')->name('reservations.store');

// Rute Khusus Pelanggan
Route::middleware(['auth', 'role:customer'])->prefix('beranda')->name('customer.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Customer/Dashboard');
    })->name('dashboard');

    // CRUD Kendaraan Pelanggan
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    // Daftar "Booking Saya", Detail, dan Pembatalan
    Route::get('/bookings', [CustomerBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [CustomerBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('bookings.cancel');
});
