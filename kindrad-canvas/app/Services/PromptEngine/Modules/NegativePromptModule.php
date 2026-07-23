<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class NegativePromptModule implements PromptModule
{
    public const PRIORITY = 60;

    private const NEGATIVE_BASE = 'Avoid: blurry, distorted faces, extra limbs, low quality, watermark, text overlay';

    public function fragment(Project $project): ?PromptFragment
    {
        return new PromptFragment(
            text: self::NEGATIVE_BASE,
            priority: self::PRIORITY,
        );
    }
}
