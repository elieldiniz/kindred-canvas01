<?php

use App\Livewire\Projects\Wizard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('projects/new', Wizard::class)->name('projects.new');
    Route::livewire('projects/{project}', 'projects::show')->name('projects.show');
});

require __DIR__.'/settings.php';
