<div data-test="wizard-mode-step">
    @error('modeId')
        <p class="font-label-md text-label-md text-error" data-test="wizard-mode-error">{{ $message }}</p>
    @enderror

    <div class="mx-auto grid w-full max-w-3xl grid-cols-1 gap-stack-md md:grid-cols-2" data-test="wizard-mode-grid">
        @foreach ($this->modes() as $mode)
            <button
                type="button"
                wire:click="selectMode({{ $mode['id'] }})"
                data-test="wizard-mode-tile"
                data-mode-id="{{ $mode['id'] }}"
                class="glass-card flex flex-col items-start gap-stack-md rounded-xl p-stack-lg text-left group cursor-pointer transition-colors {{ $modeId === $mode['id'] ? 'active-selection selection-glow' : '' }}"
            >
                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container-high text-primary transition-transform group-hover:scale-110">
                    <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 0, 'wght' 400;">{{ $mode['icon'] }}</span>
                </span>

                <div class="flex w-full items-center justify-between">
                    <span class="font-headline-md text-headline-md text-on-surface">{{ $mode['name'] }}</span>

                    @if ($modeId === $mode['id'])
                        <span class="material-symbols-outlined text-[28px] text-primary" style="font-variation-settings: 'FILL' 1;" data-test="wizard-mode-tile-selected">check_circle</span>
                    @endif
                </div>
            </button>
        @endforeach
    </div>
</div>