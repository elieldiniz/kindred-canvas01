<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\Layout as LayoutModel;
use Illuminate\Support\Collection;
use Livewire\Component;

class Layout extends Component
{
    public ?int $projectId = null;

    public ?int $styleId = null;

    public ?int $layoutId = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'layoutId' => ['nullable', 'integer', 'exists:layouts,id'],
        ];
    }

    public function mount(?int $projectId = null, ?int $styleId = null, ?int $layoutId = null): void
    {
        $this->projectId = $projectId;
        $this->styleId = $styleId;
        $this->layoutId = $layoutId;
    }

    /**
     * @return Collection<int, LayoutModel>
     */
    public function layouts(): Collection
    {
        if ($this->styleId === null) {
            return collect();
        }

        return LayoutModel::query()
            ->with('status:id,slug')
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->whereHas('styles', fn ($q) => $q->where('styles.id', $this->styleId))
            ->orderBy('name')
            ->get();
    }

    public function selectLayout(int $layoutId): void
    {
        if ($this->styleId === null) {
            $this->addError('layoutId', __('Choose a style before picking a layout.'));

            return;
        }

        $exists = LayoutModel::whereKey($layoutId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->whereHas('styles', fn ($q) => $q->where('styles.id', $this->styleId))
            ->exists();

        if (! $exists) {
            $this->addError('layoutId', __('Layout is not associated with this style.'));

            return;
        }

        $this->dispatch('layout-selected', layoutId: $layoutId);
    }

    public function goToStyles(): void
    {
        $this->dispatch('go-to-step', step: 3);
    }

    /**
     * @param  array<string, int>|null  $safeArea
     * @return array{top: int, right: int, bottom: int, left: int}
     */
    public function safeAreaPadding(?array $safeArea): array
    {
        return [
            'top' => (int) ($safeArea['top_mm'] ?? 5),
            'right' => (int) ($safeArea['right_mm'] ?? 5),
            'bottom' => (int) ($safeArea['bottom_mm'] ?? 5),
            'left' => (int) ($safeArea['left_mm'] ?? 5),
        ];
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.layout');
    }
}
