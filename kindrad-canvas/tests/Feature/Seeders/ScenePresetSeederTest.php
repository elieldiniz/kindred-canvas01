<?php

use App\Models\Category;
use App\Models\ScenePreset;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\ScenePresetSeeder;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('seeder creates 3-4 presets per category', function (): void {
    $this->seed(ScenePresetSeeder::class);

    $categories = Category::query()->get();

    foreach ($categories as $category) {
        $count = ScenePreset::query()->where('category_id', $category->id)->count();
        expect($count)->toBeGreaterThanOrEqual(3)
            ->and($count)->toBeLessThanOrEqual(4);
    }
});

test('seeder is idempotent', function (): void {
    $this->seed(ScenePresetSeeder::class);
    $firstCount = ScenePreset::query()->count();

    $this->seed(ScenePresetSeeder::class);
    $secondCount = ScenePreset::query()->count();

    expect($secondCount)->toBe($firstCount);
});

test('seeder creates 3-4 presets per category (across product variants)', function (): void {
    $this->seed(ScenePresetSeeder::class);

    $categories = Category::query()->get();

    foreach ($categories as $category) {
        $count = ScenePreset::query()->where('category_id', $category->id)->count();
        expect($count)->toBeGreaterThanOrEqual(3)
            ->and($count)->toBeLessThanOrEqual(4);
    }
});

test('ScenePreset belongs to a category', function (): void {
    $this->seed(ScenePresetSeeder::class);

    $preset = ScenePreset::query()->first();

    expect($preset->category)->toBeInstanceOf(Category::class);
});
