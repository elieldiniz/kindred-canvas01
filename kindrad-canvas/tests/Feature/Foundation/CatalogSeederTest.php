<?php

use App\Models\Category;
use App\Models\ColorMode;
use App\Models\Layout;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\ProjectMode;
use App\Models\ProjectStatus;
use App\Models\Style;
use Database\Seeders\CatalogSeeder;

test('seeder is idempotent and produces the expected catalog', function (): void {
    $this->seed(CatalogSeeder::class);
    $this->seed(CatalogSeeder::class);

    expect(Product::where('slug', 'mug')->exists())->toBeTrue();
    expect(ProductStatus::where('slug', 'active')->exists())->toBeTrue();
    expect(ProductStatus::where('slug', 'inactive')->exists())->toBeTrue();
    expect(ColorMode::where('slug', 'rgb')->exists())->toBeTrue();
    expect(ColorMode::where('slug', 'cmyk')->exists())->toBeTrue();
    expect(ProjectStatus::where('slug', 'draft')->exists())->toBeTrue();
    expect(ProjectStatus::where('slug', 'active')->exists())->toBeTrue();
    expect(ProjectStatus::where('slug', 'archived')->exists())->toBeTrue();
    expect(ProjectMode::where('slug', 'free')->exists())->toBeTrue();
    expect(ProjectMode::where('slug', 'mug')->exists())->toBeTrue();

    expect(Category::where('slug', 'birthday')->exists())->toBeTrue();
    expect(Category::where('slug', 'wedding')->exists())->toBeTrue();
    expect(Category::where('slug', 'pets')->exists())->toBeTrue();
    expect(Category::where('slug', 'family')->exists())->toBeTrue();
    expect(Category::where('slug', 'couples')->exists())->toBeTrue();
    expect(Category::where('slug', 'kids')->exists())->toBeTrue();

    $mug = Product::where('slug', 'mug')->firstOrFail();
    expect(Category::where('product_id', $mug->id)->count())->toBe(6);
    expect(Style::whereIn('slug', ['watercolor', 'cartoon', 'realistic', 'pixel_art', 'minimalist_line'])->count())->toBe(5);
    expect(Layout::whereIn('slug', ['centered', 'border_wrap', 'full_bleed', 'split_top_bottom'])->count())->toBe(4);

    $styleIds = Style::pluck('id')->all();
    $layoutIds = Layout::pluck('id')->all();
    $categoryIds = Category::pluck('id')->all();

    expect(DB::table('category_styles')->whereIn('category_id', $categoryIds)->count())->toBe(30);
    expect(DB::table('style_layouts')->whereIn('style_id', $styleIds)->count())->toBe(20);
});
