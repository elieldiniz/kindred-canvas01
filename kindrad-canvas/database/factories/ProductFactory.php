<?php

namespace Database\Factories;

use App\Models\ColorMode;
use App\Models\Product;
use App\Models\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activeStatus = ProductStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
        $rgb = ColorMode::firstOrCreate(['slug' => 'rgb'], ['name' => 'RGB']);

        return [
            'name' => fake()->unique()->word().' Product',
            'slug' => fake()->unique()->slug(2),
            'status_id' => $activeStatus->id,
            'print_width_mm' => 100.00,
            'print_height_mm' => 100.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode_id' => $rgb->id,
        ];
    }

    /**
     * Indicate the product is the canonical mug.
     */
    public function mug(): static
    {
        $activeStatus = ProductStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
        $rgb = ColorMode::firstOrCreate(['slug' => 'rgb'], ['name' => 'RGB']);

        return $this->state(fn (array $attributes): array => [
            'name' => 'Mug',
            'slug' => 'mug',
            'status_id' => $activeStatus->id,
            'print_width_mm' => 220.00,
            'print_height_mm' => 95.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode_id' => $rgb->id,
        ]);
    }
}
