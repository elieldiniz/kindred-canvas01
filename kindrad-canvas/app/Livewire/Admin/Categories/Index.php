<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
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
        $category = Category::findOrFail($this->deleteId);
        $snapshot = $category->only(['id', 'product_id', 'name', 'slug']);
        $category->delete();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_category',
            target: $category,
            payload: ['event' => 'deleted', 'snapshot' => $snapshot],
        );

        $this->confirmDelete = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.categories.index', [
            'categories' => Category::with('product', 'status')
                ->withCount('styles')
                ->latest()
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Categories'),
        ]);
    }
}
