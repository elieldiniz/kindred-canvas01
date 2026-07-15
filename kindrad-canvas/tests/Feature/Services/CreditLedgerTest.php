<?php

use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\User;
use App\Services\CreditLedger;
use App\Services\Exceptions\CreditInsufficientException;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('signup grant writes a ledger row and updates balance', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);

    $row = app(CreditLedger::class)->signupGrant($user, 5);

    expect($row->reason->slug)->toBe('signup_grant');
    expect($row->delta)->toBe(5);
    expect($row->balance_after)->toBe(5);

    expect($user->fresh()->credit_balance)->toBe(5);
    expect(CreditTransaction::where('user_id', $user->id)->count())->toBe(1);
});

test('signup grant is idempotent and returns the existing row', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);

    $first = app(CreditLedger::class)->signupGrant($user, 5);
    $second = app(CreditLedger::class)->signupGrant($user, 5);

    expect($second->id)->toBe($first->id);
    expect($user->fresh()->credit_balance)->toBe(5);
    expect(CreditTransaction::where('user_id', $user->id)->count())->toBe(1);
});

test('debit writes a negative ledger row and decrements the balance', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    $row = app(CreditLedger::class)->debit($user, 1, $generation);

    expect($row->delta)->toBe(-1);
    expect($row->balance_after)->toBe(4);
    expect($row->reason->slug)->toBe('generation_debit');
    expect($row->reference_type)->toBe(Generation::class);
    expect($row->reference_id)->toBe($generation->id);

    expect($user->fresh()->credit_balance)->toBe(4);
});

test('refund increments balance and writes a positive ledger row', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    app(CreditLedger::class)->debit($user, 1, $generation);

    $refund = app(CreditLedger::class)->refund($generation, 'provider timeout');

    expect($refund->delta)->toBe(1);
    expect($refund->balance_after)->toBe(5);
    expect($refund->reason->slug)->toBe('generation_refund');
    expect($refund->notes)->toBe('provider timeout');
    expect($refund->reference_id)->toBe($generation->id);

    expect($user->fresh()->credit_balance)->toBe(5);
});

test('refund is idempotent for the same generation', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    app(CreditLedger::class)->debit($user, 1, $generation);
    $first = app(CreditLedger::class)->refund($generation, 'first reason');
    $second = app(CreditLedger::class)->refund($generation, 'second reason');

    expect($second->id)->toBe($first->id);
    expect($user->fresh()->credit_balance)->toBe(5);
    expect(CreditTransaction::where('user_id', $user->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'generation_refund')->value('id'))
        ->count())->toBe(1);
});

test('admin grant stores notes and increments the balance', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);
    $admin = User::factory()->admin()->create();

    $row = app(CreditLedger::class)->adminGrant($user, 10, $admin, 'PR-1234 promo');

    expect($row->delta)->toBe(10);
    expect($row->balance_after)->toBe(10);
    expect($row->notes)->toBe('PR-1234 promo');
    expect($row->reason->slug)->toBe('admin_grant');
    expect($row->reference_type)->toBe(User::class);
    expect($row->reference_id)->toBe($admin->id);

    expect($user->fresh()->credit_balance)->toBe(10);
});

test('debit refuses to take a user below zero and throws', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);
    $generation = Generation::factory()->for($user)->create();

    app(CreditLedger::class)->debit($user, 1, $generation);
})->throws(CreditInsufficientException::class);

test('debit on zero balance leaves no ledger row', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);
    $generation = Generation::factory()->for($user)->create();

    try {
        app(CreditLedger::class)->debit($user, 1, $generation);
    } catch (CreditInsufficientException) {
        // expected
    }

    expect(CreditTransaction::where('user_id', $user->id)->count())->toBe(0);
    expect($user->fresh()->credit_balance)->toBe(0);
});

test('debit is idempotent for the same generation', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    $first = app(CreditLedger::class)->debit($user, 1, $generation);
    $second = app(CreditLedger::class)->debit($user, 1, $generation);

    expect($second->id)->toBe($first->id);
    expect($user->fresh()->credit_balance)->toBe(4);
    expect(CreditTransaction::where('reference_type', Generation::class)
        ->where('reference_id', $generation->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'generation_debit')->value('id'))
        ->count())->toBe(1);
});

test('debit does not double-charge when job retries after refund', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    $first = app(CreditLedger::class)->debit($user, 1, $generation);
    app(CreditLedger::class)->refund($generation, 'provider timeout');

    $retry = app(CreditLedger::class)->debit($user, 1, $generation);

    expect($retry->id)->toBe($first->id);
    expect($user->fresh()->credit_balance)->toBe(5);
});

test('debit is idempotent under concurrent lockForUpdate', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $generation = Generation::factory()->for($user)->create();

    $first = app(CreditLedger::class)->debit($user, 1, $generation);

    DB::transaction(function () use ($user, $generation, $first): void {
        $retry = app(CreditLedger::class)->debit($user, 1, $generation);
        expect($retry->id)->toBe($first->id);
    });

    expect($user->fresh()->credit_balance)->toBe(4);
});
