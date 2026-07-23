<?php

use App\Models\Category;
use App\Models\Layout;
use App\Models\Pose;
use App\Models\PoseStatus;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use Database\Seeders\CatalogSeeder;

test('seeder is idempotent and can run twice without duplicating', function (): void {
    (new CatalogSeeder)->run();
    $posesAfterFirst = Pose::count();

    (new CatalogSeeder)->run();
    $posesAfterSecond = Pose::count();

    expect($posesAfterSecond)->toBe($posesAfterFirst);
});

test('seeder seeds exactly 8 poses', function (): void {
    (new CatalogSeeder)->run();

    expect(Pose::count())->toBe(8);
});

test('seeder seeds the free_art product', function (): void {
    (new CatalogSeeder)->run();

    expect(Product::where('slug', 'free_art')->exists())->toBeTrue();
});

test('seeder creates only active poses', function (): void {
    (new CatalogSeeder)->run();
    $activeId = PoseStatus::where('slug', 'active')->value('id');

    expect(Pose::where('status_id', $activeId)->count())->toBe(8);
});

test('prompt templates contain the new placeholders', function (): void {
    (new CatalogSeeder)->run();
    $body = PromptTemplate::query()->value('body');

    expect($body)->toContain('{{subject_type}}')
        ->toContain('{{pose}}')
        ->toContain('{{custom_prompt}}')
        ->toContain('{{name}}')
        ->toContain('{{phrase}}')
        ->toContain('{{print_specs}}');
});

test('seeder creates 240 prompt templates (2 products * 6 categories * 5 styles * 4 layouts)', function (): void {
    (new CatalogSeeder)->run();

    expect(PromptTemplate::count())->toBe(240);
});

test('seeder populates enriched category fields', function (): void {
    (new CatalogSeeder)->run();

    foreach (['birthday', 'wedding', 'pets', 'family', 'couples', 'kids'] as $slug) {
        $category = Category::where('slug', $slug)->first();
        expect($category)->not->toBeNull();
        expect($category->scene_prompt)->not->toBeNull()->not->toBeEmpty();
        expect($category->emotion_hint)->not->toBeNull()->not->toBeEmpty();
        expect($category->lighting_hint)->not->toBeNull()->not->toBeEmpty();
        expect($category->color_palette)->not->toBeNull()->not->toBeEmpty();
    }
});

test('seeder populates layout prompt_fragment', function (): void {
    (new CatalogSeeder)->run();

    foreach (['centered', 'border_wrap', 'full_bleed', 'split_top_bottom'] as $slug) {
        $layout = Layout::where('slug', $slug)->first();
        expect($layout)->not->toBeNull();
        expect($layout->prompt_fragment)->not->toBeNull()->not->toBeEmpty();
    }
});

test('seeder populates style negative_fragment', function (): void {
    (new CatalogSeeder)->run();

    foreach (['watercolor', 'cartoon', 'realistic', 'pixel_art', 'minimalist_line'] as $slug) {
        $style = Style::where('slug', $slug)->first();
        expect($style)->not->toBeNull();
        expect($style->negative_fragment)->not->toBeNull()->not->toBeEmpty();
    }
});

test('seeder populates product_prompt_rules as JSON', function (): void {
    (new CatalogSeeder)->run();

    foreach (['mug', 'free_art'] as $slug) {
        $product = Product::where('slug', $slug)->first();
        expect($product)->not->toBeNull();
        expect($product->product_prompt_rules)->toBeArray()
            ->and($product->product_prompt_rules)->not->toBeEmpty();
    }
});

test('seeder populates pose rich_description', function (): void {
    (new CatalogSeeder)->run();

    foreach (['abracados', 'beijo', 'sentados', 'caminhando', 'natal', 'praia', 'sofa', 'flores'] as $slug) {
        $pose = Pose::where('slug', $slug)->first();
        expect($pose)->not->toBeNull();
        expect($pose->rich_description)->not->toBeNull()->not->toBeEmpty();
    }
});
