<div class="flex flex-col gap-section" data-test="admin-layouts-index">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-bold text-3xl text-white">
                {{ __('Layouts') }}
            </h1>
            <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Manage print layouts available in the project wizard') }}
            </p>
        </div>

        <flux:button
            icon="plus"
            variant="primary"
            :href="route('admin.layouts.create')"
            wire:navigate
            data-test="admin-layout-create-button"
        >
            {{ __('New layout') }}
        </flux:button>
    </header>

    @if ($layouts->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-layouts-empty">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">squares-2x2</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No layouts yet.') }}
            </p>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-6 bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-layouts-table">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Name') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Slug') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Ratio') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Styles') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($layouts as $layout)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors" data-test="admin-layout-row" wire:key="layout-{{ $layout->id }}">
                            <td class="px-6 py-4 font-medium text-sm text-white">
                                {{ $layout->name }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $layout->slug }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/60">
                                {{ $layout->proportion_ratio }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" :variant="$layout->status?->slug === 'active' ? 'success' : 'danger'">
                                    {{ $layout->status?->name ?? '—' }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-white/50">
                                {{ $layout->styles_count }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-stack-sm">
                                    <a href="{{ route('admin.layouts.edit', $layout) }}" wire:navigate class="text-xs font-semibold text-white/70 hover:text-white px-3 py-1.5 rounded-lg border border-white/10 hover:bg-white/5 transition-all" data-test="admin-layout-edit-link">
                                        {{ __('Edit') }}
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $layout->id }})"
                                        class="text-xs font-semibold text-red-400 hover:text-red-300 px-3 py-1.5 rounded-lg border border-red-500/20 hover:bg-red-500/10 transition-all"
                                        data-test="admin-layout-delete-button"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <flux:modal wire:model="confirmDelete" data-test="admin-layout-delete-modal">
        <div class="space-y-stack-md">
            <div>
                <flux:heading size="lg">{{ __('Delete layout?') }}</flux:heading>
                <flux:text class="mt-stack-sm text-on-surface-variant">
                    {{ __('This action cannot be undone. Layouts with styles cannot be deleted.') }}
                </flux:text>
            </div>

            @error('deleteId')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    {{ $message }}
                </flux:callout>
            @enderror

            <div class="flex justify-end gap-stack-sm pt-stack-sm">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="$set('confirmDelete', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button
                    variant="danger"
                    wire:click="delete"
                    data-test="admin-layout-delete-confirm"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
