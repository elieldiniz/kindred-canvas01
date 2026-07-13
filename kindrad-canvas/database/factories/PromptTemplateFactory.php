<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Layout;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromptTemplate>
 */
class PromptTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::where('slug', 'mug')->first()?->id ?? Product::factory()->mug(),
            'category_id' => Category::factory(),
            'style_id' => Style::factory(),
            'layout_id' => Layout::factory(),
            'body' => '{{name}} {{phrase}} {{theme}} {{image_tags}} {{print_specs}}',
            'version' => 1,
        ];
    }

    public function forTuple(int $productId, int $categoryId, int $styleId, int $layoutId): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $productId,
            'category_id' => $categoryId,
            'style_id' => $styleId,
            'layout_id' => $layoutId,
        ]);
    }
}
