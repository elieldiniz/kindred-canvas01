<?php

namespace Database\Seeders;

use App\Models\AuditLogAction;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\ColorMode;
use App\Models\CreditTransactionReason;
use App\Models\GenerationProvider;
use App\Models\GenerationStatus;
use App\Models\Layout;
use App\Models\LayoutStatus;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\ProjectMode;
use App\Models\ProjectStatus;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\StyleStatus;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $projectStatuses = [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ];

    /**
     * @var array<string, array<string, string|bool>>
     */
    private array $projectModes = [
        'free' => ['name' => 'Free', 'injects_print_specs' => false],
        'mug' => ['name' => 'Mug', 'injects_print_specs' => true],
    ];

    /**
     * @var array<string, string>
     */
    private array $productStatuses = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    /**
     * @var array<string, string>
     */
    private array $categoryStatuses = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    /**
     * @var array<string, string>
     */
    private array $styleStatuses = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    /**
     * @var array<string, string>
     */
    private array $layoutStatuses = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    /**
     * @var array<string, string>
     */
    private array $colorModes = [
        'rgb' => 'RGB',
        'cmyk' => 'CMYK',
    ];

    /**
     * @var array<string, string>
     */
    private array $generationStatuses = [
        'waiting' => 'Waiting',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ];

    /**
     * @var array<string, array{name: string, driver_class: string, is_active: bool}>
     */
    private array $generationProviders = [
        'openai' => ['name' => 'OpenAI', 'driver_class' => 'App\\Services\\Generation\\OpenAIProvider', 'is_active' => true],
        'gemini' => ['name' => 'Google Gemini', 'driver_class' => 'App\\Services\\Generation\\GeminiProvider', 'is_active' => false],
        'replicate' => ['name' => 'Replicate', 'driver_class' => 'App\\Services\\Generation\\ReplicateProvider', 'is_active' => false],
    ];

    /**
     * @var array<string, array{name: string, expected_sign: string}>
     */
    private array $creditTransactionReasons = [
        'signup_grant' => ['name' => 'Signup Grant', 'expected_sign' => '+'],
        'generation_debit' => ['name' => 'Generation Debit', 'expected_sign' => '-'],
        'generation_refund' => ['name' => 'Generation Refund', 'expected_sign' => '+'],
        'admin_grant' => ['name' => 'Admin Grant', 'expected_sign' => '+'],
    ];

    /**
     * @var array<string, string>
     */
    private array $auditLogActions = [
        'toggle_admin' => 'Toggle Admin',
        'grant_credits' => 'Grant Credits',
        'edit_product' => 'Edit Product',
        'edit_category' => 'Edit Category',
        'edit_style' => 'Edit Style',
        'edit_layout' => 'Edit Layout',
        'edit_prompt_template' => 'Edit Prompt Template',
    ];

    /**
     * @var array<string, array{name: string, description: string, prompt_fragment: string, thumbnail_path: string|null}>
     */
    private array $styles = [
        'watercolor' => [
            'name' => 'Watercolor',
            'prompt_fragment' => 'rendered in soft watercolor with gentle washes and visible brushstrokes',
            'thumbnail_path' => null,
            'description' => 'Soft washes and brushstrokes for a hand-painted feel.',
        ],
        'cartoon' => [
            'name' => 'Cartoon',
            'prompt_fragment' => 'rendered as a flat colorful cartoon with bold outlines',
            'thumbnail_path' => null,
            'description' => 'Bold outlines and flat colors.',
        ],
        'realistic' => [
            'name' => 'Realistic',
            'prompt_fragment' => 'rendered photographically with realistic lighting and texture',
            'thumbnail_path' => null,
            'description' => 'Photo-realistic detail and lighting.',
        ],
        'pixel_art' => [
            'name' => 'Pixel Art',
            'prompt_fragment' => 'rendered as 16-bit pixel art with a limited palette',
            'thumbnail_path' => null,
            'description' => 'Retro 16-bit pixel style.',
        ],
        'minimalist_line' => [
            'name' => 'Minimalist Line',
            'prompt_fragment' => 'rendered as minimalist single-line art on a clean background',
            'thumbnail_path' => null,
            'description' => 'Single-line minimalist drawings.',
        ],
    ];

    /**
     * @var array<string, array{name: string, proportion_ratio: string, safe_area_overlay: array<string, int>|null}>
     */
    private array $layouts = [
        'centered' => [
            'name' => 'Centered',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => ['top_mm' => 5, 'bottom_mm' => 5, 'left_mm' => 5, 'right_mm' => 5],
        ],
        'border_wrap' => [
            'name' => 'Border Wrap',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => ['top_mm' => 8, 'bottom_mm' => 8, 'left_mm' => 8, 'right_mm' => 8],
        ],
        'full_bleed' => [
            'name' => 'Full Bleed',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => null,
        ],
        'split_top_bottom' => [
            'name' => 'Split Top-Bottom',
            'proportion_ratio' => '9:16',
            'safe_area_overlay' => ['top_mm' => 5, 'bottom_mm' => 5, 'left_mm' => 0, 'right_mm' => 0],
        ],
    ];

    /**
     * @var list<string>
     */
    private array $categories = [
        'birthday',
        'wedding',
        'pets',
        'family',
        'couples',
        'kids',
    ];

    public function run(): void
    {
        $this->seedLookups();
        $this->seedGenerationLookups();
        $product = $this->seedProducts();
        $styles = $this->seedStyles();
        $layouts = $this->seedLayouts();
        $this->seedCategories($product);
        $this->seedCategoryStyles($styles);
        $this->seedStyleLayouts($styles, $layouts);
        $this->seedPromptTemplates($product);
    }

    private function seedGenerationLookups(): void
    {
        foreach ($this->generationStatuses as $slug => $name) {
            GenerationStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->generationProviders as $slug => $row) {
            GenerationProvider::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'driver_class' => $row['driver_class'],
                'is_active' => $row['is_active'],
            ]);
        }

        foreach ($this->creditTransactionReasons as $slug => $row) {
            CreditTransactionReason::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'expected_sign' => $row['expected_sign'],
            ]);
        }

        foreach ($this->auditLogActions as $slug => $name) {
            AuditLogAction::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }

    private function seedLookups(): void
    {
        foreach ($this->projectStatuses as $slug => $name) {
            ProjectStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->projectModes as $slug => $row) {
            ProjectMode::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'injects_print_specs' => $row['injects_print_specs'],
            ]);
        }

        foreach ($this->productStatuses as $slug => $name) {
            ProductStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->categoryStatuses as $slug => $name) {
            CategoryStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->styleStatuses as $slug => $name) {
            StyleStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->layoutStatuses as $slug => $name) {
            LayoutStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->colorModes as $slug => $name) {
            ColorMode::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }

    private function seedProducts(): Product
    {
        $active = ProductStatus::where('slug', 'active')->firstOrFail();
        $rgb = ColorMode::where('slug', 'rgb')->firstOrFail();

        return Product::firstOrCreate(['slug' => 'mug'], [
            'name' => 'Mug',
            'status_id' => $active->id,
            'print_width_mm' => 220.00,
            'print_height_mm' => 95.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode_id' => $rgb->id,
        ]);
    }

    /**
     * @return array<string, Style>
     */
    private function seedStyles(): array
    {
        $active = StyleStatus::where('slug', 'active')->firstOrFail();
        $models = [];

        foreach ($this->styles as $slug => $row) {
            $models[$slug] = Style::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'prompt_fragment' => $row['prompt_fragment'],
                'thumbnail_path' => $row['thumbnail_path'],
                'status_id' => $active->id,
            ]);
        }

        return $models;
    }

    /**
     * @return array<string, Layout>
     */
    private function seedLayouts(): array
    {
        $active = LayoutStatus::where('slug', 'active')->firstOrFail();
        $models = [];

        foreach ($this->layouts as $slug => $row) {
            $models[$slug] = Layout::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'preview_path' => null,
                'safe_area_overlay' => $row['safe_area_overlay'],
                'proportion_ratio' => $row['proportion_ratio'],
                'status_id' => $active->id,
            ]);
        }

        return $models;
    }

    private function seedCategories(Product $product): void
    {
        $active = CategoryStatus::where('slug', 'active')->firstOrFail();

        foreach ($this->categories as $index => $slug) {
            Category::firstOrCreate(
                ['product_id' => $product->id, 'slug' => $slug],
                [
                    'name' => ucfirst(str_replace('_', ' ', $slug)),
                    'description' => 'Default description for '.$slug.' category.',
                    'thumbnail_path' => null,
                    'status_id' => $active->id,
                    'sort_order' => $index,
                ],
            );
        }
    }

    /**
     * @param  array<string, Style>  $styles
     */
    private function seedCategoryStyles(array $styles): void
    {
        $categories = Category::whereHas('product', fn ($q) => $q->where('slug', 'mug'))->get();

        foreach ($categories as $category) {
            foreach (array_keys($this->styles) as $styleSlug) {
                $style = $styles[$styleSlug] ?? null;
                if ($style === null) {
                    continue;
                }

                $category->styles()->syncWithoutDetaching([$style->id]);
            }
        }
    }

    /**
     * @param  array<string, Style>  $styles
     * @param  array<string, Layout>  $layouts
     */
    private function seedStyleLayouts(array $styles, array $layouts): void
    {
        foreach ($styles as $style) {
            foreach ($layouts as $layout) {
                $style->layouts()->syncWithoutDetaching([$layout->id]);
            }
        }
    }

    private function seedPromptTemplates(Product $product): void
    {
        $body = 'A personalized design for {{name}}. {{phrase}} {{theme}} {{image_tags}} {{print_specs}} {{dedicatoria}}';

        $categories = Category::where('product_id', $product->id)->get();
        $styles = Style::all();
        $layouts = Layout::all();

        foreach ($categories as $category) {
            foreach ($styles as $style) {
                foreach ($layouts as $layout) {
                    PromptTemplate::firstOrCreate([
                        'product_id' => $product->id,
                        'category_id' => $category->id,
                        'style_id' => $style->id,
                        'layout_id' => $layout->id,
                    ], [
                        'body' => $body,
                        'version' => 1,
                    ]);
                }
            }
        }
    }
}
