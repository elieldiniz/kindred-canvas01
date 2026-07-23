<?php

namespace App\Services\PromptEngine;

use App\Models\Project;

class PromptEngine
{
    /**
     * @param  iterable<PromptModule>  $modules
     */
    public function __construct(
        private readonly iterable $modules,
    ) {}

    /**
     * @return array{prompt: string, constraints: array{width: int, height: int, dpi?: int, safe_area_mm?: float, print_width_mm?: float, print_height_mm?: float, min_dpi?: int}}
     */
    public function assemble(Project $project): array
    {
        $fragments = [];

        foreach ($this->modules as $module) {
            $fragment = $module->fragment($project);
            if ($fragment !== null) {
                $fragments[] = $fragment;
            }
        }

        usort($fragments, fn (PromptFragment $a, PromptFragment $b): int => $b->priority <=> $a->priority);

        $pieces = [];
        $negatives = [];

        foreach ($fragments as $fragment) {
            if ($fragment->text !== '') {
                $pieces[] = $fragment->text;
            }
            if ($fragment->negativeFragment !== null && $fragment->negativeFragment !== '') {
                $negatives[] = $fragment->negativeFragment;
            }
        }

        $prompt = implode('. ', $pieces);

        if ($negatives !== []) {
            $prompt .= '. '.self::buildNegativeBlock($negatives);
        }

        $prompt = trim($prompt);
        if ($prompt !== '' && ! str_ends_with($prompt, '.') && substr_count($prompt, 'Avoid:') === 0) {
            $prompt .= '.';
        }

        return [
            'prompt' => $prompt,
            'constraints' => $this->constraintsFor($project),
        ];
    }

    /**
     * @return array{width: int, height: int, dpi?: int, safe_area_mm?: float, print_width_mm?: float, print_height_mm?: float, min_dpi?: int}
     */
    private function constraintsFor(Project $project): array
    {
        $product = $project->product;

        if ($product === null) {
            return ['width' => 0, 'height' => 0];
        }

        $dpi = (int) $product->min_dpi;
        $widthMm = (float) $product->print_width_mm;
        $heightMm = (float) $product->print_height_mm;

        $widthPx = (int) round($widthMm * $dpi / 25.4);
        $heightPx = (int) round($heightMm * $dpi / 25.4);

        return [
            'width' => $widthPx,
            'height' => $heightPx,
            'dpi' => $dpi,
            'safe_area_mm' => (float) $product->safe_area_mm,
            'print_width_mm' => $widthMm,
            'print_height_mm' => $heightMm,
            'min_dpi' => $dpi,
        ];
    }

    /**
     * @param  list<string>  $negatives
     */
    private static function buildNegativeBlock(array $negatives): string
    {
        $combined = implode(', ', array_map(
            static fn (string $n): string => trim($n, " \t\n\r\0\x0B,;:"),
            $negatives,
        ));

        $combined = preg_replace('/\s*,\s*/', ', ', $combined) ?? $combined;

        return 'Avoid: '.$combined;
    }
}
