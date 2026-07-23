<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class UserOverrideModule implements PromptModule
{
    public const PRIORITY = 100;

    public function fragment(Project $project): ?PromptFragment
    {
        $custom = $project->custom_prompt;

        if ($custom === null || trim($custom) === '') {
            return null;
        }

        return new PromptFragment(
            text: $custom,
            priority: self::PRIORITY,
        );
    }
}
