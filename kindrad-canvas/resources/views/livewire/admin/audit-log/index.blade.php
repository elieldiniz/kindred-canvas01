<div class="flex flex-col gap-section" data-test="admin-audit-log-index">

    <header>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">
            {{ __('Audit Log') }}
        </h1>
        <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Track all admin actions across the platform') }}
        </p>
    </header>

    {{-- Filters --}}
    <div class="glass-card p-stack-md">
        <div class="flex flex-wrap items-end gap-stack-md">
            <div class="flex-1 min-w-[160px]">
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">{{ __('Actor') }}</label>
                <select
                    wire:model.live="filterActor"
                    class="w-full rounded-xl border border-outline-variant bg-transparent px-4 py-2.5 font-label-sm text-label-sm text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    data-test="audit-filter-actor"
                >
                    <option value="">{{ __('All actors') }}</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}">{{ $actor->name }} ({{ $actor->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[160px]">
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">{{ __('Action') }}</label>
                <select
                    wire:model.live="filterAction"
                    class="w-full rounded-xl border border-outline-variant bg-transparent px-4 py-2.5 font-label-sm text-label-sm text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    data-test="audit-filter-action"
                >
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action->id }}">{{ $action->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Log entries --}}
    @if ($logs->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-audit-empty">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">document-text</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No audit log entries yet.') }}
            </p>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-stack-md" data-test="admin-audit-table">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Date') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Actor') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Action') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Target') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Details') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-t border-outline-variant/30" data-test="admin-audit-row" wire:key="log-{{ $log->id }}">
                            <td class="px-stack-md py-stack-sm font-mono-xs text-mono-xs text-on-surface-variant whitespace-nowrap">
                                {{ $log->created_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td class="px-stack-md py-stack-sm font-label-sm text-label-sm text-on-surface">
                                {{ $log->actor?->name ?? '—' }}
                            </td>
                            <td class="px-stack-md py-stack-sm">
                                <flux:badge size="sm" variant="solid">
                                    {{ $log->action?->name ?? '—' }}
                                </flux:badge>
                            </td>
                            <td class="px-stack-md py-stack-sm font-mono-xs text-mono-xs text-on-surface-variant">
                                {{ class_basename($log->target_type ?? '') }}#{{ $log->target_id ?? '—' }}
                            </td>
                            <td class="px-stack-md py-stack-sm font-mono-xs text-mono-xs text-on-surface-variant max-w-xs truncate">
                                {{ $log->payload ? json_encode($log->payload) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-stack-md">
            {{ $logs->links() }}
        </div>
    @endif
</div>
