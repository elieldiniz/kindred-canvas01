<?php

namespace App\Livewire\Projects\Configurator;

use App\Models\Pose;
use Livewire\Component;

class BlockPose extends Component
{
    public ?int $poseId = null;

    public function mount(?int $poseId = null): void
    {
        $this->poseId = $poseId;
    }

    public function render()
    {
        $poses = Pose::query()
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.projects.configurator.block-pose', [
            'poses' => $poses,
        ]);
    }
}
