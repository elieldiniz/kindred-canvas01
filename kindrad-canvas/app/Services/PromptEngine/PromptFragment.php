<?php

namespace App\Services\PromptEngine;

final class PromptFragment
{
    public function __construct(
        public readonly string $text,
        public readonly int $priority,
        public readonly ?string $negativeFragment = null,
    ) {}
}
