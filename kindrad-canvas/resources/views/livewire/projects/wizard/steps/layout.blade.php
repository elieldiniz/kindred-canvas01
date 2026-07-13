<div data-test="wizard-layout-step">
    @error('layoutId')
        <p class="font-label-md text-label-md text-error" data-test="wizard-layout-error">{{ $message }}</p>
    @enderror

    @php($list = $this->layouts())

    @if ($list->isEmpty())
        <div class="glass-card mx-auto flex w-full max-w-md flex-col items-center gap-stack-md rounded-2xl p-stack-lg text-center" data-test="wizard-layout-empty">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0, 'wght' 400;">dashboard</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('No layouts available') }}</h3>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">{{ __('Choose another style to see layouts.') }}</p>
            <button
                type="button"
                wire:click="goToStyles"
                data-test="wizard-layout-empty-action"
                class="inline-flex items-center gap-2 rounded-full bg-primary px-stack-lg py-2 font-label-md text-label-md font-bold text-on-primary shadow-sm shadow-primary/30 transition-transform hover:scale-105"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>{{ __('Edit style') }}</span>
            </button>
        </div>
    @else
        <div class="mx-auto grid w-full max-w-5xl grid-cols-2 gap-stack-md md:grid-cols-4" data-test="wizard-layout-grid">
            @foreach ($list as $layout)
                @php($padding = $this->safeAreaPadding($layout->safe_area_overlay))
                <button
                    type="button"
                    wire:click="selectLayout({{ $layout->id }})"
                    data-test="wizard-layout-tile"
                    data-layout-id="{{ $layout->id }}"
                    class="aspect-square relative overflow-hidden rounded-xl border border-outline-variant group cursor-pointer text-left transition-colors {{ $layoutId === $layout->id ? 'selection-glow active-selection' : '' }}"
                >
                    @if ($layout->preview_path)
                        <img
                            src="{{ $layout->preview_path }}"
                            alt="{{ $layout->name }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    @else
                        <div class="absolute inset-0 flex items-center justify-center bg-surface-container-high text-primary">
                            <span class="material-symbols-outlined text-[48px]" style="font-variation-settings: 'FILL' 0, 'wght' 400;">dashboard</span>
                        </div>
                    @endif

                    <div
                        class="pointer-events-none absolute rounded-lg border-2 border-dashed border-primary/40"
                        style="top: {{ $padding['top'] }}%; right: {{ $padding['right'] }}%; bottom: {{ $padding['bottom'] }}%; left: {{ $padding['left'] }}%;"
                    ></div>

                    <div class="absolute inset-0 bg-gradient-to-t from-background/70 via-background/10 to-transparent"></div>

                    <div class="absolute right-2 top-2">
                        @if ($layoutId === $layout->id)
                            <span class="material-symbols-outlined text-[28px] text-primary drop-shadow-md" style="font-variation-settings: 'FILL' 1;" data-test="wizard-layout-tile-selected">check_circle</span>
                        @endif
                    </div>

                    <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 p-stack-md">
                        <span class="material-symbols-outlined text-[18px] text-primary" style="font-variation-settings: 'FILL' 0, 'wght' 400;">dashboard</span>
                        <span class="font-label-md text-label-md font-bold text-on-surface drop-shadow-md">{{ $layout->name }}</span>
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>
