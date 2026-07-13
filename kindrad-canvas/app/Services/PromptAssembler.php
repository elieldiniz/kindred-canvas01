<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PromptTemplate;
use RuntimeException;

class PromptAssembler
{
    /**
     * @return array{prompt: string, constraints: array{width: int, height: int, dpi?: int, safe_area_mm?: float, print_width_mm?: float, print_height_mm?: float, min_dpi?: int}}
     */
    public function assemble(Project $project): array
    {
        $product = $project->product;
        $category = $project->category;
        $style = $project->style;
        $layout = $project->layout;
        $mode = $project->mode;

        if ($product === null || $category === null || $style === null || $layout === null) {
            throw new RuntimeException('Project is missing required catalog references.');
        }

        $template = PromptTemplate::query()
            ->where('product_id', $product->id)
            ->where('category_id', $category->id)
            ->where('style_id', $style->id)
            ->where('layout_id', $layout->id)
            ->first();

        if ($template === null) {
            throw new PromptTemplateMissingException(sprintf(
                'No prompt template exists for product=%s, category=%s, style=%s, layout=%s.',
                $product->slug,
                $category->slug,
                $style->slug,
                $layout->slug,
            ));
        }

        $inputs = is_array($project->inputs) ? $project->inputs : [];

        $name = (string) ($inputs['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Project inputs.name is required to assemble a prompt.');
        }

        $phrase = (string) ($inputs['phrase'] ?? '');
        $theme = (string) ($inputs['theme'] ?? '');
        $dedicatoria = (string) ($inputs['dedicatoria'] ?? '');
        $imageTags = '';

        $printSpecs = $this->renderPrintSpecs($mode?->slug, $product);

        $body = $template->body;

        $replacements = [
            '{{name}}' => $name,
            '{{phrase}}' => $phrase,
            '{{theme}}' => $theme,
            '{{image_tags}}' => $imageTags,
            '{{print_specs}}' => $printSpecs,
            '{{dedicatoria}}' => $dedicatoria,
        ];

        $prompt = strtr($body, $replacements);

        $dpi = (int) $product->min_dpi;
        $widthMm = (float) $product->print_width_mm;
        $heightMm = (float) $product->print_height_mm;

        $widthPx = (int) round($widthMm * $dpi / 25.4);
        $heightPx = (int) round($heightMm * $dpi / 25.4);

        $constraints = [
            'width' => $widthPx,
            'height' => $heightPx,
            'dpi' => $dpi,
            'safe_area_mm' => (float) $product->safe_area_mm,
            'print_width_mm' => $widthMm,
            'print_height_mm' => $heightMm,
            'min_dpi' => $dpi,
        ];

        return [
            'prompt' => $prompt,
            'constraints' => $constraints,
        ];
    }

    private function renderPrintSpecs(?string $modeSlug, $product): string
    {
        if ($modeSlug !== 'mug') {
            return '';
        }

        return sprintf(
            '%smm × %smm, %d DPI, %smm safe area',
            $product->print_width_mm,
            $product->print_height_mm,
            (int) $product->min_dpi,
            $product->safe_area_mm,
        );
    }
}
