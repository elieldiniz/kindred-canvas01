<?php

use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component {
    /**
     * @return array{month: Collection<int, SubscriptionPlan>, year: Collection<int, SubscriptionPlan>}
     */
    public function plans(): array
    {
        $all = SubscriptionPlan::query()
            ->active()
            ->ordered()
            ->with('interval')
            ->get();

        return [
            'month' => $all->filter(fn ($p) => $p->interval?->slug === 'month')->take(3)->values(),
            'year' => $all->filter(fn ($p) => $p->interval?->slug === 'year')->take(3)->values(),
        ];
    }

    public function render(): View
    {
        return view('components.welcome.plans', [
            'plans' => $this->plans(),
        ]);
    }
};
?>

@php
    /** @var array{month: \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionPlan>, year: \Illuminate\Database\Eloquent\Collection<int, \App\Models\SubscriptionPlan>} $plans */
@endphp
<section id="welcome-plans" x-data="{ interval: 'month' }" class="border-t border-outline-variant" data-test="welcome-plans-section">
    <style>[x-cloak]{display:none!important}</style>
    <div class="mx-auto max-w-7xl px-stack-lg py-section">

        <div class="text-center mb-12">
            <span class="inline-flex items-center gap-2 text-primary text-xs font-bold tracking-[0.2em] mb-4 uppercase">
                <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">auto_awesome</span>
                {{ __('Simple. Flexible. Yours.') }}
            </span>
            <h2 class="text-4xl md:text-5xl font-serif text-on-surface">
                {{ __('Choose your plan') }}
            </h2>
            <p class="mt-4 text-on-surface-variant text-lg">{{ __('Monthly or annual — credits refresh every period, cancel anytime.') }}</p>
        </div>

        <div class="flex justify-center mb-12">
            <div class="inline-flex items-center rounded-full border border-white/10 bg-white/5 p-1" data-test="welcome-plans-toggle">
                <button
                    type="button"
                    x-on:click="interval = 'month'"
                    x-bind:class="interval === 'month' ? 'bg-primary text-white shadow-[0_0_10px_rgba(99,54,255,0.4)]' : 'text-white/60 hover:text-white'"
                    class="px-6 py-2 rounded-full text-sm font-bold transition-all duration-300"
                    data-test="welcome-plans-toggle-month"
                >
                    {{ __('Monthly') }}
                </button>
                <button
                    type="button"
                    x-on:click="interval = 'year'"
                    x-bind:class="interval === 'year' ? 'bg-primary text-white shadow-[0_0_10px_rgba(99,54,255,0.4)]' : 'text-white/60 hover:text-white'"
                    class="px-6 py-2 rounded-full text-sm font-bold transition-all duration-300"
                    data-test="welcome-plans-toggle-year"
                >
                    {{ __('Yearly') }}
                </button>
            </div>
        </div>

        {{-- Monthly grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-test="welcome-plans-grid-month" x-show="interval === 'month'" x-cloak>
            @forelse ($plans['month'] as $loop_plan)
                @php $isPopular = $loop->first; @endphp
                <article
                    class="relative flex flex-col rounded-2xl border p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl
                        {{ $isPopular
                            ? 'border-primary/50 bg-gradient-to-b from-primary/10 to-background/50 shadow-[0_0_40px_rgba(99,54,255,0.15)] backdrop-blur-md'
                            : 'border-white/8 bg-surface-container/40 backdrop-blur-md hover:border-white/20' }}"
                    data-test="welcome-plan-card-{{ $loop_plan->slug }}"
                >
                    @if ($isPopular)
                        <div class="absolute -top-3 left-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-primary/20 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-primary shadow-[0_0_10px_rgba(99,54,255,0.3)]">
                                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">star</span>
                                {{ __('Popular') }}
                            </span>
                        </div>
                    @endif

                    <header class="mb-5">
                        <h3 class="text-lg font-bold text-white capitalize">{{ $loop_plan->name }}</h3>
                        <p class="mt-1 text-sm text-white/50 leading-relaxed">{{ $loop_plan->description }}</p>
                    </header>

                    <div class="mb-5 flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-white">{{ $loop_plan->formattedPrice() }}</span>
                        <span class="mb-1 text-sm text-white/40">{{ $loop_plan->interval->slug === 'year' ? '/ '.__('year') : '/ '.__('month') }}</span>
                    </div>

                    <div class="mb-6 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                            <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">bolt</span>
                        </span>
                        <p class="text-sm text-white/60" data-test="welcome-plan-credits-{{ $loop_plan->slug }}">
                            <span class="font-bold text-white">{{ $loop_plan->credits_per_period }}</span>
                            {{ __('credits per') }} {{ $loop_plan->interval->slug === 'year' ? __('year') : __('month') }}
                        </p>
                    </div>

                    <div class="mt-auto">
                        @guest
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                class="block w-full rounded-xl px-6 py-3.5 text-center text-sm font-bold transition-all duration-300 whitespace-nowrap
                                    {{ $isPopular
                                        ? 'gradient-generate text-white shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02]'
                                        : 'border border-white/10 bg-white/5 text-white hover:bg-white/10 hover:border-white/20' }}"
                                data-test="welcome-plan-cta-{{ $loop_plan->slug }}"
                            >
                                {{ __('Sign up and subscribe') }}
                            </a>
                        @else
                            <a
                                href="{{ route('billing.index') }}"
                                wire:navigate
                                class="block w-full rounded-xl px-6 py-3.5 text-center text-sm font-bold transition-all duration-300 whitespace-nowrap
                                    {{ $isPopular
                                        ? 'gradient-generate text-white shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02]'
                                        : 'border border-white/10 bg-white/5 text-white hover:bg-white/10 hover:border-white/20' }}"
                                data-test="welcome-plan-cta-{{ $loop_plan->slug }}"
                            >
                                {{ __('Subscribe to :name', ['name' => $loop_plan->name]) }}
                            </a>
                        @endguest
                    </div>
                </article>
            @empty
                <div class="col-span-3 flex flex-col items-center gap-4 rounded-2xl border border-white/5 bg-surface-container/40 p-16 text-center">
                    <span class="material-symbols-outlined text-[48px] text-white/20">inventory_2</span>
                    <p class="text-white/50" data-test="welcome-plans-empty-month">{{ __('No monthly plans available right now.') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Yearly grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-test="welcome-plans-grid-year" x-show="interval === 'year'" x-cloak>
            @forelse ($plans['year'] as $loop_plan)
                @php $isPopular = $loop->first; @endphp
                <article
                    class="relative flex flex-col rounded-2xl border p-7 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl
                        {{ $isPopular
                            ? 'border-primary/50 bg-gradient-to-b from-primary/10 to-background/50 shadow-[0_0_40px_rgba(99,54,255,0.15)] backdrop-blur-md'
                            : 'border-white/8 bg-surface-container/40 backdrop-blur-md hover:border-white/20' }}"
                    data-test="welcome-plan-card-{{ $loop_plan->slug }}"
                >
                    @if ($isPopular)
                        <div class="absolute -top-3 left-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-primary/20 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-primary shadow-[0_0_10px_rgba(99,54,255,0.3)]">
                                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">star</span>
                                {{ __('Popular') }}
                            </span>
                        </div>
                    @endif

                    <header class="mb-5">
                        <h3 class="text-lg font-bold text-white capitalize">{{ $loop_plan->name }}</h3>
                        <p class="mt-1 text-sm text-white/50 leading-relaxed">{{ $loop_plan->description }}</p>
                    </header>

                    <div class="mb-5 flex items-end gap-1">
                        <span class="text-4xl font-extrabold text-white">{{ $loop_plan->formattedPrice() }}</span>
                        <span class="mb-1 text-sm text-white/40">{{ $loop_plan->interval->slug === 'year' ? '/ '.__('year') : '/ '.__('month') }}</span>
                    </div>

                    <div class="mb-6 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                            <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">bolt</span>
                        </span>
                        <p class="text-sm text-white/60" data-test="welcome-plan-credits-{{ $loop_plan->slug }}">
                            <span class="font-bold text-white">{{ $loop_plan->credits_per_period }}</span>
                            {{ __('credits per') }} {{ $loop_plan->interval->slug === 'year' ? __('year') : __('month') }}
                        </p>
                    </div>

                    <div class="mt-auto">
                        @guest
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                class="block w-full rounded-xl px-6 py-3.5 text-center text-sm font-bold transition-all duration-300 whitespace-nowrap
                                    {{ $isPopular
                                        ? 'gradient-generate text-white shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02]'
                                        : 'border border-white/10 bg-white/5 text-white hover:bg-white/10 hover:border-white/20' }}"
                                data-test="welcome-plan-cta-{{ $loop_plan->slug }}"
                            >
                                {{ __('Sign up and subscribe') }}
                            </a>
                        @else
                            <a
                                href="{{ route('billing.index') }}"
                                wire:navigate
                                class="block w-full rounded-xl px-6 py-3.5 text-center text-sm font-bold transition-all duration-300 whitespace-nowrap
                                    {{ $isPopular
                                        ? 'gradient-generate text-white shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02]'
                                        : 'border border-white/10 bg-white/5 text-white hover:bg-white/10 hover:border-white/20' }}"
                                data-test="welcome-plan-cta-{{ $loop_plan->slug }}"
                            >
                                {{ __('Subscribe to :name', ['name' => $loop_plan->name]) }}
                            </a>
                        @endguest
                    </div>
                </article>
            @empty
                <div class="col-span-3 flex flex-col items-center gap-4 rounded-2xl border border-white/5 bg-surface-container/40 p-16 text-center">
                    <span class="material-symbols-outlined text-[48px] text-white/20">inventory_2</span>
                    <p class="text-white/50" data-test="welcome-plans-empty-year">{{ __('No annual plans available right now.') }}</p>
                </div>
            @endforelse
        </div>

    </div>
</section>
