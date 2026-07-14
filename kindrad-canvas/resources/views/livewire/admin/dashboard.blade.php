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
                {{ __('Phase 5.2+') }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('CRUD ships in next phase') }}
            </p>
        </div>
    </section>

    {{-- Audit log preview --}}
    <section class="glass-card overflow-hidden" data-test="admin-audit-log">
        <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-low p-stack-md">
            <div>
                <h2 class="font-headline-md text-headline-md text-on-surface">
                    {{ __('Recent admin actions') }}
                </h2>
                <p class="font-mono-sm text-mono-sm text-on-surface-variant">
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
                <thead class="border-b border-outline-variant bg-surface-container-low">
                    <tr>
                        <th class="px-stack-md py-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                            {{ __('When') }}
                        </th>
                        <th class="px-stack-md py-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                            {{ __('Actor') }}
                        </th>
                        <th class="px-stack-md py-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                            {{ __('Action') }}
                        </th>
                        <th class="px-stack-md py-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                            {{ __('Target') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-b border-outline-variant/30 last:border-b-0 hover:bg-surface-container-high" data-test="admin-audit-row">
                            <td class="px-stack-md py-stack-md font-mono-sm text-mono-sm text-on-surface-variant">
                                {{ $log['created_at'] }}
                            </td>
                            <td class="px-stack-md py-stack-md font-label-md text-label-md text-on-surface">
                                {{ $log['actor'] ?? '—' }}
                            </td>
                            <td class="px-stack-md py-stack-md font-label-md text-label-md text-primary">
                                {{ $log['action'] }}
                            </td>
                            <td class="px-stack-md py-stack-md font-mono-sm text-mono-sm text-on-surface-variant">
                                {{ $log['target'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

</div>