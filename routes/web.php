<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'admin-dashboard')->name('dashboard');
    Route::livewire('rangers', 'rangers-dashboard')->name('rangers');
});

require __DIR__.'/settings.php';
