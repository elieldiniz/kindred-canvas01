<?php

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Hash;

it('creates an admin user with the default credentials', function (): void {
    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'admin@kindred.local')->first();

    expect($admin)->not->toBeNull();
    expect($admin->is_admin)->toBeTrue();
    expect($admin->email_verified_at)->not->toBeNull();
    expect($admin->credit_balance)->toBe(0);
    expect(Hash::check('password', $admin->password))->toBeTrue();
});

it('promotes an existing user to admin instead of duplicating', function (): void {
    $existing = User::factory()->create([
        'email' => 'admin@kindred.local',
        'is_admin' => false,
    ]);

    $this->seed(AdminSeeder::class);

    expect(User::where('email', 'admin@kindred.local')->count())->toBe(1);
    expect($existing->fresh()->is_admin)->toBeTrue();
});

it('honors custom admin credentials from config', function (): void {
    config()->set('app.admin_email', 'custom@example.com');
    config()->set('app.admin_password', 'secret123');

    $this->seed(AdminSeeder::class);

    $admin = User::where('email', 'custom@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->is_admin)->toBeTrue();
    expect(Hash::check('secret123', $admin->password))->toBeTrue();
});
