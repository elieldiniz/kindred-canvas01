<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class ProductModule implements PromptModule
{
    public const PRIORITY = 80;

    public function fragment(Project $project): ?PromptFragment
    {
        $product = $project->product;

        if ($product === null) {
            return null;
        }

        $rules = $product->product_prompt_rules;

        if ($rules === null || $rules === []) {
            return null;
        }

        if (! is_array($rules)) {
            return null;
        }

        $text = implode(' ', array_map(
            static fn (string $rule): string => trim($rule, " \t\n\r\0\x0B."),
            $rules,
        ));

        if ($text === '') {
            return null;
        }

        return new PromptFragment(
            text: sprintf('Product rules: %s', $text),
            priority: self::PRIORITY,
        );
    }
}
