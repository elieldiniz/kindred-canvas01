<div data-test="wizard-category-step">
    @error('categoryId')
        <p class="font-label-md text-label-md text-error" data-test="wizard-category-error">{{ $message }}</p>
    @enderror

    @php($cats = $this->categories())

    @if ($cats->isEmpty())
        <div class="glass-card mx-auto flex w-full max-w-md flex-col items-center gap-stack-md rounded-2xl p-stack-lg text-center" data-test="wizard-category-empty">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0, 'wght' 400;">style</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('No categories available') }}</h3>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">{{ __("We couldn't find any active categories.") }}</p>
            <span class="font-label-md text-label-md text-on-surface-variant">{{ __('Contact support') }}</span>
        </div>
    @else
        <div class="mx-auto grid w-full max-w-4xl grid-cols-1 gap-stack-md md:grid-cols-3" data-test="wizard-category-grid">
            @foreach ($cats as $cat)
                <button
                    type="button"
                    wire:click="selectCategory({{ $cat->id }})"
                    data-test="wizard-category-tile"
                    data-category-id="{{ $cat->id }}"
                    data-category-slug="{{ $cat->slug }}"
                    class="glass-card flex flex-col items-start gap-stack-md rounded-xl p-stack-lg text-left group cursor-pointer transition-colors {{ $categoryId === $cat->id ? 'active-selection selection-glow' : '' }}"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container-high text-primary transition-transform group-hover:scale-110">
                        <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 0, 'wght' 400;">{{ $this->iconFor($cat->slug) }}</span>
                    </span>

                    @if ($cat->thumbnail_path)
                        <img
                            src="{{ $cat->thumbnail_path }}"
                            alt="{{ $cat->name }}"
                            class="h-20 w-full rounded-lg object-cover"
                        />
                    @endif

                    <div class="flex w-full items-center justify-between gap-stack-sm">
                        <span class="font-headline-md text-headline-md text-on-surface">{{ $cat->name }}</span>

                        @if ($categoryId === $cat->id)
                            <span class="material-symbols-outlined text-[28px] text-primary" style="font-variation-settings: 'FILL' 1;" data-test="wizard-category-tile-selected">check_circle</span>
                        @endif
                    </div>

                    @if (! empty($cat->description))
                        <p class="font-label-md text-label-md text-on-surface-variant">{{ $cat->description }}</p>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
