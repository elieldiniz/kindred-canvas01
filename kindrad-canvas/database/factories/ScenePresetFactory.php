<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ScenePreset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScenePreset>
 */
class ScenePresetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'prompt_fragment' => ucfirst($name).' scene background',
            'sort_order' => 0,
            'is_default' => false,
        ];
    }
}
