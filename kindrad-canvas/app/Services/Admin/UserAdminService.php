<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Admin-side user operations. Encapsulates state-changing actions
 * (suspend, unsuspend, password reset) and the audit call.
 *
 * READ paths (metrics) intentionally live on the Index component.
 */
class UserAdminService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function suspend(User $target, User $actor): User
    {
        if ($target->id === $actor->id) {
            throw new \InvalidArgumentException('You cannot suspend yourself.');
        }

        $wasSuspended = (bool) $target->is_suspended;
        $target->is_suspended = true;
        $target->save();

        if (! $wasSuspended) {
            $this->audit->record(
                actor: $actor,
                actionSlug: 'suspend_user',
                target: $target,
                payload: ['before' => false, 'after' => true],
            );
        }

        return $target;
    }

    public function unsuspend(User $target, User $actor): User
    {
        $wasSuspended = (bool) $target->is_suspended;
        $target->is_suspended = false;
        $target->save();

        if ($wasSuspended) {
            $this->audit->record(
                actor: $actor,
                actionSlug: 'unsuspend_user',
                target: $target,
                payload: ['before' => true, 'after' => false],
            );
        }

        return $target;
    }

    /**
     * Set the target user's password to a specific value supplied by the
     * admin. Rotates remember_token so existing sessions are invalidated.
     * Does NOT return or echo the plain-text password.
     */
    public function setPassword(User $target, string $plainPassword, User $actor): User
    {
        $target->password = Hash::make($plainPassword);
        $target->setRememberToken(Str::random(60));
        $target->save();

        $this->audit->record(
            actor: $actor,
            actionSlug: 'password_reset_by_admin',
            target: $target,
            payload: [
                'reset_at' => now()->toIso8601String(),
                'method' => 'admin_supplied',
            ],
        );

        return $target;
    }
}
