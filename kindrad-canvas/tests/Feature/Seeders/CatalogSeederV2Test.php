<?php

use App\Models\Pose;
use App\Models\PoseStatus;
use App\Models\Product;
use App\Models\PromptTemplate;
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
