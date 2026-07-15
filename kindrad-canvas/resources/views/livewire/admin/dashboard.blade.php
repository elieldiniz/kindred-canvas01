<div class="flex flex-col gap-section" data-test="admin-dashboard-page">

    {{-- Metric tiles --}}
    <section class="grid gap-stack-md md:grid-cols-2 xl:grid-cols-4" data-test="admin-metrics-grid">
        <div class="glass-card p-stack-lg" data-test="admin-metric-users">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Total users') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary" data-test="admin-metric-users-total">
                {{ $this->totalUsers() }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-secondary">
                {{ __('+:count in 7d', ['count' => $this->newUsersLast7Days()]) }}
            </p>
        </div>

        <div class="glass-card p-stack-lg" data-test="admin-metric-generations">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Total generations') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary" data-test="admin-metric-generations-total">
                {{ $this->totalGenerations() }}
            </p>
            @php($statuses = $this->recentGenerations())
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Recent distribution') }}: @foreach ($statuses as $slug => $count){{ $slug }}={{ $count }}@if (! $loop->last), @endif @endforeach
            </p>
        </div>

        <div class="glass-card p-stack-lg" data-test="admin-metric-credits">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Credits in circulation') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary" data-test="admin-metric-credits-total">
                {{ $this->creditsInCirculation() }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-error">
                {{ __('Spent: :count', ['count' => abs($this->creditsSpent())]) }}
            </p>
        </div>

        <div class="glass-card p-stack-lg" data-test="admin-metric-pending">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Catalog coverage') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary">
                {{ __('Products') }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                <flux:link :href="route('admin.products.index')" wire:navigate>
                    {{ __('Manage catalog') }}
                </flux:link>
            </p>
        </div>
    </section>

    {{-- Financial panel --}}
    @php($statusBreakdown = $this->subscriptionStatusBreakdown())
    @php($plansRevenue = $this->plansRevenue())
    @php($mrr = $this->mrrFormatted())
    @php($arr = $this->arrFormatted())

    <section class="grid gap-stack-md lg:grid-cols-3" data-test="admin-financial-panel">
        <div class="glass-card p-stack-lg" data-test="admin-financial-mrr">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Monthly Recurring Revenue') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary">
                {{ $mrr }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('ARR (annualized): :value', ['value' => $arr]) }}
            </p>
        </div>

        <div class="glass-card p-stack-lg" data-test="admin-financial-arr">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Active subscriptions') }}
            </p>
            <p class="mt-stack-sm font-display-lg text-display-lg text-primary" data-test="admin-financial-active-count">
                {{ $statusBreakdown['active'] ?? 0 }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Total subscriptions: :count', ['count' => $this->totalSubscriptions()]) }}
            </p>
        </div>

        <div class="glass-card p-stack-lg" data-test="admin-financial-status">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Status breakdown') }}
            </p>
            <ul class="mt-stack-sm flex flex-col gap-stack-xs text-sm">
                @forelse ($statusBreakdown as $slug => $count)
                    <li class="flex items-center justify-between gap-stack-sm">
                        <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ $slug }}</span>
                        <span class="font-mono-sm text-mono-sm font-semibold text-on-surface">{{ $count }}</span>
                    </li>
                @empty
                    <li class="font-mono-sm text-mono-sm text-on-surface-variant">{{ __('No subscriptions yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section class="glass-card overflow-hidden bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-financial-plans">
        <div class="flex items-center justify-between border-b border-white/5 px-6 py-5">
            <div>
                <h2 class="font-bold text-lg text-white">
                    {{ __('Recurring revenue by plan') }}
                </h2>
                <p class="text-xs text-white/50 mt-0.5">
                    {{ __('Active subscribers × plan price, normalized to monthly. Annual plans divide by 12.') }}
                </p>
            </div>
        </div>

        @if (count($plansRevenue) === 0)
            <div class="p-stack-lg text-center" data-test="admin-financial-plans-empty">
                <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">payments</span>
                <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                    {{ __('No active subscriptions yet. As soon as the first one is created, the breakdown will appear here.') }}
                </p>
            </div>
        @else
            <table class="w-full text-left" data-test="admin-financial-plans-table">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Plan') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Interval') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Active subscribers') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Monthly revenue') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plansRevenue as $row)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors" data-test="admin-financial-plan-row-{{ $row->plan->slug }}">
                            <td class="px-6 py-4 font-medium text-sm text-white">
                                {{ $row->plan->name }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $row->plan->interval?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-white">
                                {{ $row->active_count }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-primary">
                                {{ 'R$ ' . number_format($row->monthly_revenue_cents / 100, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-white/[0.015]" data-test="admin-financial-plans-total">
                        <td colspan="3" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/40">
                            {{ __('Total MRR') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-primary">
                            {{ $mrr }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif
    </section>

    {{-- Audit log preview --}}
    <section class="glass-card overflow-hidden bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-audit-log">
        <div class="flex items-center justify-between border-b border-white/5 px-6 py-5">
            <div>
                <h2 class="font-bold text-lg text-white">
                    {{ __('Recent admin actions') }}
                </h2>
                <p class="text-xs text-white/50 mt-0.5">
                    {{ __('Last 20 audit log entries') }}
                </p>
            </div>
        </div>

        @php($logs = $this->recentAuditLogs())

        @if ($logs === [])
            <div class="p-stack-lg text-center" data-test="admin-audit-empty">
                <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">inbox</span>
                <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                    {{ __('No audit log entries yet.') }}
                </p>
            </div>
        @else
            <table class="w-full text-left" data-test="admin-audit-table">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('When') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Actor') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Action') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Target') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-b border-white/5 last:border-b-0 hover:bg-white/[0.02] transition-colors" data-test="admin-audit-row">
                            <td class="px-6 py-4 text-xs text-white/50 whitespace-nowrap">
                                {{ $log['created_at'] }}
                            </td>
                            <td class="px-6 py-4 font-medium text-sm text-white">
                                {{ $log['actor'] ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-primary">
                                {{ $log['action'] }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/60">
                                @if (! empty($log['target_href']))
                                    <flux:link :href="$log['target_href']" wire:navigate>
                                        {{ $log['target_label'] }}
                                    </flux:link>
                                @else
                                    {{ $log['target_label'] }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

</div>