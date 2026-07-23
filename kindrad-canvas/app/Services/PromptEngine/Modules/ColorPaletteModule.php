<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class ColorPaletteModule implements PromptModule
{
    public const PRIORITY = 65;

    public function fragment(Project $project): ?PromptFragment
    {
        $category = $project->category;

        if ($category === null) {
            return null;
        }

        $palette = $category->color_palette;

        if ($palette === null || $palette === '') {
            return null;
        }

        return new PromptFragment(
            text: sprintf('Color palette: %s', $palette),
            priority: self::PRIORITY,
        );
    }
}
