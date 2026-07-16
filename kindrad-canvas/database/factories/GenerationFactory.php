<?php

namespace Database\Factories;

use App\Models\Generation;
use App\Models\GenerationProvider;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $waiting = GenerationStatus::where('slug', 'waiting')->firstOrFail();

        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'status_id' => $waiting->id,
            'provider_id' => null,
            'prompt_snapshot' => fake()->sentence(),
            'constraints_snapshot' => ['print_width_mm' => 220.0, 'print_height_mm' => 95.0],
            'idempotency_key' => (string) Str::uuid(),
            'result_path' => null,
            'result_mime_type' => null,
            'result_width_px' => null,
            'result_height_px' => null,
            'failure_reason' => null,
            'credits_charged' => 1,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_id' => GenerationStatus::where('slug', 'processing')->value('id'),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_id' => GenerationStatus::where('slug', 'completed')->value('id'),
            'provider_id' => GenerationProvider::where('slug', 'openai')->value('id'),
            'result_path' => 'generations/'.fake()->uuid().'.png',
            'result_mime_type' => 'image/png',
            'result_width_px' => 1024,
            'result_height_px' => 1024,
            'started_at' => now()->subSeconds(30),
            'completed_at' => now(),
        ]);
    }

    public function failed(string $reason): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_id' => GenerationStatus::where('slug', 'failed')->value('id'),
            'failure_reason' => $reason,
            'completed_at' => now(),
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }

    public function waiting(): static
    {
        return $this->state(fn (): array => [
            'status_id' => GenerationStatus::where('slug', 'waiting')->value('id'),
        ]);
    }
}
