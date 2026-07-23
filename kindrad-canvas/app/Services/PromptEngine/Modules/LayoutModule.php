<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class LayoutModule implements PromptModule
{
    public const PRIORITY = 70;

    public function fragment(Project $project): ?PromptFragment
    {
        $layout = $project->layout;

        if ($layout === null) {
            return null;
        }

        $fragment = $layout->prompt_fragment;

        if ($fragment === null || $fragment === '') {
            return null;
        }

        return new PromptFragment(
            text: sprintf('Layout: %s', $fragment),
            priority: self::PRIORITY,
        );
    }
}
