<div class="flex flex-col gap-section" data-test="admin-prompt-templates-index">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-bold text-3xl text-white">
                {{ __('Prompt templates') }}
            </h1>
            <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Manage the AI prompts keyed by product × category × style × layout') }}
            </p>
        </div>

        <flux:button
            icon="plus"
            variant="primary"
            :href="route('admin.prompt-templates.create')"
            wire:navigate
            data-test="admin-prompt-template-create-button"
        >
            {{ __('New template') }}
        </flux:button>
    </header>

    @php($hasBumpedVersions = $templates->contains(fn ($t) => $t->version > 1))

    @if ($templates->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-prompt-templates-empty">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">code-bracket-square</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No prompt templates yet.') }}
            </p>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-6 bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-prompt-templates-table">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Product') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Category') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Style') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Layout') }}
                        </th>
                        @if ($hasBumpedVersions)
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                                {{ __('Version') }}
                            </th>
                        @endif
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($templates as $template)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors" data-test="admin-prompt-template-row" wire:key="template-{{ $template->id }}">
                            <td class="px-6 py-4 font-medium text-sm text-white">
                                {{ $template->product?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $template->category?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $template->style?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $template->layout?->name ?? '—' }}
                            </td>
                            @if ($hasBumpedVersions)
                                <td class="px-6 py-4 text-right font-mono-xs text-mono-xs {{ $template->version > 1 ? 'text-primary' : 'text-on-surface-variant' }}">
                                    {{ $template->version > 1 ? 'v'.$template->version : '—' }}
                                </td>
                            @endif
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-stack-sm">
                                    <a href="{{ route('admin.prompt-templates.edit', $template) }}" wire:navigate class="text-xs font-semibold text-white/70 hover:text-white px-3 py-1.5 rounded-lg border border-white/10 hover:bg-white/5 transition-all">
                                        {{ __('Edit') }}
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $template->id }})"
                                        class="text-xs font-semibold text-red-400 hover:text-red-300 px-3 py-1.5 rounded-lg border border-red-500/20 hover:bg-red-500/10 transition-all"
                                        data-test="admin-prompt-template-delete-button"
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

    <flux:modal wire:model="confirmDelete">
        <div class="space-y-stack-md">
            <flux:heading size="lg">{{ __('Delete prompt template?') }}</flux:heading>
            <flux:text class="mt-stack-sm text-on-surface-variant">
                {{ __('Generations requiring this template will fail until you recreate it.') }}
            </flux:text>

            <div class="flex justify-end gap-stack-sm pt-stack-sm">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="$set('confirmDelete', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>

                <flux:button
                    variant="danger"
                    wire:click="delete"
                    data-test="admin-prompt-template-delete-confirm"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>