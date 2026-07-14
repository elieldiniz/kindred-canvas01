@props([
    'wireClick' => null,
    'icon' => null,
    'name' => '',
    'description' => null,
    'thumbnail' => null,
    'selected' => false,
    'aspect' => 'square',
    'compact' => false,
    'badge' => null,
    'testId' => null,
])

@php($testId ??= 'selection-card')
@php($aspectClass = $compact ? '' : ($aspect === 'portrait' ? 'aspect-4/5' : 'aspect-square'))

<button
    type="button"
    @if ($wireClick) wire:click="{{ $wireClick }}" @endif
    data-test="{{ $testId }}"
    class="glass-card group relative flex {{ $compact ? 'flex-row items-center gap-3' : 'flex-col' }} w-full cursor-pointer overflow-hidden text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary {{ $aspectClass }} {{ $selected ? 'selection-glow active-selection' : '' }}"
>
    {{-- Thumbnail or Icon --}}
    @if ($thumbnail)
        <img
            src="{{ $thumbnail }}"
            alt="{{ $name }}"
            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-background/85 via-background/20 to-transparent"></div>
    @else
        <div class="{{ $compact ? 'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-container-high' : 'relative flex items-center justify-center bg-surface-container-high pt-4' }}">
            <span
                class="material-symbols-outlined {{ $compact ? 'text-[20px]' : 'text-[40px]' }} text-primary"
                style="font-variation-settings: 'FILL' 0, 'wght' 400;"
                aria-hidden="true"
            >{{ $icon ?? 'image' }}</span>

            {{-- Compact: check badge on icon corner --}}
            @if ($selected && $compact)
                <span
                    class="absolute -bottom-1 -right-1 flex h-[18px] w-[18px] items-center justify-center rounded-full bg-primary text-on-primary shadow-lg shadow-primary/30"
                    data-test="{{ $testId }}-selected"
                    aria-label="Selected"
                >
                    <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 600;">check</span>
                </span>
            @endif
        </div>
    @endif

    {{-- Non-compact: check badge top-right --}}
    @if (! $compact && $selected)
        <div class="absolute right-2.5 top-2.5">
            <span
                class="material-symbols-outlined flex h-8 w-8 items-center justify-center rounded-full bg-primary text-on-primary shadow-lg shadow-primary/30"
                style="font-variation-settings: 'FILL' 1, 'wght' 600;"
                data-test="{{ $testId }}-selected"
                aria-label="Selected"
            >check_circle</span>
        </div>
    @endif

    {{-- Content --}}
    <div class="{{ $compact ? 'flex flex-1 flex-col gap-0.5 pe-3' : 'absolute inset-x-0 bottom-0 flex flex-col gap-0.5 p-3' }}">
        <div class="flex items-center gap-2">
            <p class="{{ $compact ? 'font-body-sm text-body-sm' : 'font-headline-sm text-headline-sm' }} font-bold text-on-surface {{ ! $compact ? 'drop-shadow-md' : '' }}">
                {{ $name }}
            </p>
            @if ($badge)
                <span class="inline-flex items-center rounded-full bg-primary/20 px-2 py-0.5 font-mono-xs text-mono-xs text-primary">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if ($description)
            <p class="{{ $compact ? 'font-body-xs text-body-xs' : 'font-body-xs text-body-xs' }} text-on-surface-variant">
                {{ $description }}
            </p>
        @endif
    </div>
</button>
