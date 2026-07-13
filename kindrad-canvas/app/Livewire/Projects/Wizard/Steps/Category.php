<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\Category as CategoryModel;
use Illuminate\Support\Collection;
use Livewire\Component;

class Category extends Component
{
    public ?int $projectId = null;

    public ?int $categoryId = null;

    public function mount(?int $projectId = null, ?int $categoryId = null): void
    {
        $this->projectId = $projectId;
        $this->categoryId = $categoryId;
    }

    /**
     * @return Collection<int, CategoryModel>
     */
    public function categories(): Collection
    {
        return CategoryModel::query()
            ->with(['status:id,slug', 'product:id,slug'])
            ->whereHas('product', fn ($q) => $q->where('slug', 'mug'))
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(int $categoryId): void
    {
        $exists = CategoryModel::whereKey($categoryId)
            ->whereHas('product', fn ($q) => $q->where('slug', 'mug'))
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->exists();

        if (! $exists) {
            $this->addError('categoryId', __('Invalid category.'));

            return;
        }

        $this->dispatch('category-selected', categoryId: $categoryId);
    }

    /**
     * @return array<string, string>
     */
    public function iconFor(string $slug): string
    {
        return match ($slug) {
            'birthday' => 'cake',
            'wedding' => 'favorite',
            'pets' => 'pets',
            'family' => 'family_restroom',
            'couples' => 'people',
            'kids' => 'child_care',
            default => 'style',
        };
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.category');
    }
}
