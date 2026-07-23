<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class StyleModule implements PromptModule
{
    public const PRIORITY = 75;

    public function fragment(Project $project): ?PromptFragment
    {
        $style = $project->style;

        if ($style === null) {
            return null;
        }

        $fragment = $style->prompt_fragment;

        if ($fragment === null || $fragment === '') {
            return null;
        }

        $negative = $style->negative_fragment !== null && $style->negative_fragment !== ''
            ? $style->negative_fragment
            : null;

        return new PromptFragment(
            text: sprintf('Style: %s', $fragment),
            priority: self::PRIORITY,
            negativeFragment: $negative,
        );
    }
}
