<?php

namespace App\Livewire\Projects;

use App\Actions\Generation\SubmitGeneration;
use App\Models\Generation;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Show extends Component
{
    public Project $project;

    public ?int $selectedGenerationId = null;

    public int $refreshIntervalMs = 2000;

    public bool $confirmDelete = false;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function latestGeneration(): ?Generation
    {
        return $this->project->generations()->with('status')->latest('id')->first();
    }

    public function latestCompleted(): ?Generation
    {
        return $this->project->generations()
            ->with('status')
            ->whereHas('status', fn ($query) => $query->where('slug', 'completed'))
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, Generation>
     */
    public function generations(): Collection
    {
        return $this->project->generations()
            ->with('status')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function completedCount(): int
    {
        return $this->project->generations()->completed()->count();
    }

    public function failedCount(): int
    {
        return $this->project->generations()->failed()->count();
    }

    public function totalCount(): int
    {
        return $this->project->generations()->count();
    }

    public function statusLabel(Generation $generation): string
    {
        return ucfirst($generation->status->slug);
    }

    public function selectGeneration(int $generationId): void
    {
        $this->authorize('view', $this->project);

        abort_unless($this->project->generations()->whereKey($generationId)->exists(), 404);

        $this->selectedGenerationId = $generationId;
    }

    public function currentPreview(): ?Generation
    {
        if ($this->selectedGenerationId === null) {
            return $this->latestCompleted();
        }

        return $this->project->generations()
            ->with('status')
            ->whereKey($this->selectedGenerationId)
            ->first();
    }

    public function previewUrl(): ?string
    {
        $preview = $this->currentPreview();

        if ($preview?->status?->slug !== 'completed' || $preview->result_path === null) {
            return null;
        }

        return Storage::disk(config('generation.disk'))->url($preview->result_path);
    }

    public function poll(): void
    {
        $this->authorize('view', $this->project);
        $this->project->refresh();
    }

    public function download(int $generationId): RedirectResponse|Redirector
    {
        $generation = $this->project->generations()->findOrFail($generationId);
        $this->authorize('download', $generation);

        return redirect()->route('generations.download', $generation);
    }

    public function regenerate(): void
    {
        $this->authorize('update', $this->project);

        $user = auth()->user();
        abort_if($user === null, 401);

        app(SubmitGeneration::class)->execute($user, $this->project);
        $this->selectedGenerationId = null;
    }

    #[Computed]
    public function canRegenerate(): bool
    {
        return (int) auth()->user()?->credit_balance > 0;
    }

    public function retry(int $generationId): void
    {
        $this->authorize('update', $this->project);

        $generation = $this->project->generations()->findOrFail($generationId);
        $this->authorize('view', $generation);
        abort_unless($generation->status()->where('slug', 'failed')->exists(), 404);

        $this->regenerate();
    }

    public function openDeleteConfirmation(): void
    {
        $this->authorize('delete', $this->project);
        $this->confirmDelete = true;
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('delete', $this->project);
        $this->project->delete();

        session()->flash('status', 'Project scheduled for deletion in 30 days.');

        return redirect()->route('dashboard');
    }

    public function render()
    {
        $latestGeneration = $this->latestGeneration();
        $currentPreview = $this->currentPreview();

        return view('livewire.projects.show', [
            'latestGeneration' => $latestGeneration,
            'currentPreview' => $currentPreview,
            'generations' => $this->generations(),
        ])->layout('layouts::app', [
            'title' => $this->project->title ?: __('Untitled project'),
        ]);
    }
}
