<?php

use App\Http\Controllers\DailySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/history', function () {
    return view('history');
})->middleware(['auth', 'verified'])->name('history');

Route::get('/balance', function () {
    return view('balance');
})->middleware(['auth', 'verified'])->name('balance');

Route::get('/user-management', function () {
    return view('user-management');
})->middleware(['auth', 'verified'])->name('user-management');

Route::middleware('auth')->group(function () {
    // Daily session (Start Day / End Day)
    Route::post('/day/start', [DailySessionController::class, 'start'])->name('day.start');
    Route::post('/day/end', [DailySessionController::class, 'end'])->name('day.end');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
