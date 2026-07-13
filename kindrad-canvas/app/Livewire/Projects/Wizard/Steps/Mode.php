<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\ProjectMode;
use Illuminate\Support\Collection;
use Livewire\Component;

class Mode extends Component
{
    public int $projectId;

    public ?int $modeId = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'modeId' => ['nullable', 'integer', 'exists:project_modes,id'],
        ];
    }

    public function mount(int $projectId, ?int $modeId = null): void
    {
        $this->projectId = $projectId;
        $this->modeId = $modeId;
    }

    /**
     * @return Collection<int, array{id: int, name: string, icon: string}>
     */
    public function modes(): Collection
    {
        return ProjectMode::whereIn('slug', ['free', 'mug'])
            ->orderBy('name')
            ->get()
            ->map(fn (ProjectMode $m): array => [
                'id' => $m->id,
                'name' => $m->name,
                'icon' => $m->slug === 'mug' ? 'coffee' : 'auto_awesome',
            ]);
    }

    public function selectMode(int $modeId): void
    {
        $mode = ProjectMode::whereIn('slug', ['free', 'mug'])->find($modeId);

        if ($mode === null) {
            $this->addError('modeId', __('Please select a mode.'));

            return;
        }

        $this->dispatch('mode-selected', modeId: $mode->id);
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.mode');
    }
}
