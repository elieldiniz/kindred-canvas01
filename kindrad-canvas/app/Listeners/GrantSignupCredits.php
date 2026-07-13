<?php

namespace App\Listeners;

use App\Models\CreditTransactionReason;
use App\Models\User;
use App\Services\CreditLedger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class GrantSignupCredits
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $reasonExists = CreditTransactionReason::where('slug', 'signup_grant')->exists();

        if (! $reasonExists) {
            Log::warning('Skipping signup credit grant: credit_transaction_reasons lookup is not seeded.');

            return;
        }

        app(CreditLedger::class)->signupGrant($event->user, 5);
    }
}
