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
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionStatus;
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
        'subscription_credit_grant' => ['name' => 'Subscription Credit Grant', 'expected_sign' => '+'],
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
        'edit_subscription_plan' => 'Edit Subscription Plan',
    ];

    /**
     * @var array<string, string>
     */
    private array $subscriptionIntervals = [
        'month' => 'Mensal',
        'year' => 'Anual',
    ];

    /**
     * @var array<string, string>
     */
    private array $subscriptionStatuses = [
        'active' => 'Ativo',
        'trialing' => 'Em trial',
        'past_due' => 'Pagamento atrasado',
        'canceled' => 'Cancelado',
        'incomplete' => 'Incompleto',
        'incomplete_expired' => 'Incompleto expirado',
        'unpaid' => 'Não pago',
        'paused' => 'Pausado',
    ];

    /**
     * @var array<string, array{name: string, prompt_fragment: string, negative_fragment: string, thumbnail_path: string|null, description: string}>
     */
    private array $styles = [
        'watercolor' => [
            'name' => 'Watercolor',
            'prompt_fragment' => 'rendered in soft watercolor with gentle washes and visible brushstrokes, warm color palette',
            'negative_fragment' => 'no harsh lines, no digital artifacts, no overly saturated colors',
            'thumbnail_path' => null,
            'description' => 'Soft washes and brushstrokes for a hand-painted feel.',
        ],
        'cartoon' => [
            'name' => 'Cartoon',
            'prompt_fragment' => 'modern cartoon illustration, vibrant colors, soft shading, bold outlines, flat colorful style',
            'negative_fragment' => 'no realistic textures, no photographic shading',
            'thumbnail_path' => null,
            'description' => 'Bold outlines and flat colors.',
        ],
        'realistic' => [
            'name' => 'Realistic',
            'prompt_fragment' => 'highly detailed semi-realistic digital portrait, realistic lighting and texture, photographic quality',
            'negative_fragment' => 'no flat shading, no cartoon outlines, no painterly strokes',
            'thumbnail_path' => null,
            'description' => 'Photo-realistic detail and lighting.',
        ],
        'pixel_art' => [
            'name' => 'Pixel Art',
            'prompt_fragment' => 'rendered as 16-bit pixel art with a limited palette, retro gaming aesthetic',
            'negative_fragment' => 'no smooth gradients, no anti-aliased edges, no high-resolution textures',
            'thumbnail_path' => null,
            'description' => 'Retro 16-bit pixel style.',
        ],
        'minimalist_line' => [
            'name' => 'Minimalist Line',
            'prompt_fragment' => 'rendered as minimalist single-line art on a clean background, elegant simplicity',
            'negative_fragment' => 'no fills, no shading, no color blocks, no complex detail',
            'thumbnail_path' => null,
            'description' => 'Single-line minimalist drawings.',
        ],
    ];

    /**
     * @var array<string, array{name: string, proportion_ratio: string, safe_area_overlay: array<string, int>|null, prompt_fragment: string}>
     */
    private array $layouts = [
        'centered' => [
            'name' => 'Centered',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => ['top_mm' => 5, 'bottom_mm' => 5, 'left_mm' => 5, 'right_mm' => 5],
            'prompt_fragment' => 'Main subject MUST be centered. Keep subject within central 60% of canvas width. Leave left and right areas lighter or decorative. Clean background. Do NOT place important elements near edges.',
        ],
        'border_wrap' => [
            'name' => 'Border Wrap',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => ['top_mm' => 8, 'bottom_mm' => 8, 'left_mm' => 8, 'right_mm' => 8],
            'prompt_fragment' => 'Artwork must fill entire width. No empty areas. Seamless edges (left and right must connect). Background should flow continuously. Composition balanced across canvas.',
        ],
        'full_bleed' => [
            'name' => 'Full Bleed',
            'proportion_ratio' => '1:1',
            'safe_area_overlay' => null,
            'prompt_fragment' => 'Create a repeating pattern. Small elements evenly distributed. Seamless horizontal tiling. Consistent spacing and style.',
        ],
        'split_top_bottom' => [
            'name' => 'Split Top-Bottom',
            'proportion_ratio' => '9:16',
            'safe_area_overlay' => ['top_mm' => 5, 'bottom_mm' => 5, 'left_mm' => 0, 'right_mm' => 0],
            'prompt_fragment' => 'Two compositions: left and right. Leave central 20% empty (handle area). Each side should have its own subject. Balanced composition. Avoid placing important elements in the center.',
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
     * @var array<string, array{scene_prompt: string, emotion_hint: string, lighting_hint: string, color_palette: string}>
     */
    private array $categoryEnrichment = [
        'birthday' => [
            'scene_prompt' => 'festive birthday celebration setting with party decorations and joyful crowd',
            'emotion_hint' => 'joyful, celebratory, full of excitement',
            'lighting_hint' => 'bright colorful party lights, warm glow from candles',
            'color_palette' => 'vibrant primary colors, candy pinks and blues, gold accents',
        ],
        'wedding' => [
            'scene_prompt' => 'romantic wedding ceremony setting with flowers and soft drapery',
            'emotion_hint' => 'tender, romantic, deeply emotional',
            'lighting_hint' => 'soft golden hour sunlight, romantic warm glow',
            'color_palette' => 'ivory, blush pink, sage green, gold accents',
        ],
        'pets' => [
            'scene_prompt' => 'playful pet environment with toys and natural elements',
            'emotion_hint' => 'playful, loving, full of personality',
            'lighting_hint' => 'natural daylight, soft window light',
            'color_palette' => 'warm earth tones, soft greens, gentle blues',
        ],
        'family' => [
            'scene_prompt' => 'cozy family gathering space with warm homely details',
            'emotion_hint' => 'warm, loving, comfortable togetherness',
            'lighting_hint' => 'soft natural light, warm indoor lighting',
            'color_palette' => 'soft earth tones, cream, sage, terracotta',
        ],
        'couples' => [
            'scene_prompt' => 'intimate setting for two with elegant romantic atmosphere',
            'emotion_hint' => 'intimate, romantic, deeply connected',
            'lighting_hint' => 'golden hour glow, candle-light, soft warm tones',
            'color_palette' => 'deep burgundy, gold, soft amber, cream',
        ],
        'kids' => [
            'scene_prompt' => 'whimsical playful environment with bright cheerful elements',
            'emotion_hint' => 'playful, curious, full of wonder',
            'lighting_hint' => 'bright daylight, soft pastel tones',
            'color_palette' => 'pastel rainbow, bright primary colors, soft yellows and pinks',
        ],
    ];

    /**
     * @var array<string, array{name: string, print_width_mm: float, print_height_mm: float, min_dpi: int, safe_area_mm: float, color_mode: string, product_prompt_rules: list<string>}>
     */
    private array $products = [
        'mug' => [
            'name' => 'Mug',
            'print_width_mm' => 220.00,
            'print_height_mm' => 95.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode' => 'rgb',
            'product_prompt_rules' => [
                'Horizontal wrap-around design',
                'Seamless left-right connection',
                'Full bleed from edge to edge',
                'Optimized for sublimation printing',
            ],
        ],
        'free_art' => [
            'name' => 'Free Art',
            'print_width_mm' => 210.00,
            'print_height_mm' => 297.00,
            'min_dpi' => 300,
            'safe_area_mm' => 5.00,
            'color_mode' => 'cmyk',
            'product_prompt_rules' => [
                'Standard portrait orientation',
                'Full canvas coverage',
                'CMYK color space',
            ],
        ],
    ];

    /**
     * @var array<string, array{name: string, sort_order: int, rich_description: string}>
     */
    private array $poses = [
        'abracados' => ['name' => 'Abraçados', 'sort_order' => 0, 'rich_description' => 'embracing couple with warm body language'],
        'beijo' => ['name' => 'Beijo', 'sort_order' => 1, 'rich_description' => 'kissing couple in romantic embrace'],
        'sentados' => ['name' => 'Sentados', 'sort_order' => 2, 'rich_description' => 'sitting side by side in relaxed pose'],
        'caminhando' => ['name' => 'Caminhando', 'sort_order' => 3, 'rich_description' => 'walking together hand in hand'],
        'natal' => ['name' => 'Natal', 'sort_order' => 4, 'rich_description' => 'festive Christmas holiday scene'],
        'praia' => ['name' => 'Praia', 'sort_order' => 5, 'rich_description' => 'beach scene with ocean waves'],
        'sofa' => ['name' => 'Sofá', 'sort_order' => 6, 'rich_description' => 'cozy living room sofa setting'],
        'flores' => ['name' => 'Flores', 'sort_order' => 7, 'rich_description' => 'surrounded by colorful flowers'],
    ];

    public function run(): void
    {
        $this->seedLookups();
        $this->seedGenerationLookups();
        $this->seedBillingLookups();
        $products = $this->seedProducts();
        $styles = $this->seedStyles();
        $layouts = $this->seedLayouts();
        $this->seedCategories($products['mug']);
        $this->seedCategories($products['free_art']);
        $this->seedCategoryStyles($styles);
        $this->seedStyleLayouts($styles, $layouts);
        $this->seedPoses();
        $this->seedPromptTemplates($products);

        $this->enrichCategories();
        $this->enrichLayouts();
        $this->enrichStyles();
        $this->enrichProducts();
    }

    private function enrichCategories(): void
    {
        $categories = Category::query()->get();

        foreach ($categories as $category) {
            $enrichment = $this->categoryEnrichment[$category->slug] ?? null;
            if ($enrichment === null) {
                continue;
            }

            Category::query()->where('id', $category->id)->update($enrichment);
        }
    }

    private function enrichLayouts(): void
    {
        foreach ($this->layouts as $slug => $row) {
            Layout::query()->where('slug', $slug)->update([
                'prompt_fragment' => $row['prompt_fragment'],
            ]);
        }
    }

    private function enrichStyles(): void
    {
        foreach ($this->styles as $slug => $row) {
            Style::query()->where('slug', $slug)->update([
                'negative_fragment' => $row['negative_fragment'],
            ]);
        }
    }

    private function enrichProducts(): void
    {
        foreach ($this->products as $slug => $row) {
            $product = Product::query()->where('slug', $slug)->first();
            if ($product === null) {
                continue;
            }
            $product->product_prompt_rules = $row['product_prompt_rules'];
            $product->save();
        }
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

    private function seedBillingLookups(): void
    {
        foreach ($this->subscriptionIntervals as $slug => $name) {
            SubscriptionInterval::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        foreach ($this->subscriptionStatuses as $slug => $name) {
            SubscriptionStatus::firstOrCreate(['slug' => $slug], ['name' => $name]);
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
                'product_prompt_rules' => $row['product_prompt_rules'],
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
                'rich_description' => $row['rich_description'],
            ]);

            Pose::query()->where('slug', $slug)->update([
                'rich_description' => $row['rich_description'],
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
                'negative_fragment' => $row['negative_fragment'],
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
                'prompt_fragment' => $row['prompt_fragment'],
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
