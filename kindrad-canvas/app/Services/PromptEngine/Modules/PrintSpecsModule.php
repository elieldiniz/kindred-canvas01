<?php

namespace App\Services\PromptEngine\Modules;

use App\Models\Project;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

class PrintSpecsModule implements PromptModule
{
    public const PRIORITY = 80;

    public function fragment(Project $project): ?PromptFragment
    {
        $product = $project->product;

        if ($product === null) {
            return null;
        }

        $mode = $project->mode;

        if ($mode === null || $mode->slug !== 'mug') {
            return null;
        }

        $dpi = (int) $product->min_dpi;
        $widthMm = (float) $product->print_width_mm;
        $heightMm = (float) $product->print_height_mm;
        $safeArea = (float) $product->safe_area_mm;

        $widthPx = (int) round($widthMm * $dpi / 25.4);
        $heightPx = (int) round($heightMm * $dpi / 25.4);

        $text = sprintf(
            'Print specs: Horizontal layout (landscape), aspect ratio 2.5:1, resolution %dx%dpx, optimized for sublimation printing, %smm × %smm at %d DPI, %smm safe area',
            $widthPx,
            $heightPx,
            $dpi,
            $widthMm,
            $heightMm,
            $dpi,
            $safeArea,
        );

        return new PromptFragment(
            text: $text,
            priority: self::PRIORITY,
        );
    }
}
