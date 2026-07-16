<?php

namespace App\Livewire\Gallery;

use App\Models\GalleryFavorite;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Explore extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()?->id !== null, 401);
    }

    /**
     * @return LengthAwarePaginator<Project>
     */
    #[Computed]
    public function projects(): LengthAwarePaginator
    {
        return Project::query()
            ->where('is_published', true)
            ->where('is_in_explore', true)
            ->whereHas('latestGeneration', fn ($q) => $q->whereNotNull('completed_at'))
            ->whereHas('user', fn ($q) => $q
                ->whereDoesntHave('subscriptions', fn ($sub) => $sub->whereIn('stripe_status', ['active', 'trialing']))
            )
            ->with(['user', 'latestGeneration', 'remixedFrom'])
            ->withCount('favorites')
            ->latest('first_generated_at')
            ->paginate(24);
    }

    /**
     * Toggle a favorite for the current user on the given project. Idempotent.
     */
    public function toggleFavorite(int $projectId): void
    {
        $deleted = GalleryFavorite::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $projectId)
            ->delete();

        if ($deleted === 0) {
            GalleryFavorite::create([
                'user_id' => auth()->id(),
                'project_id' => $projectId,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function favoritedProjectIds(): array
    {
        return GalleryFavorite::query()
            ->where('user_id', auth()->id())
            ->pluck('project_id')
            ->mapWithKeys(fn ($id) => [$id => true])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.gallery.explore');
    }
}
