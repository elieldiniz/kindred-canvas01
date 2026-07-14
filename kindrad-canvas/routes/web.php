<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Generations\DownloadController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Credits\Index as CreditsIndex;
use App\Livewire\Projects\Configurator;
use App\Livewire\Projects\Show as ProjectShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('projects/new', Configurator::class)->name('projects.new');
    Route::get('generations/{generation}/download', [DownloadController::class, 'download'])->name('generations.download');
    Route::get('projects/{project}', ProjectShow::class)->name('projects.show');
    Route::livewire('credits', CreditsIndex::class)->name('credits.index');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/', AdminDashboard::class)->name('dashboard');
        Route::livewire('products', \App\Livewire\Admin\Products\Index::class)->name('products.index');
        Route::livewire('products/create', \App\Livewire\Admin\Products\Create::class)->name('products.create');
        Route::livewire('products/{product}/edit', \App\Livewire\Admin\Products\Edit::class)->name('products.edit');
    });
});

Route::get('auth/{provider}', [OAuthController::class, 'redirect'])->name('auth.oauth.redirect');
Route::get('auth/{provider}/callback', [OAuthController::class, 'callback'])->name('auth.oauth.callback');

require __DIR__.'/settings.php';
