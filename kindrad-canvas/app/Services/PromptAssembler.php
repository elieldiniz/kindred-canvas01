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
        $customPrompt = (string) ($project->custom_prompt ?? '');

        $printSpecs = $this->renderPrintSpecs($mode?->slug, $product);
        $layoutInstructions = $this->renderLayoutInstructions($layout->slug);
        $subjectType = $project->subject_type ? __('articles.subject_type.'.$project->subject_type) : '';
        $pose = $project->pose?->name ?? '';

        $body = $template->body;

        $replacements = [
            '{{name}}' => $name,
            '{{phrase}}' => $phrase,
            '{{theme}}' => $theme,
            '{{image_tags}}' => $imageTags,
            '{{print_specs}}' => $printSpecs,
            '{{dedicatoria}}' => $dedicatoria,
            '{{custom_prompt}}' => $customPrompt,
            '{{subject_type}}' => $subjectType,
            '{{pose}}' => $pose,
            '{{layout_instructions}}' => $layoutInstructions,
            '{{style_description}}' => $style->prompt_fragment,
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
            'Horizontal layout (landscape), aspect ratio 2.5:1, resolution %dx%dpx, optimized for sublimation printing, %smm × %smm at %d DPI, %smm safe area',
            (int) round((float) $product->print_width_mm * (int) $product->min_dpi / 25.4),
            (int) round((float) $product->print_height_mm * (int) $product->min_dpi / 25.4),
            (int) $product->min_dpi,
            $product->print_width_mm,
            $product->print_height_mm,
            (int) $product->min_dpi,
            $product->safe_area_mm,
        );
    }

    private function renderLayoutInstructions(?string $layoutSlug): string
    {
        $instructions = [
            'centered' => 'Main subject MUST be centered. Keep subject within central 60% of canvas width. Leave left and right areas lighter or decorative. Clean background. Do NOT place important elements near edges.',
            'border_wrap' => 'Artwork must fill entire width. No empty areas. Seamless edges (left and right must connect). Background should flow continuously. Composition balanced across canvas.',
            'full_bleed' => 'Create a repeating pattern. Small elements evenly distributed. Seamless horizontal tiling. Consistent spacing and style.',
            'split_top_bottom' => 'Two compositions: left and right. Leave central 20% empty (handle area). Each side should have its own subject. Balanced composition. Avoid placing important elements in the center.',
        ];

        return $instructions[$layoutSlug] ?? '';
    }
}
