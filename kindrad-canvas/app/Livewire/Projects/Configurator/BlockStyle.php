<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Category;
use App\Models\Style;
use Livewire\Component;

class BlockStyle extends Component
{
    public ?int $categoryId = null;

    public ?int $styleId = null;

    public function mount(?int $categoryId = null, ?int $styleId = null): void
    {
        $this->categoryId = $categoryId;
        $this->styleId = $styleId;
    }

    public function render()
    {
        $styles = collect();

        if ($this->categoryId !== null) {
            $category = Category::find($this->categoryId);
            $styles = Style::query()
                ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
                ->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId))
                ->orderBy('name')
                ->get();
        }

        return view('livewire.projects.configurator.block-style', [
            'styles' => $styles,
        ]);
    }
}
