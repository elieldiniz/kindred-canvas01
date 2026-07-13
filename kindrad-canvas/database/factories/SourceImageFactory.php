<?php

namespace Database\Factories;

use App\Models\SourceImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceImage>
 */
class SourceImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'disk' => 's3',
            'path' => 'source-images/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width_px' => null,
            'height_px' => null,
        ];
    }
}
