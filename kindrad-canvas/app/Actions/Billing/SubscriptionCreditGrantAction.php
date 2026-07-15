<?php

namespace App\Actions\Billing;

use App\Models\CreditTransaction;
use App\Models\Subscription;
use App\Models\SubscriptionStatus;
use App\Services\CreditLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionCreditGrantAction
{
    public function __construct(private readonly CreditLedger $ledger) {}

    /**
     * Grant credits for a successful invoice. Idempotent on
     * (subscription_id, current_period_end) via CreditLedger.
     *
     * @param  array<string, mixed>  $invoice
     */
    public function handle(array $invoice): ?CreditTransaction
    {
        $stripeSubscriptionId = $invoice['subscription'] ?? null;

        if (! is_string($stripeSubscriptionId) || $stripeSubscriptionId === '') {
            Log::info('subscription_credit_grant_skipped_no_subscription_id', $invoice);

            return null;
        }

        $subscription = Subscription::with('subscriptionPlan')
            ->where('stripe_id', $stripeSubscriptionId)
            ->first();

        if ($subscription === null) {
            Log::warning('subscription_credit_grant_skipped_unknown_subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            return null;
        }

        $plan = $subscription->subscriptionPlan;

        if ($plan === null) {
            Log::warning('subscription_credit_grant_skipped_no_local_plan', [
                'subscription_id' => $subscription->id,
            ]);

            return null;
        }

        $credits = (int) $plan->credits_per_period;
        $periodEnd = $invoice['lines']['data'][0]['period']['end']
            ?? $invoice['period_end']
            ?? null;

        $tx = $this->ledger->subscriptionGrant(
            $subscription,
            $credits,
            $periodEnd !== null ? (int) $periodEnd : null,
        );

        if ($subscription->status_id === null
            || ($subscription->status?->slug !== 'active'
                && $subscription->status?->slug !== 'trialing')) {
            $activeId = SubscriptionStatus::where('slug', 'active')->value('id');
            if ($activeId) {
                $subscription->status_id = $activeId;
            }
        }

        if (isset($invoice['lines']['data'][0]['period']['start'])) {
            $subscription->current_period_start = Carbon::createFromTimestamp($invoice['lines']['data'][0]['period']['start']);
        }

        if (isset($invoice['lines']['data'][0]['period']['end'])) {
            $subscription->current_period_end = Carbon::createFromTimestamp($invoice['lines']['data'][0]['period']['end']);
        }

        $subscription->save();

        return $tx;
    }
}
