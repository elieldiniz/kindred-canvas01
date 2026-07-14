@props([
    'icon' => null,
    'title' => '',
    'helper' => null,
    'slug' => null,
])

@php($blockSlug = $slug ?? \Illuminate\Support\Str::slug($title))

<section
    class="glass-card flex flex-col gap-stack-sm p-stack-md"
    data-test="block-{{ $blockSlug }}"
>
    @if ($icon || $title || $helper)
        <header class="flex items-start gap-stack-sm">
            @if ($icon)
                <span
                    class="material-symbols-outlined text-[20px] text-primary"
                    style="font-variation-settings: 'FILL' 0, 'wght' 400;"
                    aria-hidden="true"
                >{{ $icon }}</span>
            @endif

            <div class="flex flex-col gap-0.5">
                @if ($title)
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">
                        {{ $title }}
                    </h2>
                @endif

                @if ($helper)
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        {{ $helper }}
                    </p>
                @endif
            </div>
        </header>
    @endif

    {{ $slot }}
</section>
