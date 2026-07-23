<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class LightingModule implements PromptModule
{
    public const PRIORITY = 70;

    public function fragment(Project $project): ?PromptFragment
    {
        $category = $project->category;

        if ($category === null) {
            return null;
        }

        $hint = $category->lighting_hint;

        if ($hint === null || $hint === '') {
            return null;
        }

        return new PromptFragment(
            text: sprintf('Lighting: %s', $hint),
            priority: self::PRIORITY,
        );
    }
}
