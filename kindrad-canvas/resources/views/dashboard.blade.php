<x-layouts::app :title="__('Dashboard')">
    @php
        $recentProjects = App\Models\Project::query()
            ->where('user_id', auth()->id())
            ->whereNull('deleted_at')
            ->with(['category', 'style', 'mode', 'latestGeneration'])
            ->latest()
            ->limit(3)
            ->get();

        $totalGenerations = App\Models\Generation::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', auth()->id()))
            ->count();

        $generationsThisWeek = App\Models\Generation::query()
            ->whereHas('project', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        $creditBalance = (int) auth()->user()->credit_balance;

        $mostUsedStyle = App\Models\Project::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('style_id')
            ->selectRaw('style_id, COUNT(*) as uses')
            ->groupBy('style_id')
            ->orderByDesc('uses')
            ->with('style')
            ->first();
    @endphp

    <div class="flex flex-col gap-section p-margin-page" data-test="dashboard-page">

        @if (session('status'))
            <div class="glass-card border-success/30 bg-success/10 p-stack-md font-label-md text-label-md text-success" data-test="dashboard-session-status">
                {{ session('status') }}
            </div>
        @endif

        <section class="flex flex-col gap-stack-lg sm:flex-row sm:items-start sm:justify-between" data-test="dashboard-hero">
            <div>
                <h1 class="font-display-lg text-display-lg text-on-surface" data-test="dashboard-greeting">
                    {{ __('Welcome back, Curator.') }}
                </h1>
                <p class="mt-stack-sm max-w-xl font-body-md text-body-md text-on-surface-variant">
                    {{ __('Your creative studio is ready. What will you manifest today?') }}
                </p>

                <div class="mt-stack-md">
                    <a
                        href="{{ route('projects.new') }}"
                        wire:navigate
                        class="gradient-generate inline-flex items-center gap-stack-sm rounded-full px-stack-lg py-3 font-label-md text-label-md font-bold text-on-primary shadow-lg shadow-primary/20"
                        data-test="dashboard-new-project-button"
                    >
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add</span>
                        {{ __('New Project') }}
                    </a>
                </div>
            </div>

            <div class="credits-badge w-fit shrink-0" data-test="dashboard-credits-card">
                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">bolt</span>
                <span>{{ $creditBalance }} {{ __('credits') }}</span>
                <a href="{{ route('credits.index') }}" wire:navigate class="ml-1 text-on-primary/70 hover:text-on-primary">
                    →
                </a>
            </div>
        </section>

        <section class="grid gap-stack-sm sm:grid-cols-3" data-test="dashboard-stats-grid">
            <div class="glass-card flex flex-col gap-stack-sm p-stack-md" data-test="dashboard-stat-generations">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings: 'FILL' 0, 'wght' 400;">auto_awesome</span>
                    <span class="font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                        {{ __('Generations') }}
                    </span>
                </div>
                <p class="font-headline-sm text-headline-sm text-on-surface" data-test="dashboard-stat-generations-count">
                    {{ $totalGenerations }}
                </p>
                <p class="font-mono-xs text-mono-xs text-primary">
                    {{ __('+:count this week', ['count' => $generationsThisWeek]) }}
                </p>
            </div>

            <div class="glass-card flex flex-col gap-stack-sm p-stack-md" data-test="dashboard-stat-popular-style">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">trending_up</span>
                    <span class="font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                        {{ __('Top style') }}
                    </span>
                </div>
                <p class="font-headline-sm text-headline-sm text-on-surface" data-test="dashboard-stat-popular-style-name">
                    {{ $mostUsedStyle?->style?->name ?? __('No style yet') }}
                </p>
                <p class="font-mono-xs text-mono-xs text-on-surface-variant">
                    {{ $mostUsedStyle ? trans_choice(':count use|:count uses', $mostUsedStyle->uses, ['count' => $mostUsedStyle->uses]) : __('Run a generation to unlock insights') }}
                </p>
            </div>

            <a
                href="#"
                class="glass-card group flex flex-col gap-stack-sm p-stack-md transition-colors hover:border-primary hover:bg-primary/5"
                data-test="dashboard-stat-upgrade-pack"
            >
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant transition-transform group-hover:scale-110" style="font-variation-settings: 'FILL' 0, 'wght' 400;">rocket_launch</span>
                <p class="font-headline-sm text-headline-sm text-on-surface transition-colors group-hover:text-primary">
                    {{ __('Premium Pack') }}
                </p>
                <p class="font-mono-xs text-mono-xs text-on-surface-variant">
                    {{ __('Unlock 4K export · priority queue') }}
                </p>
            </a>
        </section>

        <section class="flex flex-col gap-stack-md" data-test="dashboard-recent-projects">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">
                        {{ __('Recent Projects') }}
                    </h2>
                    <p class="font-body-xs text-body-xs text-on-surface-variant">
                        {{ __('Continue your latest work or browse everything you have manifested.') }}
                    </p>
                </div>
                @if ($recentProjects->isNotEmpty())
                    <a href="#" wire:navigate class="font-mono-xs text-mono-xs text-primary hover:underline">
                        {{ __('View all projects') }} →
                    </a>
                @endif
            </div>

            @if ($recentProjects->isEmpty())
                <div class="glass-card flex flex-col items-center gap-stack-md p-stack-lg text-center" data-test="dashboard-recent-empty">
                    <span class="material-symbols-outlined text-[40px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">collections</span>
                    <p class="font-headline-sm text-headline-sm text-on-surface">
                        {{ __('No projects yet') }}
                    </p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ __('Your first creation is one click away.') }}
                    </p>
                    <a
                        href="{{ route('projects.new') }}"
                        wire:navigate
                        class="gradient-generate inline-flex items-center gap-stack-sm rounded-full px-stack-md py-2 font-label-md text-label-md font-bold text-on-primary"
                    >
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">add</span>
                        {{ __('Start a project') }}
                    </a>
                </div>
            @else
                <div class="grid gap-stack-sm sm:grid-cols-2 md:grid-cols-3" data-test="dashboard-recent-grid">
                    @foreach ($recentProjects as $project)
                        @php
                            $previewUrl = $project->latestGeneration?->result_path
                                ? Illuminate\Support\Facades\Storage::disk(config('generation.disk'))->url($project->latestGeneration->result_path)
                                : null;
                            $categoryLabel = $project->category?->name ?? __('No category');
                            $styleLabel = $project->style?->name ?? __('No style');
                        @endphp

                        <a
                            href="{{ route('projects.show', $project) }}"
                            wire:navigate
                            class="glass-card group relative flex flex-col overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:border-primary"
                            data-test="dashboard-recent-card"
                            data-project-id="{{ $project->id }}"
                        >
                            <div class="relative aspect-[16/9] overflow-hidden">
                                @if ($previewUrl)
                                    <img
                                        src="{{ $previewUrl }}"
                                        alt="{{ $project->title ?: __('Untitled project') }}"
                                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-background/85 via-background/20 to-transparent"></div>
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center bg-surface-container-high">
                                        <span class="material-symbols-outlined text-[40px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-col gap-0.5 p-stack-sm">
                                <p class="font-mono-xs text-mono-xs text-primary" data-test="dashboard-recent-card-meta">
                                    {{ $categoryLabel }} · {{ $styleLabel }}
                                </p>
                                <p class="font-headline-sm text-headline-sm font-bold text-on-surface" data-test="dashboard-recent-card-title">
                                    {{ $project->title ?: __('Untitled project') }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
</x-layouts::app>
