<div class="flex flex-col gap-section" data-test="admin-gallery-index">

    <header class="flex flex-col gap-stack-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('Gallery moderation') }}
            </h1>
            <p class="mt-stack-sm text-body-sm text-on-surface-variant">
                {{ __('Toggle is_published to hide a project from the public /explore feed. The author keeps it in their own project page.') }}
            </p>
        </div>

        <nav class="inline-flex items-center rounded-full border border-white/10 bg-white/5 p-1" data-test="admin-gallery-filter">
            @foreach (['all', 'published', 'unpublished'] as $key)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $key }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest transition-all',
                        'bg-primary text-white shadow-[0_0_10px_rgba(99,54,255,0.4)]' => $filter === $key,
                        'text-white/60 hover:text-white' => $filter !== $key,
                    ])
                    data-test="admin-gallery-filter-{{ $key }}"
                >
                    {{ ucfirst($key) }}
                    <span class="ml-1 opacity-60">{{ $counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </nav>
    </header>

    @if ($projects->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-gallery-empty">
            <span class="material-symbols-outlined text-[48px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
            <p class="mt-stack-sm text-base text-on-surface-variant">{{ __('No completed artwork yet.') }}</p>
        </div>
    @else
        <section class="grid gap-stack-md sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-test="admin-gallery-grid">
            @foreach ($projects as $project)
                @php $preview = $project->previewImageUrl(); @endphp
                <article
                    class="glass-card relative overflow-hidden"
                    data-test="admin-gallery-card"
                    wire:key="admin-gallery-{{ $project->id }}"
                >
                    <a href="{{ route('projects.show', $project) }}" target="_blank" rel="noopener" class="block">
                        <div class="relative aspect-square overflow-hidden bg-surface-container-high">
                            @if ($preview)
                                <img src="{{ $preview }}" alt="{{ $project->title ?: __('Untitled') }}" class="h-full w-full object-cover" />
                                @if (! $project->is_published)
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm" data-test="admin-gallery-hidden-overlay">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold uppercase tracking-widest text-rose-300">
                                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">visibility_off</span>
                                            {{ __('Hidden from /explore') }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </a>

                    <div class="p-stack-sm space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-purple-600 text-[9px] font-bold text-white">
                                {{ $project->user?->initials() ?? '?' }}
                            </div>
                            <span class="font-semibold text-on-surface truncate">{{ $project->user?->name ?? '—' }}</span>
                        </div>

                        <button
                            type="button"
                            wire:click="togglePublished({{ $project->id }})"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition-all
                                {{ $project->is_published ? 'border border-emerald-500/40 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' : 'border border-rose-500/40 bg-rose-500/10 text-rose-300 hover:bg-rose-500/20' }}"
                            data-test="admin-gallery-toggle"
                        >
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">
                                {{ $project->is_published ? 'visibility' : 'visibility_off' }}
                            </span>
                            {{ $project->is_published ? __('Published on /explore') : __('Hidden from /explore') }}
                        </button>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="mt-stack-md" data-test="admin-gallery-pagination">
            {{ $projects->links() }}
        </div>
    @endif
</div>
