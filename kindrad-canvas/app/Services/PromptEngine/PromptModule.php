<?php

namespace App\Services\PromptEngine;

use App\Models\Project;

interface PromptModule
{
    public function fragment(Project $project): ?PromptFragment;
}
