<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activeStatus = CategoryStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
        $product = Product::where('slug', 'mug')->first() ?? Product::factory()->mug()->create();

        return [
            'product_id' => $product->id,
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'thumbnail_path' => null,
            'status_id' => $activeStatus->id,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate the category is inactive.
     */
    public function inactive(): static
    {
        $inactive = CategoryStatus::firstOrCreate(['slug' => 'inactive'], ['name' => 'Inactive']);

        return $this->state(fn (array $attributes): array => [
            'status_id' => $inactive->id,
        ]);
    }
}
