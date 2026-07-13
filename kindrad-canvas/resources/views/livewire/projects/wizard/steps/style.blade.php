<div data-test="wizard-style-step">
    @error('styleId')
        <p class="font-label-md text-label-md text-error" data-test="wizard-style-error">{{ $message }}</p>
    @enderror

    @php($list = $this->styles())

    @if ($list->isEmpty())
        <div class="glass-card mx-auto flex w-full max-w-md flex-col items-center gap-stack-md rounded-2xl p-stack-lg text-center" data-test="wizard-style-empty">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0, 'wght' 400;">style</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('No styles available for this category') }}</h3>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">{{ __('Try another category or browse available styles.') }}</p>
            <button
                type="button"
                wire:click="goToCategories"
                data-test="wizard-style-empty-action"
                class="inline-flex items-center gap-2 rounded-full bg-primary px-stack-lg py-2 font-label-md text-label-md font-bold text-on-primary shadow-sm shadow-primary/30 transition-transform hover:scale-105"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>{{ __('Browse other categories') }}</span>
            </button>
        </div>
    @else
        <div class="mx-auto grid w-full max-w-5xl grid-cols-2 gap-stack-md md:grid-cols-3 lg:grid-cols-5" data-test="wizard-style-grid">
            @foreach ($list as $style)
                <button
                    type="button"
                    wire:click="selectStyle({{ $style->id }})"
                    data-test="wizard-style-tile"
                    data-style-id="{{ $style->id }}"
                    class="aspect-square relative overflow-hidden rounded-xl border border-outline-variant group cursor-pointer text-left transition-colors {{ $styleId === $style->id ? 'selection-glow active-selection' : '' }}"
                >
                    @if ($style->thumbnail_path)
                        <img
                            src="{{ $style->thumbnail_path }}"
                            alt="{{ $style->name }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    @else
                        <div class="absolute inset-0 flex items-center justify-center bg-surface-container-high text-primary">
                            <span class="material-symbols-outlined text-[48px]" style="font-variation-settings: 'FILL' 0, 'wght' 400;">palette</span>
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-background/80 via-background/20 to-transparent"></div>

                    <div class="absolute right-2 top-2">
                        @if ($styleId === $style->id)
                            <span class="material-symbols-outlined text-[28px] text-primary drop-shadow-md" style="font-variation-settings: 'FILL' 1;" data-test="wizard-style-tile-selected">check_circle</span>
                        @endif
                    </div>

                    <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 p-stack-md">
                        <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings: 'FILL' 0, 'wght' 400;">palette</span>
                        <span class="font-label-md text-label-md font-bold text-on-surface drop-shadow-md">{{ $style->name }}</span>
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>
