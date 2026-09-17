<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('goals', 'pages::goals.index')->name('goals.index');
    Route::livewire('goals/create', 'pages::goals.create')->name('goals.create');
    Route::livewire('goals/{goal}/edit', 'pages::goals.edit')->name('goals.edit');
    Route::livewire('goals/{goal}', 'pages::goals.show')->name('goals.show');
});

require __DIR__.'/settings.php';
