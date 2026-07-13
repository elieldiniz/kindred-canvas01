<?php

namespace App\Services\Generation;

use App\Contracts\GenerationProvider;
use App\Models\GenerationProvider as GenerationProviderModel;
use InvalidArgumentException;

class ProviderRegistry
{
    /**
     * @var array<string, class-string<GenerationProvider>>
     */
    private array $map = [
        'openai' => OpenAIProvider::class,
        'gemini' => GeminiProvider::class,
        'replicate' => ReplicateProvider::class,
    ];

    /**
     * Resolve a provider instance by its slug.
     */
    public function resolve(string $slug): GenerationProvider
    {
        $class = $this->map[$slug] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown generation provider slug: {$slug}");
        }

        return app($class);
    }

    /**
     * Resolve the active provider.
     *
     * Honors `config('generation.provider')`. If the lookup table marks that
     * slug as inactive, falls back to the first `is_active = true` row, then
     * to the OpenAI provider as a safe default.
     */
    public function resolveActive(): GenerationProvider
    {
        $configured = (string) config('generation.provider', 'openai');

        $row = GenerationProviderModel::where('slug', $configured)->first();

        if ($row !== null && $row->is_active) {
            return $this->resolve($row->slug);
        }

        $fallback = GenerationProviderModel::where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($fallback !== null) {
            return $this->resolve($fallback->slug);
        }

        return $this->resolve('openai');
    }
}
