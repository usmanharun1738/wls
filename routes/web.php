<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// --- Admin routes ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'admin-dashboard')->name('dashboard');
    Route::livewire('rangers', 'rangers-dashboard')->name('rangers');
});

// --- Ranger routes (phone + PIN auth) ---
Route::livewire('ranger/login', 'ranger-login')->name('ranger.login');

Route::middleware(['ranger.auth'])->group(function () {
    Route::livewire('ranger/dashboard', 'ranger-dashboard')->name('ranger.dashboard');
});

require __DIR__.'/settings.php';
