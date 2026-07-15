<?php

namespace App\Actions\Billing;

use App\Models\SubscriptionPlan;
use App\Models\User;

class StartCheckoutAction
{
    public function handle(User $user, SubscriptionPlan $plan): string
    {
        if (empty($plan->stripe_price_id)) {
            throw new \LogicException("Subscription plan {$plan->slug} is missing a stripe_price_id; ensure EnsureStripePriceAction ran first.");
        }

        $user->createOrGetStripeCustomer();

        return $user->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => route('billing.index', ['success' => 1]),
                'cancel_url' => route('billing.plans.index'),
            ])
            ->redirect()
            ->getTargetUrl();
    }
}
