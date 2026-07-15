<?php

namespace App\Actions\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncSubscriptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?string $eventType = null): ?Subscription
    {
        $stripeId = $data['id'] ?? null;
        $customer = $data['customer'] ?? null;

        if (! is_string($stripeId) || ! is_string($customer)) {
            Log::warning('sync_subscription_skipped_missing_ids', ['data' => $data]);

            return null;
        }

        $user = User::where('stripe_id', $customer)->first();

        if ($user === null) {
            Log::warning('sync_subscription_skipped_unknown_customer', ['customer' => $customer]);

            return null;
        }

        $priceId = $data['items']['data'][0]['price']['id'] ?? $data['plan']['id'] ?? null;
        $plan = is_string($priceId)
            ? SubscriptionPlan::where('stripe_price_id', $priceId)->first()
            : null;

        $subscription = Subscription::where('stripe_id', $stripeId)->first();

        if ($subscription === null) {
            $subscription = new Subscription;
            $subscription->user_id = $user->id;
            $subscription->type = 'default';
            $subscription->stripe_id = $stripeId;
        }

        $subscription->stripe_status = $data['status'] ?? $subscription->stripe_status;
        $subscription->stripe_price = $priceId ?: $subscription->stripe_price;

        if ($plan !== null && $subscription->subscription_plan_id !== $plan->id) {
            $subscription->subscription_plan_id = $plan->id;
        }

        $statusId = SubscriptionStatus::where('slug', $data['status'] ?? '')->value('id');
        if ($statusId !== null) {
            $subscription->status_id = $statusId;
        }

        if (isset($data['current_period_start'])) {
            $subscription->current_period_start = Carbon::createFromTimestamp($data['current_period_start']);
        }

        if (isset($data['current_period_end'])) {
            $subscription->current_period_end = Carbon::createFromTimestamp($data['current_period_end']);
        }

        $subscription->cancel_at_period_end = (bool) ($data['cancel_at_period_end'] ?? false);

        if (! empty($data['cancel_at_period_end'])) {
            $subscription->ends_at = isset($data['cancel_at'])
                ? Carbon::createFromTimestamp($data['cancel_at'])
                : ($subscription->ends_at ?? $subscription->current_period_end);
        } elseif (($data['status'] ?? null) === 'canceled') {
            $subscription->ends_at = now();
        }

        $subscription->save();

        return $subscription;
    }
}
