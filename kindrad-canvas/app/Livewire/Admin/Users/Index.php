<?php

namespace App\Livewire\Admin\Users;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use App\Services\AuditLogger;
use App\Services\CreditLedger;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public bool $showSettingsModal = false;

    public ?int $targetUserId = null;

    public int $grantAmount = 0;

    public string $grantNotes = '';

    /**
     * Plain-text password to be set on the target user. Admin types or
     * clicks "Generate" to fill it; clicking "Update password" persists it.
     */
    public string $newPassword = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function openSettings(int $userId): void
    {
        $this->targetUserId = $userId;
        $this->grantAmount = 0;
        $this->grantNotes = '';
        $this->newPassword = '';
        $this->resetErrorBag();
        $this->showSettingsModal = true;
    }

    /**
     * Backwards-compatible alias used by tests/legacy callers. The whole
     * "user configuration" sheet is the replacement for inline actions.
     */
    public function openGrantModal(int $userId): void
    {
        $this->openSettings($userId);
    }

    public function closeSettings(): void
    {
        $this->showSettingsModal = false;
        $this->targetUserId = null;
        $this->grantAmount = 0;
        $this->grantNotes = '';
        $this->newPassword = '';
    }

    public function grant(CreditLedger $ledger, AuditLogger $audit): void
    {
        $this->validate([
            'grantAmount' => ['required', 'integer', 'min:1'],
            'grantNotes' => ['required', 'string', 'max:500'],
        ]);

        if ($this->targetUserId === null) {
            $this->addError('grant', __('No user selected.'));

            return;
        }

        $user = User::findOrFail($this->targetUserId);
        $ledger->adminGrant($user, $this->grantAmount, auth()->user(), $this->grantNotes);

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'grant_credits',
            target: $user,
            payload: [
                'amount' => $this->grantAmount,
                'notes' => $this->grantNotes,
                'balance_after' => $user->fresh()->credit_balance,
            ],
        );

        $this->grantAmount = 0;
        $this->grantNotes = '';
    }

    public function toggleAdmin(AuditLogger $audit): void
    {
        if ($this->targetUserId === null) {
            $this->addError('toggleAdmin', __('No user selected.'));

            return;
        }

        $user = User::findOrFail($this->targetUserId);

        if ($user->id === auth()->id()) {
            $this->addError('toggleAdmin', __('You cannot change your own admin status.'));

            return;
        }

        $wasAdmin = $user->is_admin;
        $user->update(['is_admin' => ! $user->is_admin]);
        $user->refresh();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'toggle_admin',
            target: $user,
            payload: [
                'before' => $wasAdmin,
                'after' => $user->is_admin,
            ],
        );
    }

    public function suspend(UserAdminService $service): void
    {
        $user = User::findOrFail($this->targetUserId);
        $service->suspend($user, auth()->user());
    }

    public function unsuspend(UserAdminService $service): void
    {
        $user = User::findOrFail($this->targetUserId);
        $service->unsuspend($user, auth()->user());
    }

    /**
     * Fill the password input with a strong random password. Does NOT persist.
     * The admin reviews the value then clicks "Update password" to commit.
     */
    public function generatePassword(): void
    {
        $this->newPassword = Str::password(16, true, true, false);
    }

    public function cancelPasswordReset(): void
    {
        $this->newPassword = '';
        $this->resetErrorBag();
    }

    /**
     * Persist $newPassword to the target user. Validation runs first; on
     * success the input is cleared and an audit_log row written via the
     * service. The plain-text value is not echoed back to the browser.
     */
    public function resetPassword(UserAdminService $service): void
    {
        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'max:200'],
        ]);

        $user = User::findOrFail($this->targetUserId);
        $service->setPassword($user, $this->newPassword, auth()->user());

        $this->newPassword = '';
    }

    public function render()
    {
        $userCount = User::withTrashed()->count();
        // "Active" excludes soft-deleted AND suspended accounts (they are blocked).
        $activeCount = User::where('is_suspended', false)->count();
        $deletedCount = User::onlyTrashed()->count();
        $adminCount = User::where('is_admin', true)->count();
        $suspendedCount = User::where('is_suspended', true)->count();
        $pastDueCount = Subscription::query()
            ->where('stripe_status', 'past_due')
            ->distinct('user_id')
            ->count('user_id');
        $withActiveSubscription = Subscription::query()
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->distinct('user_id')
            ->count('user_id');

        return view('livewire.admin.users.index', [
            'users' => User::withTrashed()
                ->orderByDesc('created_at')
                ->paginate(25),
            'targetUser' => $this->targetUserId ? User::withTrashed()->find($this->targetUserId) : null,
            'metrics' => [
                'total' => $userCount,
                'active' => $activeCount,
                'deleted' => $deletedCount,
                'admins' => $adminCount,
                'suspended' => $suspendedCount,
                'past_due' => $pastDueCount,
                'with_active_subscription' => $withActiveSubscription,
            ],
        ])->layout('components.layouts.admin', [
            'header' => __('Users'),
        ]);
    }
}
