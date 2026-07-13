<?php

namespace Database\Factories;

use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason_id' => CreditTransactionReason::where('slug', 'signup_grant')->value('id'),
            'delta' => 5,
            'balance_after' => 5,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
        ];
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason_id' => CreditTransactionReason::where('slug', 'generation_debit')->value('id'),
            'delta' => -1,
            'reference_type' => Generation::class,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason_id' => CreditTransactionReason::where('slug', 'generation_refund')->value('id'),
            'delta' => 1,
        ]);
    }

    public function adminGrant(int $n, string $notes): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason_id' => CreditTransactionReason::where('slug', 'admin_grant')->value('id'),
            'delta' => $n,
            'notes' => $notes,
        ]);
    }
}
