<?php

namespace App\Generation;

class GenerationResult
{
    public function __construct(
        public readonly string $path,
        public readonly string $mime,
        public readonly int $width,
        public readonly int $height,
        public readonly string $binary = '',
    ) {}
}
