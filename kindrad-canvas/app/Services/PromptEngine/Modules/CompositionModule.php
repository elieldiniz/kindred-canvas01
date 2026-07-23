<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class CompositionModule implements PromptModule
{
    private const COMPOSITIONS = [
        'centered' => 'Composition: subject centered with clean background, balanced and minimal',
        'border_wrap' => 'Composition: full width seamless edges, background flows continuously across the canvas',
        'full_bleed' => 'Composition: repeating pattern, small elements evenly distributed, seamless horizontal tiling',
        'split_top_bottom' => 'Composition: dual layout with empty center, two distinct subjects on left and right',
    ];

    public const PRIORITY = 70;

    public function fragment(Project $project): ?PromptFragment
    {
        $layout = $project->layout;

        if ($layout === null) {
            return null;
        }

        $slug = $layout->slug;

        $text = self::COMPOSITIONS[$slug] ?? null;

        if ($text === null) {
            return null;
        }

        return new PromptFragment(
            text: $text,
            priority: self::PRIORITY,
        );
    }
}
