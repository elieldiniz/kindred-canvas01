<div class="relative min-h-full overflow-hidden px-6 py-8 lg:px-10">
    @php
        $latestStatus = $latestGeneration?->status?->slug;
        $previewStatus = $currentPreview?->status?->slug;
        $statusClasses = [
            'waiting' => 'bg-neutral-500/15 text-neutral-400',
            'processing' => 'bg-indigo-400/15 text-indigo-300',
            'completed' => 'bg-emerald-400/15 text-emerald-300',
            'failed' => 'bg-red-400/15 text-red-300',
        ];
    @endphp

    <div class="pointer-events-none absolute -right-40 -top-40 h-[500px] w-[500px] rounded-full bg-indigo-400/5 blur-[120px]"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-40 h-[400px] w-[400px] rounded-full bg-fuchsia-400/10 blur-[100px]"></div>

    <div class="relative mx-auto flex max-w-7xl flex-col gap-8">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold text-neutral-900 dark:text-white">
                        {{ $project->title ?: __('Untitled project') }}
                    </h1>
                    @if ($latestGeneration)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$latestStatus] ?? $statusClasses['waiting'] }}">
                            {{ $this->statusLabel($latestGeneration) }}
                        </span>
                    @endif
                </div>
                <p class="font-mono text-xs text-neutral-500 dark:text-neutral-400">
                    @if ($project->first_generated_at)
                        {{ __('First generated :date', ['date' => $project->first_generated_at->format('M j, Y · H:i')]) }}
                    @else
                        {{ __('Created :date', ['date' => $project->created_at->format('M j, Y · H:i')]) }}
                    @endif
                </p>
            </div>
            <flux:button :href="route('projects.new', ['id' => $project->id])" variant="ghost" icon="pencil-square" wire:navigate>
                {{ __('Edit project') }}
            </flux:button>
        </header>

        <div class="grid grid-cols-3 gap-3 sm:max-w-lg">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-xl">
                <p class="text-2xl font-semibold">{{ $this->totalCount() }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Total') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-xl">
                <p class="text-2xl font-semibold text-emerald-400">{{ $this->completedCount() }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Completed') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-xl">
                <p class="text-2xl font-semibold text-red-400">{{ $this->failedCount() }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Failed') }}</p>
            </div>
        </div>

        @if (in_array($latestStatus, ['waiting', 'processing'], true) && $selectedGenerationId === null)
            <section wire:poll.{{ $refreshIntervalMs }}ms="poll" class="relative flex min-h-[560px] items-center justify-center overflow-hidden rounded-3xl border border-white/10 bg-neutral-950/70 p-8 text-center shadow-2xl">
                <div class="flex max-w-xl flex-col items-center gap-6">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-indigo-300/20 p-2">
                        <div class="h-full w-full animate-spin rounded-full border-2 border-transparent border-t-indigo-300"></div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <h2 class="text-4xl font-semibold text-white">{{ __('AI Generating...') }}</h2>
                        <p class="text-lg text-neutral-400">{{ __('We are weaving your concepts into a high-fidelity masterpiece.') }}</p>
                        <p class="font-mono text-xs text-indigo-300">{{ __('Progress is being monitored automatically every 2 seconds.') }}</p>
                    </div>
                </div>
            </section>
        @elseif ($currentPreview && $previewStatus === 'completed')
            <section class="grid min-h-[600px] overflow-hidden rounded-3xl border border-white/10 bg-neutral-950/70 shadow-2xl lg:grid-cols-[minmax(0,7fr)_minmax(300px,3fr)]">
                <div class="flex min-h-[480px] items-center justify-center bg-black/20 p-6 lg:p-10">
                    <img class="max-h-[72vh] max-w-full rounded-2xl border border-white/10 object-contain shadow-2xl" src="{{ $this->previewUrl() }}" alt="{{ $project->title ?: __('Generated artwork') }}">
                </div>

                <aside class="flex flex-col gap-6 border-t border-white/10 p-6 lg:border-l lg:border-t-0 lg:p-8">
                    <div class="flex flex-col gap-3">
                        <h2 class="text-xl font-semibold text-white">{{ __('Result Details') }}</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ([$project->category?->name, $project->style?->name, $project->layout?->name] as $label)
                                @if ($label)
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-indigo-300">{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">{{ __('Prompt Used') }}</p>
                        <p class="mt-2 text-sm italic text-neutral-300">“{{ $currentPreview->prompt_snapshot }}”</p>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 font-mono text-xs text-neutral-400">
                        <div><dt>{{ __('Filename') }}</dt><dd class="mt-1 break-all text-neutral-200">{{ basename($currentPreview->result_path) }}</dd></div>
                        <div><dt>{{ __('Dimensions') }}</dt><dd class="mt-1 text-neutral-200">{{ $currentPreview->result_width_px }} × {{ $currentPreview->result_height_px }}</dd></div>
                        <div><dt>{{ __('Credits') }}</dt><dd class="mt-1 text-neutral-200">{{ $currentPreview->credits_charged }}</dd></div>
                        <div><dt>{{ __('Completed') }}</dt><dd class="mt-1 text-neutral-200">{{ $currentPreview->completed_at?->format('M j · H:i') }}</dd></div>
                    </dl>

                    <div class="mt-auto flex flex-col gap-3">
                        <flux:button variant="primary" icon="arrow-down-tray" wire:click="download({{ $currentPreview->id }})">
                            {{ __('Download') }}
                        </flux:button>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:button variant="ghost" wire:click="regenerate">{{ __('Regenerate') }}</flux:button>
                            <flux:button variant="ghost" disabled>{{ __('Edit Art') }}</flux:button>
                        </div>
                        <flux:button variant="danger" wire:click="openDeleteConfirmation">{{ __('Delete') }}</flux:button>
                    </div>
                </aside>
            </section>
        @elseif ($currentPreview && $previewStatus === 'failed')
            <section class="flex min-h-[440px] items-center justify-center rounded-3xl border border-red-400/20 bg-red-950/10 p-8 text-center">
                <div class="flex max-w-xl flex-col items-center gap-5">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-red-400/15 text-2xl text-red-300">!</span>
                    <h2 class="text-3xl font-semibold">{{ __('Generation failed') }}</h2>
                    <p class="text-neutral-500 dark:text-neutral-400">{{ $currentPreview->failure_reason ?: __('The artwork could not be generated.') }}</p>
                    <flux:button variant="primary" wire:click="retry({{ $currentPreview->id }})">{{ __('Retry') }}</flux:button>
                </div>
            </section>
        @else
            <section class="flex min-h-[360px] items-center justify-center rounded-3xl border border-white/10 bg-white/5 p-8 text-center">
                <div class="flex max-w-md flex-col items-center gap-4">
                    <h2 class="text-2xl font-semibold">{{ __('No completed artwork yet') }}</h2>
                    <p class="text-neutral-500 dark:text-neutral-400">{{ __('Your first artwork will appear here when generation finishes.') }}</p>
                </div>
            </section>
        @endif

        <section class="flex flex-col gap-4">
            <div>
                <h2 class="text-2xl font-semibold">{{ __('Your History') }}</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('The 50 most recent generation attempts, newest first.') }}</p>
            </div>

            <div class="max-h-[32rem] space-y-3 overflow-y-auto pr-1">
                @forelse ($generations as $generation)
                    @php($generationStatus = $generation->status->slug)
                    <button
                        type="button"
                        wire:key="generation-{{ $generation->id }}"
                        wire:click="selectGeneration({{ $generation->id }})"
                        class="flex w-full items-center justify-between gap-4 rounded-2xl border p-4 text-left transition {{ $selectedGenerationId === $generation->id ? 'border-indigo-300/60 bg-indigo-300/10' : 'border-white/10 bg-white/5 hover:border-indigo-300/30' }}"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ __('Generation #:id', ['id' => $generation->id]) }}</p>
                            <p class="mt-1 font-mono text-xs text-neutral-500 dark:text-neutral-400">{{ $generation->created_at?->format('M j, Y · H:i:s') }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="font-mono text-xs text-neutral-500 dark:text-neutral-400">{{ trans_choice(':count credit|:count credits', $generation->credits_charged, ['count' => $generation->credits_charged]) }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$generationStatus] ?? $statusClasses['waiting'] }}">
                                {{ $this->statusLabel($generation) }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center text-neutral-500 dark:text-neutral-400">
                        {{ __('No generation history yet.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>

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
