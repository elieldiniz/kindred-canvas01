<?php

namespace App\Livewire\Projects\Configurator;

use Livewire\Component;

class BlockSubjectType extends Component
{
    public ?string $subjectType = null;

    public function mount(?string $subjectType = null): void
    {
        $this->subjectType = $subjectType;
    }

    public function render()
    {
        $types = [
            ['value' => 'pessoa', 'name' => 'Person', 'icon' => 'person'],
            ['value' => 'casal', 'name' => 'Couple', 'icon' => 'people'],
            ['value' => 'familia', 'name' => 'Family', 'icon' => 'family_restroom'],
            ['value' => 'pet', 'name' => 'Pet', 'icon' => 'pets'],
            ['value' => 'outra', 'name' => 'Other', 'icon' => 'category'],
        ];

        return view('livewire.projects.configurator.block-subject-type', [
            'types' => $types,
        ]);
    }
}
