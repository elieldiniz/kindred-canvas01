<?php

namespace Database\Factories;

use App\Models\ProjectPhoto;
use App\Models\SourceImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPhoto>
 */
class ProjectPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => null,
            'source_image_id' => SourceImage::factory(),
            'position' => 0,
        ];
    }
}
