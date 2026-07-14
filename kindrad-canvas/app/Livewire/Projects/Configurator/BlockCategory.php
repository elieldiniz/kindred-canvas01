<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class BlockCategory extends Component
{
    public ?int $productId = null;

    public ?int $categoryId = null;

    public function mount(?int $productId = null, ?int $categoryId = null): void
    {
        $this->productId = $productId;
        $this->categoryId = $categoryId;
    }

    public function render()
    {
        $categories = collect();

        if ($this->productId !== null) {
            $product = Product::find($this->productId);
            $categories = Category::query()
                ->with('product:id,slug')
                ->where('product_id', $this->productId)
                ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return view('livewire.projects.configurator.block-category', [
            'categories' => $categories,
        ]);
    }
}
