<?php

namespace App\Livewire\Admin\Gallery;

use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function togglePublished(int $projectId): void
    {
        $project = Project::query()->findOrFail($projectId);

        $wasPublished = (bool) $project->is_published;
        $project->is_published = ! $wasPublished;
        $project->save();

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: $wasPublished ? 'unpublish_project' : 'publish_project',
            target: $project,
            payload: ['before' => $wasPublished, 'after' => $project->is_published],
        );
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Project::query()
            ->whereHas('latestGeneration', fn ($q) => $q->whereNotNull('completed_at'))
            ->with(['user', 'latestGeneration'])
            ->latest('first_generated_at');

        if ($this->filter === 'published') {
            $query->where('is_published', true);
        } elseif ($this->filter === 'unpublished') {
            $query->where('is_published', false);
        }

        return view('livewire.admin.gallery.index', [
            'projects' => $query->paginate(30),
            'counts' => [
                'all' => Project::query()->whereHas('latestGeneration', fn ($q) => $q->whereNotNull('completed_at'))->count(),
                'published' => Project::query()->where('is_published', true)->whereHas('latestGeneration', fn ($q) => $q->whereNotNull('completed_at'))->count(),
                'unpublished' => Project::query()->where('is_published', false)->whereHas('latestGeneration', fn ($q) => $q->whereNotNull('completed_at'))->count(),
            ],
        ])->layout('components.layouts.admin', [
            'header' => __('Gallery moderation'),
        ]);
    }
}
