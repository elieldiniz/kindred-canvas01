<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Generations\DownloadController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Products\Create;
use App\Livewire\Admin\Products\Edit;
use App\Livewire\Admin\Products\Index;
use App\Livewire\Billing\Plans;
use App\Livewire\Credits\Index as CreditsIndex;
use App\Livewire\Projects\Configurator;
use App\Livewire\Projects\Show as ProjectShow;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('projects/new', Configurator::class)->name('projects.new');
    Route::get('generations/{generation}/download', [DownloadController::class, 'download'])->name('generations.download');
    Route::get('projects/{project}', ProjectShow::class)->name('projects.show');
    Route::livewire('credits', CreditsIndex::class)->name('credits.index');
    Route::livewire('billing', App\Livewire\Billing\Index::class)->name('billing.index');
    Route::livewire('billing/plans', Plans::class)->name('billing.plans.index');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/', AdminDashboard::class)->name('dashboard');
        Route::livewire('products', Index::class)->name('products.index');
        Route::livewire('plans', App\Livewire\Admin\Plans\Index::class)->name('plans.index');
        Route::livewire('plans/create', App\Livewire\Admin\Plans\Create::class)->name('plans.create');
        Route::livewire('plans/{plan}/edit', App\Livewire\Admin\Plans\Edit::class)->name('plans.edit');
        Route::livewire('subscriptions', App\Livewire\Admin\Subscriptions\Index::class)->name('subscriptions.index');
        Route::livewire('products/create', Create::class)->name('products.create');
        Route::livewire('products/{product}/edit', Edit::class)->name('products.edit');
        Route::livewire('categories', App\Livewire\Admin\Categories\Index::class)->name('categories.index');
        Route::livewire('categories/create', App\Livewire\Admin\Categories\Create::class)->name('categories.create');
        Route::livewire('categories/{category}/edit', App\Livewire\Admin\Categories\Edit::class)->name('categories.edit');
        Route::livewire('styles', App\Livewire\Admin\Styles\Index::class)->name('styles.index');
        Route::livewire('styles/create', App\Livewire\Admin\Styles\Create::class)->name('styles.create');
        Route::livewire('styles/{style}/edit', App\Livewire\Admin\Styles\Edit::class)->name('styles.edit');
        Route::livewire('layouts', App\Livewire\Admin\Layouts\Index::class)->name('layouts.index');
        Route::livewire('layouts/create', App\Livewire\Admin\Layouts\Create::class)->name('layouts.create');
        Route::livewire('layouts/{layout}/edit', App\Livewire\Admin\Layouts\Edit::class)->name('layouts.edit');
        Route::livewire('prompt-templates', App\Livewire\Admin\PromptTemplates\Index::class)->name('prompt-templates.index');
        Route::livewire('prompt-templates/create', App\Livewire\Admin\PromptTemplates\Create::class)->name('prompt-templates.create');
        Route::livewire('prompt-templates/{template}/edit', App\Livewire\Admin\PromptTemplates\Edit::class)->name('prompt-templates.edit');
        Route::livewire('users', App\Livewire\Admin\Users\Index::class)->name('users.index');
        Route::livewire('audit-log', App\Livewire\Admin\AuditLog\Index::class)->name('audit-log.index');
    });
});

Route::get('auth/{provider}', [OAuthController::class, 'redirect'])->name('auth.oauth.redirect');
Route::get('auth/{provider}/callback', [OAuthController::class, 'callback'])->name('auth.oauth.callback');

Route::post('stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware(VerifyWebhookSignature::class);

require __DIR__.'/settings.php';
