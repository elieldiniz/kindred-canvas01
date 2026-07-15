<div class="flex flex-col gap-section" data-test="admin-categories-index">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-bold text-3xl text-white">
                {{ __('Categories') }}
            </h1>
            <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Manage product categories and their style associations') }}
            </p>
        </div>

        <flux:button
            icon="plus"
            variant="primary"
            :href="route('admin.categories.create')"
            wire:navigate
            data-test="admin-category-create-button"
        >
            {{ __('New category') }}
        </flux:button>
    </header>

    @if ($categories->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-categories-empty">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">tag</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No categories yet.') }}
            </p>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-6 bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-categories-table">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Name') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Product') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Slug') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Styles') }}
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors" data-test="admin-category-row" wire:key="category-{{ $category->id }}">
                            <td class="px-6 py-4">
                                <p class="font-medium text-sm text-white">{{ $category->name }}</p>
                                @if ($category->description)
                                    <p class="text-xs text-white/50">
                                        {{ \Illuminate\Support\Str::limit($category->description, 60) }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $category->product?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-white/50">
                                {{ $category->slug }}
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-white/60">
                                {{ $category->styles_count }}
                            </td>
                            <td class="px-6 py-4">
                                <flux:badge size="sm" :variant="$category->status?->slug === 'active' ? 'success' : 'danger'">
                                    {{ $category->status?->name ?? '—' }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-stack-sm">
                                    <a href="{{ route('admin.categories.edit', $category) }}" wire:navigate class="text-xs font-semibold text-white/70 hover:text-white px-3 py-1.5 rounded-lg border border-white/10 hover:bg-white/5 transition-all">
                                        {{ __('Edit') }}
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $category->id }})"
                                        class="text-xs font-semibold text-red-400 hover:text-red-300 px-3 py-1.5 rounded-lg border border-red-500/20 hover:bg-red-500/10 transition-all"
                                        data-test="admin-category-delete-button"
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
            <flux:heading size="lg">{{ __('Delete category?') }}</flux:heading>
            <flux:text class="mt-stack-sm text-on-surface-variant">
                {{ __('This action cannot be undone.') }}
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
                    data-test="admin-category-delete-confirm"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>