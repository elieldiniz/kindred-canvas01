<div class="flex flex-col gap-section p-margin-page" data-test="project-show-page">
    @php
        $latestStatus = $latestGeneration?->status?->slug;
        $previewStatus = $currentPreview?->status?->slug;
        $statusClasses = [
            'waiting' => 'bg-surface-container-high text-on-surface-variant',
            'processing' => 'bg-primary/15 text-primary',
            'completed' => 'bg-success/15 text-success',
            'failed' => 'bg-error/15 text-error',
        ];
        $total = $this->totalCount();
    @endphp

    <header class="flex flex-col gap-stack-sm sm:flex-row sm:items-end sm:justify-between" data-test="project-header">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-headline-lg text-headline-lg text-on-surface" data-test="project-title">
                    {{ $project->title ?: __('Untitled project') }}
                </h1>
                @if ($latestGeneration)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 font-mono-xs text-mono-xs {{ $statusClasses[$latestStatus] ?? $statusClasses['waiting'] }}">
                        {{ $this->statusLabel($latestGeneration) }}
                    </span>
                @endif
            </div>
            <p class="font-mono-xs text-mono-xs text-on-surface-variant">
                @if ($project->first_generated_at)
                    {{ __('First generated :date', ['date' => $project->first_generated_at->format('M j, Y · H:i')]) }}
                @else
                    {{ __('Created :date', ['date' => $project->created_at->format('M j, Y · H:i')]) }}
                @endif
            </p>
        </div>
        <a
            href="{{ route('projects.new', ['id' => $project->id]) }}"
            wire:navigate
            class="font-mono-xs text-mono-xs text-primary hover:underline"
        >
            {{ __('Edit project') }} →
        </a>
    </header>

    @php
        $showFreeTierNotice = auth()->id() === $project->user_id
            && (auth()->user()?->isFreeTier() ?? false)
            && $project->is_published
            && ! session()->has('gallery_notice_dismissed_project_' . $project->id);
    @endphp

    @if ($showFreeTierNotice)
        <aside
            class="flex items-start gap-stack-md rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs text-white/70"
            data-test="gallery-free-tier-notice"
        >
            <span class="material-symbols-outlined mt-0.5 text-[18px] text-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">photo</span>
            <div class="flex-1 space-y-1">
                <p class="font-semibold text-white">{{ __('This artwork appears in the public gallery') }}</p>
                <p>{{ __('Any logged-in user can discover and remix it from /explore. You can hide it from public view at any time.') }}</p>
                <form action="{{ route('projects.toggle-explore', $project) }}" method="POST" class="mt-2">
                    @csrf
                    <flux:button type="submit" size="xs" variant="ghost" data-test="gallery-hide-btn">
                        <span class="material-symbols-outlined text-[14px]" aria-hidden="true">visibility_off</span>
                        {{ __('Hide this project from the public gallery') }}
                    </flux:button>
                </form>
            </div>
        </aside>
    @endif

    @if ($total > 0)
        <div class="grid gap-stack-sm sm:grid-cols-3" data-test="project-stats">
            <div class="glass-card flex flex-col gap-1 p-stack-md">
                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $total }}</p>
                <p class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Total') }}</p>
            </div>
            <div class="glass-card flex flex-col gap-1 p-stack-md">
                <p class="font-headline-sm text-headline-sm text-success">{{ $this->completedCount() }}</p>
                <p class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Completed') }}</p>
            </div>
            <div class="glass-card flex flex-col gap-1 p-stack-md">
                <p class="font-headline-sm text-headline-sm text-error">{{ $this->failedCount() }}</p>
                <p class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Failed') }}</p>
            </div>
        </div>
    @endif

    @if (in_array($latestStatus, ['waiting', 'processing'], true) && $selectedGenerationId === null)
        <section wire:poll.{{ $refreshIntervalMs }}ms="poll" class="glass-card flex min-h-[400px] items-center justify-center p-8" data-test="project-generating">
            <div class="flex flex-col items-center gap-stack-md text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-primary/20">
                    <div class="h-full w-full animate-spin rounded-full border-2 border-transparent border-t-primary"></div>
                </div>
                <div class="flex flex-col gap-1">
                    <h2 class="font-headline-md text-headline-md text-on-surface">{{ __('AI Generating...') }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('We are weaving your concepts into a high-fidelity masterpiece.') }}</p>
                </div>
            </div>
        </section>
    @elseif (in_array($latestStatus, ['waiting', 'processing'], true))
        <div wire:poll.{{ $refreshIntervalMs }}ms="poll" class="glass-card flex items-center gap-stack-sm border-primary/30 bg-primary/5 p-stack-sm" data-test="project-generating-banner">
            <div class="h-3 w-3 animate-spin rounded-full border-2 border-transparent border-t-primary"></div>
            <p class="font-mono-sm text-mono-sm text-on-surface">
                {{ __('A new generation is in progress. This view will refresh automatically.') }}
            </p>
        </div>
    @endif
    @if ($currentPreview && $previewStatus === 'completed')
        <section class="glass-card flex flex-col overflow-hidden lg:flex-row" data-test="project-preview">
            <div class="flex min-h-[360px] items-center justify-center p-stack-lg lg:flex-1">
                <img
                    class="max-h-[60vh] w-full rounded-xl object-contain"
                    src="{{ $this->previewUrl() }}"
                    alt="{{ $project->title ?: __('Generated artwork') }}"
                />
            </div>

            <aside class="flex flex-col gap-stack-md border-t border-outline-variant p-stack-md lg:w-80 lg:border-l lg:border-t-0">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('Result Details') }}</h2>

                <div class="flex flex-wrap gap-1">
                    @foreach ([$project->category?->name, $project->style?->name, $project->layout?->name] as $label)
                        @if ($label)
                            <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 font-mono-xs text-mono-xs text-primary">{{ $label }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="glass-card p-stack-sm">
                    <p class="font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">{{ __('Prompt Used') }}</p>
                    <p class="mt-1 font-body-xs text-body-xs italic text-on-surface-variant">"{{ $currentPreview->prompt_snapshot }}"</p>
                </div>

                <dl class="grid grid-cols-2 gap-2">
                    <div class="flex flex-col gap-0.5">
                        <dt class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Filename') }}</dt>
                        <dd class="font-body-xs text-body-xs text-on-surface break-all">{{ basename($currentPreview->result_path) }}</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Dimensions') }}</dt>
                        <dd class="font-body-xs text-body-xs text-on-surface">{{ $currentPreview->result_width_px }} × {{ $currentPreview->result_height_px }}</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Credits') }}</dt>
                        <dd class="font-body-xs text-body-xs text-on-surface">{{ $currentPreview->credits_charged }}</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Completed') }}</dt>
                        <dd class="font-body-xs text-body-xs text-on-surface">{{ $currentPreview->completed_at?->format('M j · H:i') }}</dd>
                    </div>
                </dl>

                <div class="mt-auto flex flex-col gap-2">
                    <button
                        type="button"
                        wire:click="download({{ $currentPreview->id }})"
                        class="gradient-generate flex items-center justify-center gap-stack-sm rounded-full py-3 font-label-md text-label-md font-bold text-on-primary"
                    >
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
                        {{ __('Download') }}
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="regenerate"
                            @disabled(! $this->canRegenerate())
                            class="glass-card py-2 font-label-md text-label-md text-on-surface hover:bg-surface-container-high disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                            title="{{ $this->canRegenerate() ? __('Generate a new variation (1 credit)') : __('You are out of credits') }}"
                        >
                            {{ __('Regenerate') }}
                        </button>
                        <button type="button" disabled class="glass-card py-2 font-label-md text-label-md text-on-surface-variant" title="{{ __('Coming soon') }}">
                            {{ __('Edit Art') }}
                        </button>
                    </div>
                    <button
                        type="button"
                        wire:click="openDeleteConfirmation"
                        class="py-2 font-label-md text-label-md text-error hover:bg-error/5"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            </aside>
        </section>
    @elseif ($currentPreview && $previewStatus === 'failed')
        <section class="glass-card flex min-h-[360px] items-center justify-center p-8 text-center" data-test="project-failed">
            <div class="flex flex-col items-center gap-stack-md">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-error/15 text-error">!</span>
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ __('Generation failed') }}</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $currentPreview->failure_reason ?: __('The artwork could not be generated.') }}</p>
                <button
                    type="button"
                    wire:click="retry({{ $currentPreview->id }})"
                    class="gradient-generate inline-flex items-center gap-stack-sm rounded-full px-stack-md py-2 font-label-md text-label-md font-bold text-on-primary"
                >
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">refresh</span>
                    {{ __('Retry') }}
                </button>
            </div>
        </section>
    @else
        <section class="glass-card flex min-h-[300px] items-center justify-center p-8 text-center" data-test="project-empty">
            <div class="flex flex-col items-center gap-stack-md">
                <span class="material-symbols-outlined text-[40px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('No completed artwork yet') }}</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ __('Your first artwork will appear here when generation finishes.') }}</p>
            </div>
        </section>
    @endif

    <section class="flex flex-col gap-stack-md" data-test="project-history">
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('Your History') }}</h2>
            <p class="font-body-xs text-body-xs text-on-surface-variant">{{ __('The 50 most recent generation attempts, newest first.') }}</p>
        </div>

        <div class="flex max-h-[32rem] flex-col gap-2 overflow-y-auto pr-1">
            @forelse ($generations as $generation)
                @php($generationStatus = $generation->status->slug)
                <div
                    wire:key="generation-{{ $generation->id }}"
                    class="glass-card flex w-full items-center justify-between gap-4 p-stack-sm transition-all hover:-translate-y-0.5 hover:border-primary {{ $selectedGenerationId === $generation->id ? 'selection-glow active-selection' : '' }}"
                    data-test="generation-row-{{ $generation->id }}"
                >
                    <button
                        type="button"
                        wire:click="selectGeneration({{ $generation->id }})"
                        class="flex min-w-0 flex-1 items-center gap-4 text-left"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-label-md text-label-md text-on-surface truncate">{{ __('Generation #:id', ['id' => $generation->id]) }}</p>
                            <p class="font-mono-xs text-mono-xs text-on-surface-variant">{{ $generation->created_at?->format('M j, Y · H:i:s') }}</p>
                        </div>
                    </button>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="font-mono-xs text-mono-xs text-on-surface-variant">{{ trans_choice(':count credit|:count credits', $generation->credits_charged, ['count' => $generation->credits_charged]) }}</span>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 font-mono-xs text-mono-xs {{ $statusClasses[$generationStatus] ?? $statusClasses['waiting'] }}">
                            {{ $this->statusLabel($generation) }}
                        </span>
                        @if ($generationStatus === 'failed')
                            <button
                                type="button"
                                wire:click.stop="retry({{ $generation->id }})"
                                class="inline-flex items-center gap-1 rounded-full bg-error/15 px-2 py-0.5 font-mono-xs text-mono-xs text-error transition hover:bg-error/25"
                                data-test="history-retry-button-{{ $generation->id }}"
                                title="{{ __('Retry') }}"
                            >
                                <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">refresh</span>
                                {{ __('Retry') }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="glass-card p-stack-md text-center font-body-sm text-body-sm text-on-surface-variant">
                    {{ __('No generation history yet.') }}
                </div>
            @endforelse
        </div>
    </section>

    <flux:modal wire:model="confirmDelete" class="md:w-96">
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-2">
                <flux:heading size="lg">{{ __('Delete project?') }}</flux:heading>
                <flux:text>{{ __('All generations and downloaded files will be permanently removed in 30 days.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('confirmDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
