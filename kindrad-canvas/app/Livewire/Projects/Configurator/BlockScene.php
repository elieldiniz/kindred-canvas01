<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\ScenePreset;
use Livewire\Attributes\On;
use Livewire\Component;

class BlockScene extends Component
{
    public ?int $categoryId = null;

    public ?int $scenePresetId = null;

    public function mount(?int $categoryId = null, ?int $scenePresetId = null): void
    {
        $this->categoryId = $categoryId;
        $this->scenePresetId = $scenePresetId;
    }

    #[On('category-selected')]
    public function categorySelected(int $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->scenePresetId = ScenePreset::query()
            ->where('category_id', $categoryId)
            ->where('is_default', true)
            ->value('id');
    }

    public function render()
    {
        $presets = collect();

        if ($this->categoryId !== null) {
            $presets = ScenePreset::query()
                ->where('category_id', $this->categoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return view('livewire.projects.configurator.block-scene', [
            'presets' => $presets,
        ]);
    }
}
