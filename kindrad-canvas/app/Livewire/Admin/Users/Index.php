<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CreditLedger;
use Livewire\Component;

class Index extends Component
{
    public bool $showGrantModal = false;

    public ?int $grantUserId = null;

    public int $grantAmount = 0;

    public string $grantNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function openGrantModal(int $userId): void
    {
        $this->grantUserId = $userId;
        $this->grantAmount = 0;
        $this->grantNotes = '';
        $this->showGrantModal = true;
    }

    public function grant(CreditLedger $ledger, AuditLogger $audit): void
    {
        $this->validate([
            'grantAmount' => ['required', 'integer', 'min:1'],
            'grantNotes' => ['required', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($this->grantUserId);
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

        $this->showGrantModal = false;
        $this->grantUserId = null;
        $this->grantAmount = 0;
        $this->grantNotes = '';
    }

    public function toggleAdmin(int $userId, AuditLogger $audit): void
    {
        if ($userId === auth()->id()) {
            $this->addError('toggleAdmin', __('You cannot change your own admin status.'));

            return;
        }

        $user = User::findOrFail($userId);
        $wasAdmin = $user->is_admin;
        $user->update(['is_admin' => ! $user->is_admin]);

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

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->orderBy('created_at', 'desc')
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Users'),
        ]);
    }
}
