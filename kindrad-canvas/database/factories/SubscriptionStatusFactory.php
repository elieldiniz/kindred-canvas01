<?php

namespace Database\Factories;

use App\Models\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionStatus>
 */
class SubscriptionStatusFactory extends Factory
{
    protected $model = SubscriptionStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Ativo',
            'slug' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['name' => 'Ativo', 'slug' => 'active']);
    }

    public function pastDue(): static
    {
        return $this->state(fn (): array => ['name' => 'Pagamento atrasado', 'slug' => 'past_due']);
    }

    public function canceled(): static
    {
        return $this->state(fn (): array => ['name' => 'Cancelado', 'slug' => 'canceled']);
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => ['name' => 'Em trial', 'slug' => 'trialing']);
    }

    public function incomplete(): static
    {
        return $this->state(fn (): array => ['name' => 'Incompleto', 'slug' => 'incomplete']);
    }
}
