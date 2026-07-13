<?php

namespace App\Contracts;

use App\Generation\GenerationResult;
use App\Models\SourceImage;

interface GenerationProvider
{
    /**
     * Generate an image from the given prompt and constraints.
     *
     * @param  array{width:int, height:int, mime?: string, dpi?: int, safe_area_mm?: float, print_width_mm?: float, print_height_mm?: float}  $constraints
     */
    public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult;

    /**
     * Stable slug key used to re-resolve this provider across queue boundaries
     * (e.g., for jobs that need to look up the provider by name).
     */
    public function getProviderKey(): string;
}
