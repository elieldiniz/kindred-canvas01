<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows the welcome hero and credit balance', function () {
    $user = User::factory()->create([
        'name' => 'Ada Lovelace',
        'credit_balance' => 7,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Ada Lovelace.')
        ->assertSee('data-test="dashboard-credits-card"', false)
        ->assertSee('7 credits');
});

test('dashboard shows the out-of-credits progress when balance is zero', function () {
    $user = User::factory()->create([
        'credit_balance' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('0 credits');
});

test('dashboard links to the credits history page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('credits.index').'"', false);
});

test('dashboard renders the stats bento grid with all three tiles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-stats-grid"', false)
        ->assertSee('data-test="dashboard-stat-generations"', false)
        ->assertSee('data-test="dashboard-stat-popular-style"', false);
});

test('dashboard renders empty state for the recent projects section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="dashboard-recent-empty"', false)
        ->assertSee('No projects yet');
});
