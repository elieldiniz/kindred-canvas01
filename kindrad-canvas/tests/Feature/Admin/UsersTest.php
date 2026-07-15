<?php

use App\Livewire\Admin\Users\Index;
use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    CreditTransactionReason::firstOrCreate(
        ['slug' => 'admin_grant'],
        ['name' => 'Admin Grant', 'expected_sign' => '+'],
    );
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create(['credit_balance' => 10]);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.users.index'))
        ->assertForbidden();
});

it('lists all users', function (): void {
    $this->actingAs($this->admin)->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($this->user->email)
        ->assertSee('admin-users-index');
});

it('grants credits via modal and writes a ledger row', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openGrantModal', $this->user->id)
        ->assertSet('grantUserId', $this->user->id)
        ->assertSet('showGrantModal', true)
        ->set('grantAmount', 25)
        ->set('grantNotes', 'Compensation for failed generation')
        ->call('grant')
        ->assertHasNoErrors();

    $this->user->refresh();
    expect($this->user->credit_balance)->toBe(35);

    $tx = CreditTransaction::query()
        ->where('user_id', $this->user->id)
        ->where('delta', 25)
        ->first();
    expect($tx)->not->toBeNull();
    expect($tx->notes)->toBe('Compensation for failed generation');
});

it('validates grant amount is positive', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openGrantModal', $this->user->id)
        ->set('grantAmount', 0)
        ->set('grantNotes', 'no credits')
        ->call('grant')
        ->assertHasErrors(['grantAmount']);
});

it('validates notes are required', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openGrantModal', $this->user->id)
        ->set('grantAmount', 10)
        ->set('grantNotes', '')
        ->call('grant')
        ->assertHasErrors(['grantNotes']);
});

it('toggles admin status on another user', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('toggleAdmin', $this->user->id);

    $this->user->refresh();
    expect($this->user->is_admin)->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('toggleAdmin', $this->user->id);

    $this->user->refresh();
    expect($this->user->is_admin)->toBeFalse();
});

it('prevents self-demotion from admin', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('toggleAdmin', $this->admin->id)
        ->assertHasErrors(['toggleAdmin']);

    $this->admin->refresh();
    expect($this->admin->is_admin)->toBeTrue();
});
