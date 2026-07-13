@props([
    'showExit' => true,
])

<header class="sticky top-0 z-20 border-b border-outline-variant bg-surface/80 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-4 px-gutter">
        <a href="{{ route('projects.new') }}" class="flex items-center gap-3 text-on-surface" wire:navigate>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-on-primary shadow-sm shadow-primary/30">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1, 'wght' 500;">palette</span>
            </span>
            <span class="font-headline-md text-headline-md font-semibold tracking-tight">Kindred Canvas</span>
        </a>

        @if ($showExit)
            <button
                type="button"
                class="ghost-button inline-flex items-center gap-2 rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-on-surface"
                x-on:click="$flux.modal('exit-confirm').show()"
                data-test="wizard-exit-button"
            >
                <span class="material-symbols-outlined text-[18px]">close</span>
                <span>{{ __('Exit') }}</span>
            </button>
        @endif
    </div>
</header>
