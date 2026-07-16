<?php

namespace Database\Factories;

use App\Models\PaymentFailure;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentFailure>
 */
class PaymentFailureFactory extends Factory
{
    protected $model = PaymentFailure::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'event_type' => 'invoice.payment_failed',
            'stripe_invoice_id' => 'in_'.$this->faker->unique()->bothify('????????????????'),
            'stripe_charge_id' => 'ch_'.$this->faker->unique()->bothify('????????????????'),
            'attempted_at' => now(),
            'reason' => 'card_declined',
            'payload' => ['failure_reason' => 'card_declined'],
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (): array => [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);
    }
}
