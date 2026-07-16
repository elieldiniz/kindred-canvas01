<div class="flex flex-col gap-section p-margin-page" data-test="gallery-explore">

    <header class="py-8 pb-2 text-center" data-test="gallery-explore-header">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 text-xs font-bold tracking-widest uppercase text-primary">
            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">photo</span>
            {{ __('Public Gallery') }}
        </span>
        <h1 class="mt-4 font-display-lg text-display-lg font-extrabold">
            <span class="bg-gradient-to-r from-white via-white to-white/60 bg-clip-text text-transparent">{{ __('Discover') }}</span>
            <span class="bg-gradient-to-r from-primary via-purple-400 to-cyan-400 bg-clip-text text-transparent">{{ __('community art') }}</span>
        </h1>
        <p class="mt-3 max-w-xl mx-auto text-base text-white/50 leading-relaxed">
            {{ __('A rotating showcase of artworks created with free signup credits. Hit Remix to put your own spin on it.') }}
        </p>
    </header>

    @if ($this->projects->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="gallery-explore-empty">
            <span class="material-symbols-outlined text-[48px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
            <p class="mt-stack-sm text-base text-on-surface-variant">
                {{ __('No community artwork yet. Check back soon!') }}
            </p>
        </div>
    @else
        <section
            class="grid gap-stack-md sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            data-test="gallery-explore-grid"
        >
            @foreach ($this->projects as $project)
                @php
                    $previewUrl = $project->previewImageUrl();
                    $authorInitials = $project->user?->initials() ?? '?';
                    $isFavorited = isset($this->favoritedProjectIds[$project->id]);
                @endphp
                <article
                    class="glass-card group relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:border-primary"
                    data-test="gallery-explore-card"
                    data-project-id="{{ $project->id }}"
                    wire:key="gallery-card-{{ $project->id }}"
                >
                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="block">
                        <div class="relative aspect-square overflow-hidden bg-surface-container-high">
                            @if ($previewUrl)
                                <img src="{{ $previewUrl }}" alt="{{ $project->title ?: __('Untitled project') }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                <div class="absolute inset-0 bg-gradient-to-t from-background/85 via-background/20 to-transparent"></div>
                            @else
                                <span class="absolute inset-0 flex items-center justify-center text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[40px]" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
                                </span>
                            @endif

                            @if ($project->remixed_from_project_id)
                                <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-primary/80 backdrop-blur-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-white shadow-[0_0_10px_rgba(99,54,255,0.4)]" data-test="gallery-explore-remixed-badge">
                                    <span class="material-symbols-outlined text-[10px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">fork_right</span>
                                    {{ __('Remix') }}
                                </span>
                            @endif
                        </div>
                    </a>

                    <div class="flex flex-col gap-stack-sm p-stack-sm">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-purple-600 text-[10px] font-bold text-white">
                                {{ $authorInitials }}
                            </div>
                            <a
                                href="{{ route('projects.show', $project) }}"
                                wire:navigate
                                class="text-sm font-semibold text-on-surface hover:text-primary truncate"
                                data-test="gallery-explore-card-author"
                            >
                                {{ $project->user?->name ?? __('Unknown') }}
                            </a>
                        </div>
                        <p class="font-mono-xs text-mono-xs uppercase tracking-widest text-primary" data-test="gallery-explore-card-meta">
                            {{ $project->title ?: __('Untitled project') }}
                        </p>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <button
                                type="button"
                                wire:click="toggleFavorite({{ $project->id }})"
                                class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-semibold text-white/70 hover:bg-white/10"
                                data-test="gallery-explore-favorite"
                            >
                                <span
                                    class="material-symbols-outlined text-[14px]"
                                    style="font-variation-settings: 'FILL' {{ $isFavorited ? 1 : 0 }}, 'wght' 400; color: {{ $isFavorited ? '#ff4d6d' : 'currentColor' }}"
                                    aria-hidden="true"
                                >favorite</span>
                                {{ $project->favorites_count }}
                            </button>

                            <form action="{{ route('gallery.remix', $project) }}" method="POST" wire:navigate>
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/20 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary/30"
                                    data-test="gallery-explore-remix-btn"
                                >
                                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">fork_right</span>
                                    {{ __('Remix') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="mt-stack-md" data-test="gallery-explore-pagination">
            {{ $this->projects->links() }}
        </div>
    @endif
</div>
