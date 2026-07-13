<?php

use App\Models\Category;
use App\Models\Layout;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\User;
use App\Services\PromptAssembler;
use App\Services\PromptTemplateMissingException;
use Database\Seeders\CatalogSeeder;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('substitutes all placeholders', function (): void {
    $user = User::factory()->create();
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    PromptTemplate::query()->updateOrInsert(
        [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'style_id' => $style->id,
            'layout_id' => $layout->id,
        ],
        [
            'body' => 'Hello {{name}} phrase={{phrase}} theme={{theme}} tags={{image_tags}} specs={{print_specs}}',
            'version' => 1,
        ],
    );

    $project = Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => [
                'name' => 'Alice',
                'phrase' => 'happy',
                'theme' => 'forest',
                'dedicatoria' => 'for mom',
            ],
        ]);

    $result = app(PromptAssembler::class)->assemble($project);

    expect($result['prompt'])->toContain('Alice')
        ->and($result['prompt'])->toContain('phrase=happy')
        ->and($result['prompt'])->toContain('theme=forest');
});

test('mug mode includes print specs', function (): void {
    $user = User::factory()->create();
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    PromptTemplate::query()->updateOrInsert(
        [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'style_id' => $style->id,
            'layout_id' => $layout->id,
        ],
        [
            'body' => 'for {{name}} {{print_specs}}',
            'version' => 1,
        ],
    );

    $project = Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);

    $result = app(PromptAssembler::class)->assemble($project);

    expect($result['prompt'])->toContain('mm')
        ->and($result['prompt'])->toContain('DPI');
});

test('free mode omits print specs', function (): void {
    $user = User::factory()->create();
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $free = ProjectMode::where('slug', 'free')->firstOrFail();

    PromptTemplate::query()->updateOrInsert(
        [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'style_id' => $style->id,
            'layout_id' => $layout->id,
        ],
        [
            'body' => 'A|for {{name}} {{print_specs}}',
            'version' => 1,
        ],
    );

    $project = Project::factory()
        ->withMode($free->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);

    $result = app(PromptAssembler::class)->assemble($project);

    expect($result['prompt'])->toContain('A|for Alice');
    expect(trim(explode('Alice', $result['prompt'])[1] ?? ''))->toBe('');
});

test('missing prompt template throws', function (): void {
    $user = User::factory()->create();
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    PromptTemplate::query()->delete();

    $project = Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);

    app(PromptAssembler::class)->assemble($project);
})->throws(PromptTemplateMissingException::class);

test('constraints snapshot includes pixel dimensions', function (): void {
    $user = User::factory()->create();
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    PromptTemplate::query()->updateOrInsert(
        [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'style_id' => $style->id,
            'layout_id' => $layout->id,
        ],
        [
            'body' => 'Hello {{name}}',
            'version' => 1,
        ],
    );

    $project = Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);

    $result = app(PromptAssembler::class)->assemble($project);

    $dpi = (int) $product->min_dpi;
    $expectedW = (int) round((float) $product->print_width_mm * $dpi / 25.4);
    $expectedH = (int) round((float) $product->print_height_mm * $dpi / 25.4);

    expect($result['constraints']['width'])->toBe($expectedW)
        ->and($result['constraints']['height'])->toBe($expectedH)
        ->and($result['constraints']['width'])->toBeGreaterThan(0)
        ->and($result['constraints']['height'])->toBeGreaterThan(0);
});
