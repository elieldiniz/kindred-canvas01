<?php

namespace Database\Factories;

use App\Models\Style;
use App\Models\StyleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Style>
 */
class StyleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activeStatus = StyleStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);

        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(2),
            'prompt_fragment' => fake()->sentence(),
            'thumbnail_path' => null,
            'status_id' => $activeStatus->id,
        ];
    }

    /**
     * Indicate the style uses the watercolor preset.
     */
    public function watercolor(): static
    {
        $activeStatus = StyleStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);

        return $this->state(fn (array $attributes): array => [
            'name' => 'Watercolor',
            'slug' => 'watercolor',
            'prompt_fragment' => 'rendered in soft watercolor with gentle washes and visible brushstrokes',
            'thumbnail_path' => null,
            'status_id' => $activeStatus->id,
        ]);
    }

    /**
     * Indicate the style is inactive.
     */
    public function inactive(): static
    {
        $inactive = StyleStatus::firstOrCreate(['slug' => 'inactive'], ['name' => 'Inactive']);

        return $this->state(fn (array $attributes): array => [
            'status_id' => $inactive->id,
        ]);
    }
}
