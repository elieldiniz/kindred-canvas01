<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $draft = ProjectStatus::where('slug', 'draft')->first();

        return [
            'user_id' => User::factory(),
            'product_id' => Product::where('slug', 'mug')->first()?->id
                ?? Product::factory()->mug(),
            'category_id' => null,
            'style_id' => null,
            'layout_id' => null,
            'mode_id' => null,
            'status_id' => $draft?->id ?? ProjectStatus::factory(),
            'title' => null,
            'inputs' => [],
            'first_generated_at' => null,
        ];
    }

    public function withMode(int $modeId): static
    {
        return $this->state(fn (array $attributes): array => [
            'mode_id' => $modeId,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function withCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $categoryId,
        ]);
    }

    public function withStyle(int $styleId): static
    {
        return $this->state(fn (array $attributes): array => [
            'style_id' => $styleId,
        ]);
    }

    public function withLayout(int $layoutId): static
    {
        return $this->state(fn (array $attributes): array => [
            'layout_id' => $layoutId,
        ]);
    }
}
