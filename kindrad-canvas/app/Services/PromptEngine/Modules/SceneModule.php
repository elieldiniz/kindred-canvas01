<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class SceneModule implements PromptModule
{
    public const PRIORITY = 70;

    public function fragment(Project $project): ?PromptFragment
    {
        $preset = $project->scenePreset;

        if ($preset !== null && $preset->prompt_fragment !== null && $preset->prompt_fragment !== '') {
            return new PromptFragment(
                text: sprintf('Scene: %s', $preset->prompt_fragment),
                priority: self::PRIORITY,
            );
        }

        $category = $project->category;

        if ($category !== null && $category->scene_prompt !== null && $category->scene_prompt !== '') {
            return new PromptFragment(
                text: sprintf('Scene: %s', $category->scene_prompt),
                priority: self::PRIORITY,
            );
        }

        return null;
    }
}
