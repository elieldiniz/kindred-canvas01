@props([
    'step' => 1,
    'total' => 7,
    'sectionName' => '',
])

@php
    $safeStep = max(1, min((int) $step, (int) $total));
    $safeTotal = max(1, (int) $total);
    $paddedStep = str_pad((string) $safeStep, 2, '0', STR_PAD_LEFT);
    $paddedTotal = str_pad((string) $safeTotal, 2, '0', STR_PAD_LEFT);
    $fillPct = number_format(($safeStep / $safeTotal) * 100, 3, '.', '');
@endphp

<div
    data-step="{{ $safeStep }}"
    data-total="{{ $safeTotal }}"
    data-fill-percent="{{ $fillPct }}"
    class="flex w-full flex-col gap-2"
>
    <div class="flex items-center justify-between font-mono-sm text-mono-sm uppercase tracking-widest">
        <span class="text-primary">STEP {{ $paddedStep }} OF {{ $paddedTotal }}</span>
        <span class="text-on-surface-variant">{{ $sectionName }}</span>
    </div>

    <div class="relative h-[2px] w-full overflow-hidden rounded-full bg-surface-container-highest">
        <div
            class="absolute inset-y-0 left-0 rounded-full bg-primary shadow-[0_0_10px_rgba(192,193,255,0.6)] transition-all duration-300 ease-out"
            style="width: {{ $fillPct }}%;"
            data-test="progress-bar-fill"
        ></div>
    </div>
</div>
