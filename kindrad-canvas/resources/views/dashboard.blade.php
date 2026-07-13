<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('Your projects') }}</h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Start a new personalized project or pick up where you left off.') }}
                </p>
            </div>
            <flux:button :href="route('projects.new')" variant="primary" icon="plus" wire:navigate>
                {{ __('New Project') }}
            </flux:button>
        </div>

        @php
            $recentProjects = App\Models\Project::query()
                ->where('user_id', auth()->id())
                ->whereNull('deleted_at')
                ->latest()
                ->limit(3)
                ->get();
        @endphp

        @if ($recentProjects->isNotEmpty())
            <section class="flex flex-col gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Recent Projects') }}</h2>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Continue your latest work.') }}</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($recentProjects as $project)
                        <a
                            href="{{ route('projects.show', $project) }}"
                            wire:navigate
                            class="rounded-2xl border border-neutral-200 bg-white/60 p-5 transition hover:border-indigo-400/50 dark:border-white/10 dark:bg-white/5"
                        >
                            <p class="font-semibold">{{ $project->title ?: __('Untitled project') }}</p>
                            <p class="mt-2 font-mono text-xs text-neutral-500 dark:text-neutral-400">{{ $project->updated_at->format('M j, Y · H:i') }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
