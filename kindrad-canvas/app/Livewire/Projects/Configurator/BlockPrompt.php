<?php

namespace App\Livewire\Projects\Configurator;

use Livewire\Component;

class BlockPrompt extends Component
{
    public string $customPrompt = '';

    public function mount(string $customPrompt = ''): void
    {
        $this->customPrompt = $customPrompt;
    }

    public function updatedCustomPrompt(string $value): void
    {
        if (mb_strlen($value) > 500) {
            $this->customPrompt = mb_substr($value, 0, 500);

            return;
        }

        $this->dispatch('custom-prompt-updated', value: $value);
    }

    public function render()
    {
        return view('livewire.projects.configurator.block-prompt');
    }
}
