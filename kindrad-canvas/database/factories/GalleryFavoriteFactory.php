<?php

namespace Database\Factories;

use App\Models\GalleryFavorite;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryFavorite>
 */
class GalleryFavoriteFactory extends Factory
{
    protected $model = GalleryFavorite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
        ];
    }
}
