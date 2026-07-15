<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Services\AuditLogger;
use Livewire\Component;

class Index extends Component
{
    public bool $confirmDelete = false;

    public ?int $deleteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmDelete = true;
    }

    public function delete(AuditLogger $audit): void
    {
        $product = Product::findOrFail($this->deleteId);

        if ($product->categories()->exists()) {
            $this->addError('deleteId', 'Cannot delete a product that has categories.');

            return;
        }

        $snapshot = $product->only(['id', 'name', 'slug']);
        $product->delete();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_product',
            target: $product,
            payload: ['event' => 'deleted', 'snapshot' => $snapshot],
        );

        $this->confirmDelete = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.products.index', [
            'products' => Product::with('status', 'colorMode')
                ->withCount('categories')
                ->latest()
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Products'),
        ]);
    }
}
