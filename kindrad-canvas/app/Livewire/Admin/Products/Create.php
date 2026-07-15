<?php

namespace App\Livewire\Admin\Products;

use App\Models\ColorMode;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $slug = '';

    public ?int $status_id = null;

    public float $print_width_mm = 100;

    public float $print_height_mm = 100;

    public int $min_dpi = 300;

    public float $safe_area_mm = 5.0;

    public ?int $color_mode_id = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'status_id' => ['required', 'exists:product_statuses,id'],
            'print_width_mm' => ['required', 'numeric', 'min:1'],
            'print_height_mm' => ['required', 'numeric', 'min:1'],
            'min_dpi' => ['required', 'integer', 'min:72'],
            'safe_area_mm' => ['required', 'numeric', 'min:0'],
            'color_mode_id' => ['required', 'exists:color_modes,id'],
        ]);

        $product = Product::create($this->only([
            'name', 'slug', 'status_id', 'print_width_mm',
            'print_height_mm', 'min_dpi', 'safe_area_mm', 'color_mode_id',
        ]));

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_product',
            target: $product,
            payload: ['event' => 'created', 'attributes' => $product->only([
                'name', 'slug', 'status_id', 'print_width_mm',
                'print_height_mm', 'min_dpi', 'safe_area_mm', 'color_mode_id',
            ])],
        );

        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.create', [
            'statuses' => ProductStatus::orderBy('name')->get(),
            'colorModes' => ColorMode::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Create Product'),
        ]);
    }
}
