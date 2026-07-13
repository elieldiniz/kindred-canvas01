<?php

use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Auth\Events\Registered;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('dispatching Registered grants the user five credits', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'credit_balance' => 0,
    ]);

    event(new Registered($user));

    $user->refresh();

    expect($user->credit_balance)->toBe(5);

    $row = CreditTransaction::where('user_id', $user->id)->firstOrFail();

    expect($row->delta)->toBe(5);
    expect($row->balance_after)->toBe(5);
    expect($row->reason->slug)->toBe('signup_grant');
    expect($row->notes)->toBeNull();
});

test('dispatching Registered twice does not grant credits twice', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'credit_balance' => 0,
    ]);

    event(new Registered($user));
    event(new Registered($user->fresh()));

    $user->refresh();

    expect($user->credit_balance)->toBe(5);
    expect(CreditTransaction::where('user_id', $user->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'signup_grant')->value('id'))
        ->count())->toBe(1);
});
