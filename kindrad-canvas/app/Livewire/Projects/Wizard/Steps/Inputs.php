<?php

namespace App\Livewire\Projects\Wizard\Steps;

use Livewire\Component;

class Inputs extends Component
{
    public int $projectId;

    public string $name = '';

    public string $phrase = '';

    public string $theme = '';

    public string $dedicatoria = '';

    public function mount(int $projectId, array $inputs = []): void
    {
        $this->projectId = $projectId;
        $this->name = (string) ($inputs['name'] ?? '');
        $this->phrase = (string) ($inputs['phrase'] ?? '');
        $this->theme = (string) ($inputs['theme'] ?? '');
        $this->dedicatoria = (string) ($inputs['dedicatoria'] ?? '');
    }

    public function updated($property, $value): void
    {
        if (! in_array($property, ['name', 'phrase', 'theme', 'dedicatoria'], true)) {
            return;
        }

        $this->dispatch('inputs-updated', key: $property, value: (string) $value);
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.inputs');
    }
}
