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

        $dunningSubscription = App\Models\Subscription::query()
            ->where('user_id', auth()->id())
            ->where('stripe_status', 'past_due')
            ->latest('id')
            ->first();
    @endphp

    <div class="flex flex-col gap-section p-margin-page" data-test="dashboard-page">

        @if ($dunningSubscription)
            @php
                $graceDays = (int) config('billing.grace_days', 7);
                $anchor = $dunningSubscription->ends_at ?? $dunningSubscription->current_period_end;
                $graceExpiresAt = $anchor?->copy()->addDays($graceDays);
                $isExpired = $dunningSubscription->isPastDueAndExpired($graceDays);
            @endphp
            <flux:callout
                variant="{{ $isExpired ? 'danger' : 'warning' }}"
                icon="exclamation-triangle"
                data-test="dashboard-dunning-banner"
            >
                <flux:callout.heading>
                    {{ $isExpired
                        ? __('Sua assinatura está com pagamento atrasado e o uso foi suspenso.')
                        : __('Detectamos uma falha no pagamento da sua assinatura.') }}
                </flux:callout.heading>
                <flux:callout.text>
                    @if ($graceExpiresAt && ! $isExpired)
                        {{ __('Você tem até :date para atualizar o método de pagamento sem perder acesso.', ['date' => $graceExpiresAt->format('d/m/Y')]) }}
                    @elseif ($isExpired)
                        {{ __('O período de carência expirou. Novas gerações estão bloqueadas até a regularização.') }}
                    @endif
                </flux:callout.text>
                <div class="mt-3">
                    <a
                        href="{{ route('billing.index') }}"
                        wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(99,54,255,0.3)] hover:shadow-[0_0_25px_rgba(99,54,255,0.5)] transition-all"
                        data-test="dashboard-dunning-banner-cta"
                    >
                        {{ __('Atualizar método de pagamento') }}
                    </a>
                </div>
            </flux:callout>
        @endif

        @if (session('status'))
            <div class="glass-card border-success/30 bg-success/10 p-stack-md font-label-md text-label-md text-success" data-test="dashboard-session-status">
                {{ session('status') }}
            </div>
        @endif

        <section class="flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between py-8 pb-2" data-test="dashboard-hero">
            <div>
                <h1 class="font-display-lg text-display-lg font-extrabold" data-test="dashboard-greeting">
                    <span class="bg-gradient-to-r from-white via-white to-white/60 bg-clip-text text-transparent">
                        {{ __('Welcome back,') }}
                    </span>
                    <br>
                    <span class="bg-gradient-to-r from-primary via-purple-400 to-cyan-400 bg-clip-text text-transparent">
                        {{ auth()->user()->name }}.
                    </span>
                </h1>
                <p class="mt-3 max-w-xl text-base text-white/50 leading-relaxed">
                    {{ __('Your creative studio is ready. What will you manifest today?') }}
                </p>

                <div class="mt-6 flex items-center gap-4">
                    <a
                        href="{{ route('projects.new') }}"
                        wire:navigate
                        class="gradient-generate inline-flex items-center gap-2 rounded-full px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02] transition-all duration-300 whitespace-nowrap"
                        data-test="dashboard-new-project-button"
                    >
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add</span>
                        {{ __('New Project') }}
                    </a>
                </div>
            </div>

            <div class="shrink-0" data-test="dashboard-credits-card">
                <a
                    href="{{ route('credits.index') }}"
                    wire:navigate
                    class="group flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-transparent px-5 py-4 shadow-[0_0_20px_rgba(52,211,153,0.1)] hover:shadow-[0_0_30px_rgba(52,211,153,0.2)] hover:border-emerald-500/50 transition-all duration-300"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;" aria-hidden="true">bolt</span>
                    </span>
                    <div>
                        <p class="text-xl font-extrabold text-white leading-none">{{ $creditBalance }}</p>
                        <p class="text-xs text-white/50 mt-0.5 uppercase tracking-widest">{{ __('credits') }}</p>
                    </div>
                    <span class="ml-2 text-white/30 group-hover:text-white/60 transition-colors text-lg">→</span>
                </a>
            </div>
        </section>

        <section class="grid gap-stack-sm sm:grid-cols-2" data-test="dashboard-stats-grid">
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
        </section>

        <section class="flex flex-col gap-stack-md" data-test="dashboard-recent-projects">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">
                    {{ __('Recent Projects') }}
                </h2>
                <p class="font-body-xs text-body-xs text-on-surface-variant">
                    {{ __('Continue your latest work.') }}
                </p>
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
