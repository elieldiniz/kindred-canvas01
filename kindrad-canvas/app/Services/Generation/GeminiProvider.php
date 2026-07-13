<?php

namespace App\Services\Generation;

use App\Contracts\GenerationProvider;
use App\Generation\GenerationResult;
use App\Models\SourceImage;
use LogicException;

class GeminiProvider implements GenerationProvider
{
    public function getProviderKey(): string
    {
        return 'gemini';
    }

    public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
    {
        throw new LogicException('GeminiProvider is not implemented yet.');
    }
}
