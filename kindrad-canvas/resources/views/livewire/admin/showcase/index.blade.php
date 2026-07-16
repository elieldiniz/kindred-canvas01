<div
    class="flex flex-col gap-section"
    data-test="admin-showcase-index"
>

    {{-- Upload form --}}
    <section class="glass-card p-stack-lg" data-test="admin-showcase-upload">
        <header class="mb-stack-md">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ __('Upload new artwork') }}</h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Recommended: PNG or JPEG up to 5 MB. Images appear on the public welcome page.') }}
            </p>
        </header>

        <form wire:submit="saveArtwork" class="flex flex-col gap-stack-md">
            <flux:input
                wire:model="newTitle"
                type="text"
                :label="__('Title (optional)')"
                placeholder="{{ __('e.g. Cozy winter mug') }}"
                maxlength="255"
                data-test="admin-showcase-title"
            />

            <div>
                <label class="mb-1 block font-label-sm text-label-sm text-on-surface-variant">{{ __('Image file') }}</label>
                <input
                    type="file"
                    wire:model="newImage"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full rounded-xl border border-outline-variant bg-surface-container/40 px-4 py-2.5 font-label-sm text-label-sm text-on-surface file:mr-4 file:rounded-md file:border-0 file:bg-primary/30 file:px-3 file:py-2 file:text-label-sm file:text-white"
                    data-test="admin-showcase-file"
                />
            </div>

            @error('newImage') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 font-label-md text-label-md text-white shadow-md transition hover:bg-primary/90" data-test="admin-showcase-submit">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">upload</span>
                    <span wire:loading.remove wire:target="saveArtwork">{{ __('Upload') }}</span>
                    <span wire:loading wire:target="saveArtwork">{{ __('Uploading...') }}</span>
                </button>
            </div>
        </form>
    </section>

    {{-- List --}}
    <section class="glass-card overflow-hidden" data-test="admin-showcase-list">
        <header class="border-b border-white/5 px-stack-lg py-stack-md">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                {{ __('Current items') }}
                <span class="ml-2 text-on-surface-variant">({{ $items->count() }})</span>
            </h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Order controls carousel sequence. Inactive items stay in storage but are hidden from the welcome page.') }}
            </p>
        </header>

        @if ($items->isEmpty())
            <div class="p-stack-lg text-center" data-test="admin-showcase-empty">
                <span class="material-symbols-outlined text-[48px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">image</span>
                <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                    {{ __('Nothing uploaded yet.') }}
                </p>
            </div>
        @else
            <table class="w-full" data-test="admin-showcase-table">
                <thead class="bg-white/5 text-left">
                    <tr>
                        <th class="px-4 py-3 font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">{{ __('Preview') }}</th>
                        <th class="px-4 py-3 font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">{{ __('Title') }}</th>
                        <th class="px-4 py-3 font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">{{ __('Order') }}</th>
                        <th class="px-4 py-3 text-right font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $i => $item)
                        <tr
                            class="border-t border-white/5 hover:bg-white/[0.02]"
                            data-test="admin-showcase-row"
                            wire:key="showcase-row-{{ $item->id }}"
                        >
                            <td class="px-4 py-2">
                                <img
                                    src="{{ $item->imageUrl() }}"
                                    alt="{{ $item->title ?: 'Showcase item' }}"
                                    class="h-12 w-10 rounded-md object-cover ring-1 ring-white/10"
                                    loading="lazy"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <form wire:submit="updateTitle({{ $item->id }})">
                                    <input
                                        type="text"
                                        wire:model="titles.{{ $item->id }}"
                                        value="{{ $item->title }}"
                                        maxlength="255"
                                        class="w-full rounded-md border border-white/10 bg-background/50 px-2 py-1 font-label-sm text-label-sm text-on-surface focus:border-primary focus:outline-none"
                                        data-test="admin-showcase-row-title"
                                    />
                                </form>
                            </td>
                            <td class="px-4 py-2 font-mono-xs text-mono-xs text-on-surface-variant">
                                #{{ $item->sort_order }}
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($i > 0)
                                        <flux:button
                                            type="button"
                                            size="xs"
                                            variant="ghost"
                                            wire:click="move({{ $item->id }}, 'up')"
                                            data-test="admin-showcase-move-up"
                                            :aria-label="__('Move up')"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_upward</span>
                                        </flux:button>
                                    @endif
                                    @if ($i < $items->count() - 1)
                                        <flux:button
                                            type="button"
                                            size="xs"
                                            variant="ghost"
                                            wire:click="move({{ $item->id }}, 'down')"
                                            data-test="admin-showcase-move-down"
                                            :aria-label="__('Move down')"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_downward</span>
                                        </flux:button>
                                    @endif

                                    <flux:button
                                        type="button"
                                        size="xs"
                                        :variant="$item->is_active ? 'primary' : 'ghost'"
                                        wire:click="toggleActive({{ $item->id }})"
                                        data-test="admin-showcase-toggle"
                                        :aria-pressed="$item->is_active"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[16px]"
                                            style="font-variation-settings: 'FILL' {{ $item->is_active ? 1 : 0 }}, 'wght' 400;"
                                            aria-hidden="true"
                                        >{{ $item->is_active ? 'visibility' : 'visibility_off' }}</span>
                                    </flux:button>

                                    <flux:button
                                        type="button"
                                        size="xs"
                                        variant="danger"
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="{{ __('Delete this artwork? The image file is also removed from storage.') }}"
                                        data-test="admin-showcase-delete"
                                        :aria-label="__('Delete')"
                                    >
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete</span>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
