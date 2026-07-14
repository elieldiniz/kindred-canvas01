<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Product;
use Livewire\Component;

class BlockProduct extends Component
{
    public ?int $productId = null;

    public function mount(?int $productId = null): void
    {
        $this->productId = $productId;
    }

    public function render()
    {
        $products = Product::query()
            ->with('status:id,slug')
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->orderBy('name')
            ->get();

        return view('livewire.projects.configurator.block-product', [
            'products' => $products,
        ]);
    }
}
