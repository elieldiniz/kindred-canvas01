@props([
    'icon' => null,
    'label' => '',
    'value' => null,
])

<div
    class="flex items-center justify-between gap-stack-md py-stack-sm"
    data-test="preview-row"
>
    <div class="flex items-center gap-stack-sm">
        @if ($icon)
            <span
                class="material-symbols-outlined text-[20px] text-on-surface-variant"
                style="font-variation-settings: 'FILL' 0, 'wght' 400;"
                aria-hidden="true"
            >{{ $icon }}</span>
        @endif
        <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
            {{ $label }}
        </span>
    </div>

    <span
        class="font-label-md text-label-md text-on-surface @if ($value === null) text-on-surface-variant @endif"
        data-test="preview-row-value"
    >
        @if ($value === null || $value === '')
            —
        @else
            {{ $value }}
        @endif
    </span>
</div>