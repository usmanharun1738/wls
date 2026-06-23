<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RangerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\UssdController;
use Illuminate\Support\Facades\Route;

// --- Public callback endpoints for Africa's Talking ---
Route::post('/ussd/callback', [UssdController::class, 'callback'])
    ->withoutMiddleware('csrf');
Route::post('/sms/callback', [UssdController::class, 'smsCallback'])
    ->withoutMiddleware('csrf');
Route::post('/airtime/validation', [UssdController::class, 'airtimeValidation'])
    ->withoutMiddleware('csrf');
Route::post('/airtime/status', [UssdController::class, 'airtimeStatus'])
    ->withoutMiddleware('csrf');

// --- Admin protected routes ---
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('api.admin.reports.index');
    Route::post('/reports/{report}/verify', [ReportController::class, 'verify'])->name('api.admin.reports.verify');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('api.admin.reports.reject');
    Route::get('/rangers', [RangerController::class, 'index'])->name('api.admin.rangers.index');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('api.admin.dashboard.stats');
});
