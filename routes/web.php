<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DailySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/history', [HistoryController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('history');

Route::get('/balance', [BalanceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('balance');

Route::middleware('auth')->group(function () {
    // Daily session (Start Day / End Day)
    Route::post('/day/start', [DailySessionController::class, 'start'])->name('day.start');
    Route::post('/day/end', [DailySessionController::class, 'end'])->name('day.end');

    // Recording a cash in / cash out
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

    // CSV exports (respect whatever filters the page currently has applied)
    Route::get('/export/transactions', [ExportController::class, 'transactions'])->name('export.transactions');
    Route::get('/export/sessions', [ExportController::class, 'sessions'])->name('export.sessions');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
