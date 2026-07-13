<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/_test/admin-only', fn () => response()->json(['ok' => true]))
        ->middleware(['web', 'auth', 'admin']);
});

test('admin user can access a route guarded by the admin middleware', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/_test/admin-only')
        ->assertSuccessful();
});

test('non admin user is forbidden by the admin middleware', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/_test/admin-only')
        ->assertForbidden();
});

test('guest is redirected to login by the auth middleware', function (): void {
    $this->get('/_test/admin-only')
        ->assertRedirect(route('login'));
});
