<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Pose;
use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class PoseModule implements PromptModule
{
    private const POSE_DESCRIPTIONS = [
        'abracados' => 'embracing couple with warm body language',
        'beijo' => 'kissing couple in romantic embrace',
        'sentados' => 'sitting side by side in relaxed pose',
        'caminhando' => 'walking together hand in hand',
        'natal' => 'festive Christmas holiday scene',
        'praia' => 'beach scene with ocean waves',
        'sofa' => 'cozy living room sofa setting',
        'flores' => 'surrounded by colorful flowers',
    ];

    public const PRIORITY = 85;

    public function fragment(Project $project): ?PromptFragment
    {
        $pose = $project->pose;

        if ($pose === null) {
            return null;
        }

        $description = $this->descriptionFor($pose);

        return new PromptFragment(
            text: sprintf('Pose: %s', $description),
            priority: self::PRIORITY,
        );
    }

    private function descriptionFor(Pose $pose): string
    {
        $slug = $pose->slug;
        if (isset(self::POSE_DESCRIPTIONS[$slug])) {
            return self::POSE_DESCRIPTIONS[$slug];
        }

        if ($pose->rich_description !== null && $pose->rich_description !== '') {
            return $pose->rich_description;
        }

        return $pose->name;
    }
}
