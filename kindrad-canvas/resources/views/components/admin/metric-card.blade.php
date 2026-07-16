@props([
    'icon' => 'info',
    'label' => '',
    'value' => 0,
])

<div
    {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md']) }}
>
    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/20 text-primary">
        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">{{ $icon }}</span>
    </span>
    <div class="flex flex-col">
        <span class="text-2xl font-extrabold text-on-surface">{{ $value }}</span>
        <span class="text-xs uppercase tracking-widest text-on-surface-variant">{{ $label }}</span>
    </div>
</div>
