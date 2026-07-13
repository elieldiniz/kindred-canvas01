<?php

namespace Database\Factories;

use App\Models\ProjectMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMode>
 */
class ProjectModeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(2),
            'injects_print_specs' => false,
        ];
    }
}
