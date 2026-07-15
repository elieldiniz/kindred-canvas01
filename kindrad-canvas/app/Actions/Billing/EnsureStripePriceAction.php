<?php

namespace App\Actions\Billing;

use App\Models\SubscriptionPlan;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class EnsureStripePriceAction
{
    public function handle(SubscriptionPlan $plan): SubscriptionPlan
    {
        if (empty(config('services.stripe.secret'))) {
            return $plan;
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        if (empty($plan->stripe_product_id)) {
            $product = Product::create([
                'name' => $plan->name,
                'description' => $plan->description,
            ]);
            $plan->stripe_product_id = $product->id;
        }

        if (empty($plan->stripe_price_id)) {
            $price = Price::create([
                'product' => $plan->stripe_product_id,
                'unit_amount' => $plan->price_cents,
                'currency' => strtolower($plan->currency),
                'recurring' => [
                    'interval' => $plan->interval->slug,
                ],
            ]);
            $plan->stripe_price_id = $price->id;
        }

        $plan->save();

        return $plan;
    }
}
