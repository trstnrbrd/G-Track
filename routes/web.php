<?php

use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CapitalController;
use App\Http\Controllers\DailySessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopSettingsController;
use App\Http\Controllers\StoreSaleController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserManagementController;
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

Route::get('/store', [StoreSaleController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('store');

// Owner-only: hiding the nav link isn't access control, the `owner` middleware is.
Route::middleware(['auth', 'verified', 'owner'])->group(function () {
    Route::get('/capital', [CapitalController::class, 'index'])->name('capital');

    Route::get('/user-management', [UserManagementController::class, 'index'])->name('user-management');
    Route::post('/user-management', [UserManagementController::class, 'store'])->name('user-management.store');
    Route::post('/user-management/{user}/password', [UserManagementController::class, 'resetPassword'])->name('user-management.password');

    // The owner's shop settings and backup (Settings page)
    Route::put('/settings/shop/rates', [ShopSettingsController::class, 'updateRates'])->name('settings.shop.rates');
    Route::put('/settings/shop/quick-amounts', [ShopSettingsController::class, 'updateQuickAmounts'])->name('settings.shop.quick-amounts');
    Route::put('/settings/shop/auto-close', [ShopSettingsController::class, 'updateAutoClose'])->name('settings.shop.auto-close');
    Route::delete('/settings/shop/{setting}', [ShopSettingsController::class, 'reset'])->name('settings.shop.reset');
    Route::get('/settings/backup', [ShopSettingsController::class, 'downloadBackup'])->name('settings.backup');
});

Route::get('/settings', [SettingsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::middleware('auth')->group(function () {
    // Daily session (Start Day / End Day)
    Route::post('/day/start', [DailySessionController::class, 'start'])->name('day.start');
    Route::post('/day/end', [DailySessionController::class, 'end'])->name('day.end');

    // Recording a cash in / cash out
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

    // Fill in / correct the number and reference after the fact (money fields are locked)
    Route::patch('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');

    // A store item paid via GCash: cash moves from the GCash drawer to the tindahan
    Route::post('/store/sales', [StoreSaleController::class, 'store'])->name('store.sales.store');

    // Capital (puhunan) / debt (utang) ledger: borrow, repay, add capital
    Route::post('/capital/entries', [CapitalController::class, 'store'])->middleware('owner')->name('capital.entries.store');

    // CSV exports (respect whatever filters the page currently has applied)
    Route::get('/export/transactions', [ExportController::class, 'transactions'])->name('export.transactions');
    Route::get('/export/sessions', [ExportController::class, 'sessions'])->name('export.sessions');

    // My own settings: name / email, and signing out of other devices
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/sign-out-others', [SettingsController::class, 'signOutOtherDevices'])->name('settings.sign-out-others');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
