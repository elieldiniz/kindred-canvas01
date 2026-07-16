<?php

use App\Livewire\Admin\Users\Index;
use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        ->assertSee('admin-users-table', false);
});

it('grants credits via the settings modal and writes a ledger row', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->assertSet('targetUserId', $this->user->id)
        ->assertSet('showSettingsModal', true)
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

it('validates grant amount is positive in the settings modal', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->set('grantAmount', 0)
        ->set('grantNotes', 'no credits')
        ->call('grant')
        ->assertHasErrors(['grantAmount']);
});

it('validates notes are required in the settings modal', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->set('grantAmount', 10)
        ->set('grantNotes', '')
        ->call('grant')
        ->assertHasErrors(['grantNotes']);
});

it('toggles admin status on another user via the settings modal', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->assertSet('targetUserId', $this->user->id)
        ->assertSet('showSettingsModal', true)
        ->call('toggleAdmin');

    $this->user->refresh();
    expect($this->user->is_admin)->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->call('toggleAdmin');

    $this->user->refresh();
    expect($this->user->is_admin)->toBeFalse();
});

it('prevents self-demotion from admin via the settings modal', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->admin->id)
        ->call('toggleAdmin')
        ->assertHasErrors(['toggleAdmin']);

    $this->admin->refresh();
    expect($this->admin->is_admin)->toBeTrue();
});

it('renders the base metrics dashboard card always and conditional counters when non-zero', function (): void {
    User::factory()->admin()->create();
    User::factory()->create(['is_suspended' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-test="admin-users-metrics"', false)
        ->assertSee('data-test="admin-users-metric-total"', false)
        ->assertSee('data-test="admin-users-metric-active"', false)
        // admins=1, total=4 (admin + suspended + 2 from beforeEach) → partial, card visible
        ->assertSee('data-test="admin-users-metric-admins"', false)
        // Suspended > 0, card visible
        ->assertSee('data-test="admin-users-metric-suspended"', false)
        // Conditional cards absent because the data is zero:
        ->assertDontSee('data-test="admin-users-metric-deleted"', false)
        ->assertDontSee('data-test="admin-users-metric-past-due"', false)
        ->assertDontSee('data-test="admin-users-metric-active-subscription"', false);
});

it('excludes suspended users from the active counter', function (): void {
    // 5 active users + 1 suspended: active should be 5, suspended 1.
    User::factory()->count(5)->create();
    User::factory()->create(['is_suspended' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-test="admin-users-metric-total"', false)
        ->assertSee('data-test="admin-users-metric-active"', false)
        ->assertSee('data-test="admin-users-metric-suspended"', false);
    // Numeric values are validated by the per-card tests below.
});

it('computes the right metric counts: total/active/suspended/admins', function (): void {
    // beforeEach: 1 admin + 1 user (credit_balance=10). Total=2 (currently).
    User::factory()->count(3)->create();                              // 3 more regular users
    User::factory()->create(['is_suspended' => true]);                // 1 suspended
    User::factory()->count(2)->create(['is_admin' => true]);          // 2 more admins

    $component = Livewire::actingAs($this->admin)->test(Index::class);

    $metrics = $component->viewData('metrics');
    expect($metrics)
        ->toHaveKey('total', 8)
        ->toHaveKey('active', 7)            // 8 total minus 1 suspended; includes admins
        ->toHaveKey('deleted', 0)
        ->toHaveKey('admins', 3)            // admin + 2 new
        ->toHaveKey('suspended', 1);
});

it('renders the soft-deleted card only when count is greater than zero', function (): void {
    // baseline: no soft-deleted users → card hidden
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('data-test="admin-users-metric-deleted"', false);

    // after deleting a user → card visible
    $victim = User::factory()->create();
    $victim->delete();

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-test="admin-users-metric-deleted"', false);
});

it('hides the admins card when all users are admins or none are', function (): void {
    // Case A: all users become admins (admins === total) → card hidden.
    User::query()->update(['is_admin' => true]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('data-test="admin-users-metric-admins"', false);

    // Case B: mixed (admin + non-admin) → card visible.
    User::factory()->create(['is_admin' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-test="admin-users-metric-admins"', false);

    // Case C: zero admins (no `is_admin = true`) → card hidden.
    User::query()->update(['is_admin' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('data-test="admin-users-metric-admins"', false);
});

it('suspends a user from the settings modal and writes an audit log', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->call('suspend')
        ->assertHasNoErrors();

    $this->user->refresh();
    expect($this->user->is_suspended)->toBeTrue();

    $log = AuditLog::query()->where('actor_user_id', $this->admin->id)->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->action?->slug)->toBe('suspend_user')
        ->and($log->target_id)->toBe($this->user->id);
});

it('unsuspends a previously suspended user from the settings modal', function (): void {
    $this->user->update(['is_suspended' => true]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->call('unsuspend')
        ->assertHasNoErrors();

    $this->user->refresh();
    expect($this->user->is_suspended)->toBeFalse();
});

it('sets a user-supplied password after admin types in the new value and submits', function (): void {
    $oldHash = $this->user->password;
    $newPassword = 'NewSecret-2026!';

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->set('newPassword', $newPassword)
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertSet('newPassword', '');

    $this->user->refresh();
    expect($this->user->password)->not->toBe($oldHash)
        ->and(Hash::check($newPassword, $this->user->password))->toBeTrue();

    $log = AuditLog::query()
        ->where('action_id', AuditLogAction::where('slug', 'password_reset_by_admin')->value('id'))
        ->where('target_id', $this->user->id)
        ->first();
    expect($log)->not->toBeNull()
        ->and($log->payload['method'] ?? null)->toBe('admin_supplied');
});

it('fills the password input via generatePassword without persisting', function (): void {
    $oldHash = $this->user->password;

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->call('generatePassword')
        ->assertSet('newPassword', function (string $value): bool {
            return strlen($value) >= 12;
        });

    // Persistence only happens after explicit resetPassword submit.
    $this->user->refresh();
    expect($this->user->password)->toBe($oldHash);
});

it('cancels a pending password reset by clearing the input and validation state', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->set('newPassword', 'abc')            // too short
        ->call('resetPassword')
        ->assertHasErrors(['newPassword'])
        ->call('cancelPasswordReset')
        ->assertSet('newPassword', '')
        ->assertHasNoErrors(['newPassword']);
});

it('validates the new password is at least 8 characters', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->set('newPassword', 'short')
        ->call('resetPassword')
        ->assertHasErrors(['newPassword']);
});

it('rejects login for a suspended user via Fortify', function (): void {
    $this->user->update(['is_suspended' => true, 'password' => bcrypt('password')]);

    $response = $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

it('shows the suspend/reactivate control based on the current state', function (): void {
    // Active → Suspender button visible
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->assertSee('Suspender')
        ->assertDontSee('Reactivate');

    // Suspended → Reactivate button visible
    $this->user->update(['is_suspended' => true]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openSettings', $this->user->id)
        ->assertSee('Reactivate')
        ->assertDontSee('Suspender');
});
