<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'default',
            'stripe_id' => 'sub_'.$this->faker->unique()->bothify('????????????????'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_'.$this->faker->bothify('????????????????'),
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'subscription_plan_id' => SubscriptionPlan::factory()->withInterval('month'),
            'pending_plan_id' => null,
            'status_id' => null,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'cancel_at_period_end' => false,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function forPlan(SubscriptionPlan $plan): static
    {
        return $this->state(fn (): array => [
            'subscription_plan_id' => $plan->id,
            'stripe_price' => $plan->stripe_price_id ?? 'price_test',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'stripe_status' => 'active',
            'status_id' => fn () => SubscriptionStatus::where('slug', 'active')->value('id'),
            'ends_at' => null,
            'cancel_at_period_end' => false,
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (): array => [
            'stripe_status' => 'past_due',
            'status_id' => fn () => SubscriptionStatus::where('slug', 'past_due')->value('id'),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (): array => [
            'stripe_status' => 'canceled',
            'status_id' => fn () => SubscriptionStatus::where('slug', 'canceled')->value('id'),
            'ends_at' => now(),
            'cancel_at_period_end' => true,
        ]);
    }
}
