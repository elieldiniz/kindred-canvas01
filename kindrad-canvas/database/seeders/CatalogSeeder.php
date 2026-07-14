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
use App\Models\Pose;
use App\Models\PoseStatus;
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
     * @var array<string, array{name: string, prompt_fragment: string, thumbnail_path: string|null, description: string}>
     */
    private array $styles = [
        'watercolor' => [
            'name' => 'Watercolor',
            'prompt_fragment' => 'rendered in soft watercolor with gentle washes and visible brushstrokes, warm color palette',
            'thumbnail_path' => null,
            'description' => 'Soft washes and brushstrokes for a hand-painted feel.',
        ],
        'cartoon' => [
            'name' => 'Cartoon',
            'prompt_fragment' => 'modern cartoon illustration, vibrant colors, soft shading, bold outlines, flat colorful style',
            'thumbnail_path' => null,
            'description' => 'Bold outlines and flat colors.',
        ],
        'realistic' => [
            'name' => 'Realistic',
            'prompt_fragment' => 'highly detailed semi-realistic digital portrait, realistic lighting and texture, photographic quality',
            'thumbnail_path' => null,
            'description' => 'Photo-realistic detail and lighting.',
        ],
        'pixel_art' => [
            'name' => 'Pixel Art',
            'prompt_fragment' => 'rendered as 16-bit pixel art with a limited palette, retro gaming aesthetic',
            'thumbnail_path' => null,
            'description' => 'Retro 16-bit pixel style.',
        ],
        'minimalist_line' => [
            'name' => 'Minimalist Line',
            'prompt_fragment' => 'rendered as minimalist single-line art on a clean background, elegant simplicity',
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

    /**
     * @var array<string, array{name: string, print_width_mm: float, print_height_mm: float, min_dpi: int, safe_area_mm: float, color_mode: string}>
     */
    private array $products = [
        'mug' => [
            'name' => 'Mug',
            'print_width_mm' => 220.00,
            'print_height_mm' => 95.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode' => 'rgb',
        ],
        'free_art' => [
            'name' => 'Free Art',
            'print_width_mm' => 210.00,
            'print_height_mm' => 297.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode' => 'cmyk',
        ],
    ];

    /**
     * @var array<string, array{name: string, sort_order: int}>
     */
    private array $poses = [
        'abracados' => ['name' => 'Abraçados', 'sort_order' => 0],
        'beijo' => ['name' => 'Beijo', 'sort_order' => 1],
        'sentados' => ['name' => 'Sentados', 'sort_order' => 2],
        'caminhando' => ['name' => 'Caminhando', 'sort_order' => 3],
        'natal' => ['name' => 'Natal', 'sort_order' => 4],
        'praia' => ['name' => 'Praia', 'sort_order' => 5],
        'sofa' => ['name' => 'Sofá', 'sort_order' => 6],
        'flores' => ['name' => 'Flores', 'sort_order' => 7],
    ];

    public function run(): void
    {
        $this->seedLookups();
        $this->seedGenerationLookups();
        $products = $this->seedProducts();
        $styles = $this->seedStyles();
        $layouts = $this->seedLayouts();
        $this->seedCategories($products['mug']);
        $this->seedCategories($products['free_art']);
        $this->seedCategoryStyles($styles);
        $this->seedStyleLayouts($styles, $layouts);
        $this->seedPoses();
        $this->seedPromptTemplates($products);
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

    /**
     * @return array<string, Product>
     */
    private function seedProducts(): array
    {
        $active = ProductStatus::where('slug', 'active')->firstOrFail();
        $models = [];

        foreach ($this->products as $slug => $row) {
            $colorMode = ColorMode::where('slug', $row['color_mode'])->firstOrFail();
            $models[$slug] = Product::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'status_id' => $active->id,
                'print_width_mm' => $row['print_width_mm'],
                'print_height_mm' => $row['print_height_mm'],
                'min_dpi' => $row['min_dpi'],
                'safe_area_mm' => $row['safe_area_mm'],
                'color_mode_id' => $colorMode->id,
            ]);
        }

        return $models;
    }

    /**
     * @return array<string, Pose>
     */
    private function seedPoses(): array
    {
        $active = PoseStatus::where('slug', 'active')->firstOrFail();
        $models = [];

        foreach ($this->poses as $slug => $row) {
            $models[$slug] = Pose::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'thumbnail_path' => null,
                'status_id' => $active->id,
                'sort_order' => $row['sort_order'],
            ]);
        }

        return $models;
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
        $categories = Category::all();

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

    /**
     * @param  array<string, Product>  $products
     */
    private function seedPromptTemplates(array $products): void
    {
        $body = 'Create a {{subject_type}} portrait in the {{pose}} pose for {{name}}.

{{phrase}} {{theme}} {{dedicatoria}} {{custom_prompt}}

Style: {{style_description}}.

{{layout_instructions}}

{{print_specs}}

Preserve the subject identity and key features. Maintain strong composition and high visual quality.';

        $categories = Category::whereIn('product_id', collect($products)->pluck('id'))->get();
        $styles = Style::all();
        $layouts = Layout::all();

        foreach ($products as $product) {
            $productCategories = $categories->where('product_id', $product->id);

            foreach ($productCategories as $category) {
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
}
