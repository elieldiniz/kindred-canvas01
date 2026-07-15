<?php

namespace Database\Factories;

use App\Models\SubscriptionInterval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionInterval>
 */
class SubscriptionIntervalFactory extends Factory
{
    protected $model = SubscriptionInterval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mensal',
            'slug' => 'month',
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn (): array => ['name' => 'Mensal', 'slug' => 'month']);
    }

    public function yearly(): static
    {
        return $this->state(fn (): array => ['name' => 'Anual', 'slug' => 'year']);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (SubscriptionInterval $interval): void {
            // no-op; existence handled via firstOrCreate in the factory's persist path below.
        });
    }

    /**
     * Resolve to an existing row by slug (created by CatalogSeeder).
     * Falls back to creating one if missing in the test env.
     */
    public function resolveInterval(string $slug): SubscriptionInterval
    {
        $existing = SubscriptionInterval::where('slug', $slug)->first();

        if ($existing) {
            return $existing;
        }

        $name = $slug === 'year' ? 'Anual' : 'Mensal';

        return SubscriptionInterval::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'id' => (string) Str::uuid()],
        );
    }
}
