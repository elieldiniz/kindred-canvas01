<?php

namespace App\Livewire\Admin\Products;

use App\Models\ColorMode;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Services\AuditLogger;
use Livewire\Component;

class Edit extends Component
{
    public Product $productModel;

    public string $name = '';

    public string $slug = '';

    public ?int $status_id = null;

    public float $print_width_mm = 0;

    public float $print_height_mm = 0;

    public int $min_dpi = 300;

    public float $safe_area_mm = 0;

    public ?int $color_mode_id = null;

    public function mount(int|Product $product): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $product instanceof Product ? $product : Product::findOrFail($product);
        $this->productModel = $model;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->status_id = $model->status_id;
        $this->print_width_mm = (float) $model->print_width_mm;
        $this->print_height_mm = (float) $model->print_height_mm;
        $this->min_dpi = $model->min_dpi;
        $this->safe_area_mm = (float) $model->safe_area_mm;
        $this->color_mode_id = $model->color_mode_id;
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,'.$this->productModel->id],
            'status_id' => ['required', 'exists:product_statuses,id'],
            'print_width_mm' => ['required', 'numeric', 'min:1'],
            'print_height_mm' => ['required', 'numeric', 'min:1'],
            'min_dpi' => ['required', 'integer', 'min:72'],
            'safe_area_mm' => ['required', 'numeric', 'min:0'],
            'color_mode_id' => ['required', 'exists:color_modes,id'],
        ]);

        $tracked = [
            'name', 'slug', 'status_id', 'print_width_mm',
            'print_height_mm', 'min_dpi', 'safe_area_mm', 'color_mode_id',
        ];
        $before = $this->productModel->only($tracked);

        $this->productModel->update($this->only($tracked));

        $after = $this->productModel->fresh()->only($tracked);
        $changes = array_keys(array_diff_assoc($after, $before));

        if ($changes !== []) {
            $audit->record(
                actor: auth()->user(),
                actionSlug: 'edit_product',
                target: $this->productModel,
                payload: [
                    'before' => array_intersect_key($before, array_flip($changes)),
                    'after' => array_intersect_key($after, array_flip($changes)),
                    'changed' => $changes,
                ],
            );
        }

        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.edit', [
            'statuses' => ProductStatus::orderBy('name')->get(),
            'colorModes' => ColorMode::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Edit Product'),
        ]);
    }
}
