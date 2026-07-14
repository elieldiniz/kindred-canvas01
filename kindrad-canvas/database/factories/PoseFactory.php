<?php

namespace Database\Factories;

use App\Models\Pose;
use App\Models\PoseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pose>
 */
class PoseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'slug' => Str::slug($name).'-'.Str::random(4),
            'name' => ucfirst($name),
            'thumbnail_path' => null,
            'status_id' => PoseStatus::where('slug', 'active')->value('id'),
            'sort_order' => 0,
        ];
    }
}
