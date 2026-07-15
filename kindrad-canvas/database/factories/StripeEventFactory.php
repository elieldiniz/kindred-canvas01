<?php

namespace Database\Factories;

use App\Models\StripeEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeEvent>
 */
class StripeEventFactory extends Factory
{
    protected $model = StripeEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => 'evt_'.$this->faker->unique()->bothify('????????????????'),
            'type' => 'invoice.payment_succeeded',
            'payload' => ['id' => 'evt_test', 'type' => 'invoice.payment_succeeded'],
            'processed_at' => null,
        ];
    }
}
