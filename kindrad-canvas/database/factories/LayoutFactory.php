<?php

namespace Database\Factories;

use App\Models\Layout;
use App\Models\LayoutStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Layout>
 */
class LayoutFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activeStatus = LayoutStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);

        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(2),
            'preview_path' => null,
            'safe_area_overlay' => null,
            'proportion_ratio' => '1:1',
            'status_id' => $activeStatus->id,
        ];
    }

    /**
     * Indicate the layout uses the centered preset.
     */
    public function centered(): static
    {
        $activeStatus = LayoutStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);

        return $this->state(fn (array $attributes): array => [
            'name' => 'Centered',
            'slug' => 'centered',
            'preview_path' => null,
            'safe_area_overlay' => ['top_mm' => 5, 'bottom_mm' => 5, 'left_mm' => 5, 'right_mm' => 5],
            'proportion_ratio' => '1:1',
            'status_id' => $activeStatus->id,
        ]);
    }

    /**
     * Indicate the layout is inactive.
     */
    public function inactive(): static
    {
        $inactive = LayoutStatus::firstOrCreate(['slug' => 'inactive'], ['name' => 'Inactive']);

        return $this->state(fn (array $attributes): array => [
            'status_id' => $inactive->id,
        ]);
    }
}
