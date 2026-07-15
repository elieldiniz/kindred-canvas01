<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Starter',
            'slug' => $this->faker->unique()->slug(1),
            'description' => 'Plano inicial para quem está começando.',
            'credits_per_period' => 50,
            'price_cents' => 1990,
            'currency' => 'BRL',
            'interval_id' => fn () => (new SubscriptionIntervalFactory)->resolveInterval('month')->id,
            'is_active' => true,
            'sort_order' => 10,
            'stripe_product_id' => null,
            'stripe_price_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function monthly(): static
    {
        return $this->state(fn (): array => [
            'interval_id' => fn () => (new SubscriptionIntervalFactory)->resolveInterval('month')->id,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (): array => [
            'interval_id' => fn () => (new SubscriptionIntervalFactory)->resolveInterval('year')->id,
        ]);
    }

    public function withInterval(string $slug): static
    {
        return $this->state(fn (): array => [
            'interval_id' => fn () => (new SubscriptionIntervalFactory)->resolveInterval($slug)->id,
        ]);
    }

    public function withStripeIds(string $productId = 'prod_test', string $priceId = 'price_test'): static
    {
        return $this->state(fn (): array => [
            'stripe_product_id' => $productId,
            'stripe_price_id' => $priceId,
        ]);
    }
}
