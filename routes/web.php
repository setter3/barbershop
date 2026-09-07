<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\BarberController;
use App\Http\Controllers\Admin\BarberTimeOffController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Booking\AvailabilityController;
use App\Http\Controllers\Booking\BookingPageController;
use App\Http\Controllers\Booking\HoldController;
use App\Http\Controllers\Booking\ShowReservationController;
use App\Services\Booking\BusinessSettings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['usesOnlineDeposit' => app(BusinessSettings::class)->usesOnlineDeposit()]);
})->name('home');

Route::prefix('booking')->name('booking.')->group(function (): void {
    Route::get('/', BookingPageController::class)->name('create');
    Route::get('/availability', AvailabilityController::class)->name('availability');
    Route::post('/holds', HoldController::class)->name('holds.store');
    Route::get('/{reservation}', ShowReservationController::class)->name('show');
});

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('barbers', BarberController::class)->except(['show', 'destroy']);
    Route::post('barbers/{barber}/time-offs', [BarberTimeOffController::class, 'store'])->name('barbers.time-offs.store');
    Route::delete('barbers/{barber}/time-offs/{timeOff}', [BarberTimeOffController::class, 'destroy'])->name('barbers.time-offs.destroy');
    Route::resource('services', ServiceController::class)->except(['show', 'destroy']);
    Route::resource('reservations', ReservationController::class)->only(['index', 'show', 'update']);
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
