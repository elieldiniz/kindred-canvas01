<?php

use App\Models\Layout;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Services\PromptEngine\Modules\CompositionModule;
use App\Services\PromptEngine\Modules\LayoutModule;
use App\Services\PromptEngine\Modules\NegativePromptModule;
use App\Services\PromptEngine\Modules\PrintSpecsModule;
use App\Services\PromptEngine\Modules\StyleModule;
use App\Services\PromptEngine\PromptEngine;
use App\Services\PromptEngine\PromptFragment;
use App\Services\PromptEngine\PromptModule;

function stubProject(): Project
{
    $product = new Product;
    $product->id = 1;
    $product->min_dpi = 300;
    $product->print_width_mm = '220.00';
    $product->print_height_mm = '95.00';
    $product->safe_area_mm = '5.00';

    $project = new Project;
    $project->id = 1;
    $project->product = $product;
    $project->inputs = [];
    $project->subject_type = null;
    $project->custom_prompt = null;
    $project->pose_id = null;
    $project->pose = null;
    $project->category = null;
    $project->style = null;
    $project->layout = null;
    $project->mode = null;
    $project->scene_preset_id = null;
    $project->scenePreset = null;

    return $project;
}

test('assemble returns prompt and constraints', function (): void {
    $engine = new PromptEngine([]);

    $result = $engine->assemble(stubProject());

    expect($result)->toHaveKeys(['prompt', 'constraints'])
        ->and($result['constraints'])->toHaveKeys(['width', 'height']);
});

test('fragments are sorted by priority descending', function (): void {
    $lowModule = new class implements PromptModule
    {
        public function fragment(Project $project): ?PromptFragment
        {
            return new PromptFragment(text: 'LOW', priority: 10);
        }
    };
    $highModule = new class implements PromptModule
    {
        public function fragment(Project $project): ?PromptFragment
        {
            return new PromptFragment(text: 'HIGH', priority: 100);
        }
    };

    $engine = new PromptEngine([$lowModule, $highModule]);

    $result = $engine->assemble(stubProject());

    expect($result['prompt'])->toStartWith('HIGH')
        ->and($result['prompt'])->toContain('LOW');
});

test('negative fragments are merged into a single Avoid block', function (): void {
    $module = new class implements PromptModule
    {
        public function fragment(Project $project): ?PromptFragment
        {
            return new PromptFragment(text: 'VISIBLE', priority: 50, negativeFragment: 'no blur, no distortion');
        }
    };

    $engine = new PromptEngine([$module]);

    $result = $engine->assemble(stubProject());

    expect($result['prompt'])->toContain('VISIBLE')
        ->and($result['prompt'])->toContain('Avoid:')
        ->and($result['prompt'])->toContain('no blur')
        ->and($result['prompt'])->toContain('no distortion');
});

test('null fragments are skipped', function (): void {
    $module = new class implements PromptModule
    {
        public function fragment(Project $project): ?PromptFragment
        {
            return null;
        }
    };
    $visible = new class implements PromptModule
    {
        public function fragment(Project $project): ?PromptFragment
        {
            return new PromptFragment(text: 'visible', priority: 1);
        }
    };

    $engine = new PromptEngine([$module, $visible]);

    $result = $engine->assemble(stubProject());

    expect($result['prompt'])->toContain('visible');
});

test('NegativePromptModule produces an Avoid block', function (): void {
    $module = new NegativePromptModule;
    $fragment = $module->fragment(stubProject());

    expect($fragment)->not->toBeNull()
        ->and($fragment->text)->toContain('Avoid:');
});

test('PrintSpecsModule returns null when mode is not mug', function (): void {
    $module = new PrintSpecsModule;

    $project = stubProject();
    $freeMode = new ProjectMode;
    $freeMode->slug = 'free';
    $project->mode = $freeMode;

    expect($module->fragment($project))->toBeNull();
});

test('PrintSpecsModule returns pixel dimensions for mug', function (): void {
    $module = new PrintSpecsModule;

    $project = stubProject();
    $mug = new ProjectMode;
    $mug->slug = 'mug';
    $project->mode = $mug;

    $fragment = $module->fragment($project);

    expect($fragment)->not->toBeNull()
        ->and($fragment->text)->toContain('2598')
        ->and($fragment->text)->toContain('1122');
});

test('LayoutModule reads prompt_fragment from Layout', function (): void {
    $module = new LayoutModule;
    $layout = new Layout;
    $layout->prompt_fragment = 'centered composition';

    $project = stubProject();
    $project->layout = $layout;

    $fragment = $module->fragment($project);

    expect($fragment)->not->toBeNull()
        ->and($fragment->text)->toContain('centered composition');
});

test('StyleModule emits prompt_fragment and negative_fragment', function (): void {
    $module = new StyleModule;
    $style = new Style;
    $style->prompt_fragment = 'watercolor style';
    $style->negative_fragment = 'no harsh lines';

    $project = stubProject();
    $project->style = $style;

    $fragment = $module->fragment($project);

    expect($fragment)->not->toBeNull()
        ->and($fragment->text)->toContain('watercolor')
        ->and($fragment->negativeFragment)->toContain('no harsh lines');
});

test('CompositionModule returns null for unknown layout', function (): void {
    $module = new CompositionModule;
    $layout = new Layout;
    $layout->slug = 'unknown';

    $project = stubProject();
    $project->layout = $layout;

    expect($module->fragment($project))->toBeNull();
});
