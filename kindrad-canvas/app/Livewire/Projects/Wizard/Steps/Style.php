<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\Style as StyleModel;
use Illuminate\Support\Collection;
use Livewire\Component;

class Style extends Component
{
    public ?int $projectId = null;

    public ?int $categoryId = null;

    public ?int $styleId = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'styleId' => ['nullable', 'integer', 'exists:styles,id'],
        ];
    }

    public function mount(?int $projectId = null, ?int $categoryId = null, ?int $styleId = null): void
    {
        $this->projectId = $projectId;
        $this->categoryId = $categoryId;
        $this->styleId = $styleId;
    }

    /**
     * @return Collection<int, StyleModel>
     */
    public function styles(): Collection
    {
        if ($this->categoryId === null) {
            return collect();
        }

        return StyleModel::query()
            ->with('status:id,slug')
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId))
            ->orderBy('name')
            ->get();
    }

    public function selectStyle(int $styleId): void
    {
        if ($this->categoryId === null) {
            $this->addError('styleId', __('Choose a category before picking a style.'));

            return;
        }

        $exists = StyleModel::whereKey($styleId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $this->categoryId))
            ->exists();

        if (! $exists) {
            $this->addError('styleId', __('Style is not associated with this category.'));

            return;
        }

        $this->dispatch('style-selected', styleId: $styleId);
    }

    public function goToCategories(): void
    {
        $this->dispatch('go-to-step', step: 2);
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.style');
    }
}
