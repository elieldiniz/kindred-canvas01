<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class IdentityModule implements PromptModule
{
    private const SUBJECT_TRANSLATIONS = [
        'pessoa' => 'person',
        'casal' => 'couple',
        'familia' => 'family',
        'pet' => 'pet',
        'outra' => 'subject',
    ];

    public const PRIORITY = 90;

    public function fragment(Project $project): ?PromptFragment
    {
        $inputs = is_array($project->inputs) ? $project->inputs : [];
        $name = (string) ($inputs['name'] ?? '');
        if ($name === '') {
            $name = 'the subject';
        }

        $subjectType = '';
        if ($project->subject_type !== null) {
            $subjectType = self::SUBJECT_TRANSLATIONS[$project->subject_type] ?? 'subject';
        }

        $text = $subjectType === ''
            ? sprintf('Portrait of %s', $name)
            : sprintf('Portrait of %s, %s', $name, $subjectType);

        return new PromptFragment(
            text: $text,
            priority: self::PRIORITY,
        );
    }
}
