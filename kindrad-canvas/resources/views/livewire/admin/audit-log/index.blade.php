<div class="flex flex-col gap-section" data-test="admin-audit-log-index">

    <header class="flex flex-col gap-stack-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('Audit Log') }}
            </h1>
            <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Track all admin actions across the platform.') }}
            </p>
        </div>
        <flux:badge variant="solid" color="primary" data-test="admin-audit-total">
            {{ trans_choice(':count entry|:count entries', $logs->total(), ['count' => $logs->total()]) }}
        </flux:badge>
    </header>

    {{-- Filters --}}
    <section class="glass-card flex flex-col gap-stack-sm p-stack-lg" data-test="admin-audit-filters">
        <div class="flex flex-wrap items-end gap-stack-md">
            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block font-label-sm text-label-sm text-on-surface-variant">{{ __('Actor') }}</label>
                <select
                    wire:model.live="filterActor"
                    class="w-full rounded-xl border border-outline-variant bg-surface-container/40 px-4 py-2.5 font-label-sm text-label-sm text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    data-test="audit-filter-actor"
                >
                    <option value="">{{ __('All actors') }}</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}">{{ $actor->name }} ({{ $actor->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="mb-1 block font-label-sm text-label-sm text-on-surface-variant">{{ __('Action') }}</label>
                <select
                    wire:model.live="filterAction"
                    class="w-full rounded-xl border border-outline-variant bg-surface-container/40 px-4 py-2.5 font-label-sm text-label-sm text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    data-test="audit-filter-action"
                >
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action->id }}">{{ $action->name }}</option>
                    @endforeach
                </select>
            </div>

            @if (($filterActor !== null && $filterActor !== '') || ($filterAction !== null && $filterAction !== ''))
                <flux:button wire:click="$set('filterActor', null); $set('filterAction', null)" variant="ghost" data-test="audit-filter-clear">
                    {{ __('Clear filters') }}
                </flux:button>
            @endif
        </div>
    </section>

    {{-- Log entries --}}
    @if ($logs->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-audit-empty">
            <span class="material-symbols-outlined text-[48px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">history</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No audit log entries match these filters.') }}
            </p>
        </div>
    @else
        <section class="glass-card overflow-hidden border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-audit-table-wrapper">
            <table class="w-full text-left" data-test="admin-audit-table">
                <thead>
                    <tr class="border-b border-white/10 bg-white/5">
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-white/50">
                            {{ __('When') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-white/50">
                            {{ __('Actor') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-white/50">
                            {{ __('Action') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-white/50">
                            {{ __('Target') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-white/50">
                            {{ __('Details') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $isExpanded = isset($expanded[$log->id]);
                            $hasDetails = is_array($log->payload) && ! empty($log->payload);
                        @endphp
                        <tr
                            class="border-b border-white/5 last:border-b-0 transition-colors hover:bg-white/[0.04]"
                            data-test="admin-audit-row"
                            wire:key="log-{{ $log->id }}"
                        >
                            <td class="px-6 py-4 font-mono-sm text-sm text-white/70 whitespace-nowrap" data-test="admin-audit-row-date">
                                <div>{{ $log->created_at?->format('M j, Y') ?? '—' }}</div>
                                <div class="text-xs text-white/40">{{ $log->created_at?->format('H:i:s') ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-white" data-test="admin-audit-row-actor">
                                {{ $log->actor?->name ?? '—' }}
                                @if ($log->actor?->email)
                                    <div class="text-xs text-white/40">{{ $log->actor->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4" data-test="admin-audit-row-action">
                                <flux:badge size="sm" :variant="$this->badgeVariantFor($log->action?->slug)" data-test="admin-audit-row-badge">
                                    {{ $log->action?->name ?? $log->action?->slug ?? 'unknown' }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 font-mono-sm text-sm text-white/70 whitespace-nowrap" data-test="admin-audit-row-target">
                                {{ class_basename($log->target_type ?? '') }}#{{ $log->target_id ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap" data-test="admin-audit-row-actions">
                                @if ($hasDetails)
                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="ghost"
                                        wire:click="toggleDetails({{ $log->id }})"
                                        data-test="admin-audit-row-toggle"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[14px] transition-transform {{ $isExpanded ? 'rotate-180' : '' }}"
                                            style="font-variation-settings: 'FILL' 1, 'wght' 400;"
                                            aria-hidden="true"
                                        >expand_more</span>
                                        {{ $isExpanded ? __('Hide details') : __('Show details') }}
                                    </flux:button>
                                @else
                                    <span class="text-xs text-white/30">—</span>
                                @endif
                            </td>
                        </tr>
                        @if ($isExpanded && $hasDetails)
                            <tr class="border-b border-white/5 bg-white/[0.03]" data-test="admin-audit-row-details-tr" wire:key="log-{{ $log->id }}-details">
                                <td colspan="5" class="px-6 py-5">
                                    <div class="rounded-2xl border border-white/10 bg-background/50 p-4">
                                        @include('partials.audit-details', [
                                            'payload' => $log->payload,
                                            'slug' => $log->action?->slug,
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </section>

        <div class="mt-stack-md" data-test="admin-audit-pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>
