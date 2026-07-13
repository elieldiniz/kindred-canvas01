<?php

namespace App\Services\Generation;

use App\Contracts\GenerationProvider;
use App\Generation\GenerationResult;
use App\Models\SourceImage;
use LogicException;

class ReplicateProvider implements GenerationProvider
{
    public function getProviderKey(): string
    {
        return 'replicate';
    }

    public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
    {
        throw new LogicException('ReplicateProvider is not implemented yet.');
    }
}
