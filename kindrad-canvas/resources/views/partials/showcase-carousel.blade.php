@php
    use App\Models\ShowcaseItem;

    $items = ShowcaseItem::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    $itemCount = $items->count();
@endphp

@if ($itemCount > 0)
<section
    class="relative overflow-hidden border-t border-outline-variant"
    data-test="welcome-showcase-carousel"
>


    <div class="absolute inset-0 bg-gradient-to-b from-primary/10 via-background to-background pointer-events-none"></div>

    <div class="relative mx-auto max-w-7xl px-8 py-24" data-test="welcome-showcase-section">

        {{-- Header --}}
        <header class="mb-16 text-center" data-test="welcome-showcase-header">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 text-xs font-bold tracking-widest uppercase text-primary">
                <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">collections</span>
                {{ __('Showcase') }}
            </span>
            <h2 class="mt-4 text-4xl md:text-5xl font-extrabold leading-tight">
                <span class="bg-gradient-to-r from-white via-white to-white/60 bg-clip-text text-transparent">{{ __('Create keepsakes.') }}</span>
                <span class="bg-gradient-to-r from-primary via-purple-400 to-cyan-400 bg-clip-text text-transparent">{{ __('Crafted with care.') }}</span>
            </h2>
            <p class="mt-4 max-w-xl mx-auto text-base text-on-surface-variant">
                {{ __('A glimpse at what our customers make every day.') }}
            </p>
        </header>

        {{-- 3D Stage --}}
        <div
            class="kc-stage select-none"
            x-data="kcCarousel({{ $itemCount }})"
            x-init="init()"
        >
            <div class="kc-track" data-test="welcome-showcase-frame">
                @foreach ($items as $index => $item)
                    <div
                        class="kc-card"
                        data-index="{{ $index }}"
                        :data-pos="getPos({{ $index }})"
                        @click="navigate({{ $index }})"
                        data-test="welcome-showcase-card"
                    >
                        <div class="kc-glow"></div>
                        <div class="kc-card-inner">
                            <img
                                src="{{ $item->imageUrl() }}"
                                alt="{{ $item->title ?: 'Showcase' }}"
                                loading="lazy"
                                draggable="false"
                            />
                            <div class="kc-shimmer"></div>
                        </div>
                        @if (!empty($item->title))
                            <div class="kc-label">{{ $item->title }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Controls --}}
            <div class="mt-16 flex flex-col items-center gap-6">
                <div class="flex items-center gap-4">
                    <button class="kc-nav-btn" @click="prev()" aria-label="Previous">
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </button>

                    <div class="kc-dots flex items-center gap-2">
                        @foreach ($items as $index => $item)
                            <button
                                :class="{ 'active': active === {{ $index }} }"
                                @click="goTo({{ $index }})"
                                aria-label="Go to slide {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>

                    <button class="kc-nav-btn" @click="next()" aria-label="Next">
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </button>
                </div>

                <p class="text-xs text-on-surface-variant/50 tracking-widest uppercase">
                    <span x-text="active + 1"></span> / {{ $itemCount }}
                </p>
            </div>
        </div>
    </div>


</section>
@endif
