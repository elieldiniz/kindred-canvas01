<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionStatus;
use Illuminate\Support\Facades\Log;

class MarkSubscriptionPastDueAction
{
    public function handle(string $stripeSubscriptionId): ?Subscription
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if ($subscription === null) {
            Log::info('mark_past_due_skipped_unknown_subscription', ['id' => $stripeSubscriptionId]);

            return null;
        }

        $pastDueId = SubscriptionStatus::where('slug', 'past_due')->value('id');

        if ($pastDueId !== null) {
            $subscription->status_id = $pastDueId;
        }

        $subscription->stripe_status = 'past_due';
        $subscription->save();

        return $subscription;
    }
}
