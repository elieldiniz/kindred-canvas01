<?php

namespace Database\Factories;

use App\Models\ShowcaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShowcaseItem>
 */
class ShowcaseItemFactory extends Factory
{
    protected $model = ShowcaseItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Sample '.fake()->words(2, true),
            'image_path' => 'showcase/'.fake()->uuid().'.png',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function sorted(int $order): static
    {
        return $this->state(fn (): array => ['sort_order' => $order]);
    }
}
