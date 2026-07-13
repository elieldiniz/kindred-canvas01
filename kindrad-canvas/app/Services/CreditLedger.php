<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\User;
use App\Services\Exceptions\CreditInsufficientException;
use Illuminate\Support\Facades\DB;

class CreditLedger
{
    /**
     * @var array<string, int>
     */
    private array $reasonIdCache = [];

    /**
     * Award a signup grant to a freshly-registered user. Idempotent: returns
     * the existing ledger row if the user already received a signup grant.
     */
    public function signupGrant(User $user, int $credits = 5): CreditTransaction
    {
        $this->assertPositive($credits);

        $reasonId = $this->reasonId('signup_grant');

        $existing = CreditTransaction::where('user_id', $user->id)
            ->where('reason_id', $reasonId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $credits, $reasonId): CreditTransaction {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $newBalance = $lockedUser->credit_balance + $credits;
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'reason_id' => $reasonId,
                'delta' => $credits,
                'balance_after' => $newBalance,
                'reference_type' => null,
                'reference_id' => null,
                'notes' => null,
            ]);
        });
    }

    /**
     * Debit credits for a generation. Atomic with users.credit_balance.
     * Refuses if the user does not have enough credits.
     */
    public function debit(User $user, int $credits, Generation $reference): CreditTransaction
    {
        $this->assertPositive($credits);

        $reasonId = $this->reasonId('generation_debit');

        return DB::transaction(function () use ($user, $credits, $reasonId, $reference): CreditTransaction {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->credit_balance < $credits) {
                throw CreditInsufficientException::for($lockedUser->credit_balance, $credits);
            }

            $newBalance = $lockedUser->credit_balance - $credits;
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'reason_id' => $reasonId,
                'delta' => -$credits,
                'balance_after' => $newBalance,
                'reference_type' => Generation::class,
                'reference_id' => $reference->id,
                'notes' => null,
            ]);
        });
    }

    /**
     * Refund credits for a failed generation. Idempotent: returns the existing
     * refund row if this generation was already refunded.
     */
    public function refund(Generation $reference, string $reason): CreditTransaction
    {
        $reasonId = $this->reasonId('generation_refund');

        $existing = CreditTransaction::where('reference_type', Generation::class)
            ->where('reference_id', $reference->id)
            ->where('reason_id', $reasonId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $refundAmount = $reference->credits_charged ?: 1;

        return DB::transaction(function () use ($reference, $reason, $reasonId, $refundAmount): CreditTransaction {
            $lockedUser = User::whereKey($reference->user_id)->lockForUpdate()->firstOrFail();
            $newBalance = $lockedUser->credit_balance + $refundAmount;
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'reason_id' => $reasonId,
                'delta' => $refundAmount,
                'balance_after' => $newBalance,
                'reference_type' => Generation::class,
                'reference_id' => $reference->id,
                'notes' => $reason,
            ]);
        });
    }

    /**
     * Admin manual grant. Stores notes on the ledger row.
     */
    public function adminGrant(User $user, int $credits, User $actor, string $notes): CreditTransaction
    {
        $this->assertPositive($credits);

        $reasonId = $this->reasonId('admin_grant');

        return DB::transaction(function () use ($user, $credits, $reasonId, $notes, $actor): CreditTransaction {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $newBalance = $lockedUser->credit_balance + $credits;
            $lockedUser->credit_balance = $newBalance;
            $lockedUser->save();

            return CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'reason_id' => $reasonId,
                'delta' => $credits,
                'balance_after' => $newBalance,
                'reference_type' => User::class,
                'reference_id' => $actor->id,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Resolve a credit_transaction_reasons.slug to its primary key.
     * Memoized per request for cheap repeated lookups.
     */
    public function reasonId(string $slug): int
    {
        return $this->reasonIdCache[$slug] ??= CreditTransactionReason::where('slug', $slug)->valueOrFail('id');
    }

    private function assertPositive(int $credits): void
    {
        if ($credits <= 0) {
            throw new \InvalidArgumentException('Credit amounts must be positive; received: '.$credits);
        }
    }
}
