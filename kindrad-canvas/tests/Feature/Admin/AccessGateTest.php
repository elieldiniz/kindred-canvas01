<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to login from the admin dashboard', function (): void {
    get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('non admin users get 403 from the admin dashboard', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('non admin users see no admin link in the user sidebar', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSee('data-test="sidebar-admin-link"', false);
});

test('admin users can access the admin dashboard', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="admin-dashboard-page"', false)
        ->assertSee('data-test="admin-sidebar"', false)
        ->assertSee('data-test="admin-topbar"', false);
});

test('admin users see the admin link in the user sidebar', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="sidebar-admin-link"', false)
        ->assertSee('href="'.route('admin.dashboard').'"', false);
});

test('admin dashboard renders metrics tiles', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(3)->create(['credit_balance' => 5]);

    actingAs($admin);

    Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSee('data-test="admin-metrics-grid"', false)
        ->assertSee('data-test="admin-metric-users"', false)
        ->assertSee('data-test="admin-metric-generations"', false)
        ->assertSee('data-test="admin-metric-credits"', false)
        ->assertSee('Total users')
        ->assertSee('Total generations')
        ->assertSee('Credits in circulation');
});

test('admin sidebar lists catalog sections as disabled placeholders', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="admin-nav-dashboard"', false)
        ->assertSee('Products')
        ->assertSee('Categories')
        ->assertSee('Styles')
        ->assertSee('Prompt templates')
        ->assertSee('Audit log');
});

test('admin dashboard surfaces a link card to the dedicated audit log page', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-test="admin-audit-log"', false)
        ->assertSee('data-test="admin-audit-go"', false)
        ->assertSee('Open audit log', false);
});
