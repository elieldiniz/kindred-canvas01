<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <title>{{ __('Welcome') }} — {{ config('app.name', 'Kindred Canvas') }}</title>
    </head>
    <body class="min-h-screen bg-background text-on-surface antialiased bg-[radial-gradient(ellipse_at_80%_20%,_var(--tw-gradient-stops))] from-primary/15 via-background to-background">

        {{-- Top nav --}}
        <header class="sticky top-0 z-50 w-full py-6 px-8 bg-background/70 backdrop-blur-md border-b border-white/5">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-stack-sm">
                    <x-app-logo :sidebar="false" />
                </a>

                <nav class="hidden md:flex items-center gap-8">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="text-sm font-medium text-white/70 hover:text-white px-4 py-2 rounded-xl border border-white/5 hover:border-white/20 hover:bg-white/5 transition-all duration-300">
                            {{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('projects.new') }}" wire:navigate class="text-sm font-medium text-white/70 hover:text-white px-4 py-2 rounded-xl border border-white/5 hover:border-white/20 hover:bg-white/5 transition-all duration-300">
                            {{ __('My Artworks') }}
                        </a>
                    @else
                        <a href="#how-it-works" class="text-sm font-medium text-white/70 hover:text-white px-4 py-2 rounded-xl border border-white/5 hover:border-white/20 hover:bg-white/5 transition-all duration-300">
                            {{ __('Pricing') }}
                        </a>
                        <a href="#how-it-works" class="text-sm font-medium text-white/70 hover:text-white px-4 py-2 rounded-xl border border-white/5 hover:border-white/20 hover:bg-white/5 transition-all duration-300">
                            {{ __('Help') }}
                        </a>
                    @endauth
                </nav>

                <div class="flex items-center gap-4">
                    @auth
                        <a
                            href="{{ route('projects.new') }}"
                            wire:navigate
                            class="gradient-generate inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white whitespace-nowrap shadow-[0_0_15px_rgba(99,54,255,0.3)] hover:shadow-[0_0_25px_rgba(99,54,255,0.5)] hover:-translate-y-0.5 transition-all duration-300"
                            data-test="welcome-create-cta"
                        >
                            {{ __('New artwork') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-white/80 hover:text-white whitespace-nowrap px-5 py-2.5 border border-white/10 bg-white/5 hover:bg-white/10 rounded-xl transition-all duration-300">
                            {{ __('Sign in') }}
                        </a>
                        <a
                            href="{{ route('register') }}"
                            wire:navigate
                            class="gradient-generate inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white whitespace-nowrap shadow-[0_0_15px_rgba(99,54,255,0.3)] hover:shadow-[0_0_25px_rgba(99,54,255,0.5)] hover:-translate-y-0.5 transition-all duration-300"
                            data-test="welcome-signup-cta"
                        >
                            {{ __('Get started') }}
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Hero — side-by-side layout --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute right-1/4 top-1/2 h-[500px] w-[500px] -translate-y-1/2 rounded-full bg-primary/15 blur-[120px]"></div>
            </div>

            <div class="mx-auto max-w-7xl px-stack-lg pt-12 pb-24 lg:grid lg:grid-cols-2 lg:gap-12 lg:items-center">
                {{-- Hero Left: Content --}}
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 text-xs font-bold tracking-widest uppercase text-primary mb-8">
                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">auto_awesome</span>
                        {{ __('AI-POWERED KEEPSAKES') }}
                    </span>

                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-serif leading-[1.1] mb-6 text-on-surface" data-test="welcome-headline">
                        {{ __('Turn moments into') }}
                        <br />
                        <span class="gradient-generate bg-clip-text text-transparent italic">
                            {{ __('meaningful') }}
                        </span>
                        {{ __('art.') }}
                    </h1>

                    <p class="text-on-surface-variant text-lg mb-10 max-w-lg leading-relaxed">
                        {{ __('Pick a product, choose a style, upload a photo — Kindred Canvas generates a one-of-a-kind design you can print on a mug, frame, or share.') }}
                    </p>

                    <div class="flex flex-col gap-4">
                        @auth
                            <a
                                href="{{ route('projects.new') }}"
                                wire:navigate
                                class="gradient-generate inline-flex items-center gap-3 w-fit px-8 py-4 rounded-2xl font-bold text-lg text-on-primary shadow-[0_0_20px_rgba(99,54,255,0.3)] hover:shadow-[0_0_30px_rgba(99,54,255,0.6)] hover:-translate-y-1 transition-all duration-300"
                                data-test="welcome-create-cta"
                            >
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">auto_awesome</span>
                                {{ __('Create your first artwork') }}
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                wire:navigate
                                class="gradient-generate inline-flex items-center gap-3 w-fit px-8 py-4 rounded-2xl font-bold text-lg text-on-primary shadow-[0_0_20px_rgba(99,54,255,0.3)] hover:shadow-[0_0_30px_rgba(99,54,255,0.6)] hover:-translate-y-1 transition-all duration-300"
                                data-test="welcome-signup-cta"
                            >
                                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">auto_awesome</span>
                                {{ __('Create your first artwork') }}
                            </a>
                        @endauth

                        <div class="flex items-center gap-4 text-xs text-on-surface-variant">
                            <span class="inline-flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-yellow-500" style="font-variation-settings: 'FILL' 1, 'wght' 400;">star</span>
                                {{ __('5 free credits on signup') }}
                            </span>
                            <span class="w-1 h-1 bg-on-surface-variant/40 rounded-full"></span>
                            <span>{{ __('No credit card required') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Hero Right: Showcase --}}
                <div class="relative mt-16 lg:mt-0">
                    <div class="absolute -inset-10 bg-primary/15 blur-[100px] rounded-full z-0"></div>
                    <div class="relative z-10 glass-card p-6 rounded-[2.5rem] bg-white/5 border border-white/10">
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Mug --}}
                            <div class="bg-surface-container/60 p-4 rounded-3xl border border-white/5">
                                <div class="aspect-square rounded-2xl overflow-hidden mb-4 flex items-center justify-center bg-gray-900/50">
                                    <img alt="Sunset Mug Art" class="object-cover w-full h-full scale-110" src="https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?q=80&w=800&auto=format&fit=crop"/>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-6 h-6 bg-primary rounded-md flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[14px] text-on-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">palette</span>
                                    </div>
                                    <span class="font-bold text-sm text-on-surface">Mug</span>
                                </div>
                                <p class="text-xs text-on-surface-variant">Personalized print</p>
                            </div>

                            {{-- Frame --}}
                            <div class="bg-surface-container/60 p-4 rounded-3xl border border-white/5">
                                <div class="aspect-square rounded-2xl overflow-hidden mb-4 flex items-center justify-center bg-gray-900/50">
                                    <img alt="Frame Art" class="object-cover w-full h-full scale-125" src="https://images.unsplash.com/photo-1513519245088-0e12902e5a38?q=80&w=800&auto=format&fit=crop"/>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-6 h-6 bg-primary rounded-md flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[14px] text-on-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">palette</span>
                                    </div>
                                    <span class="font-bold text-sm text-on-surface">Free Art</span>
                                </div>
                                <p class="text-xs text-on-surface-variant">Share &amp; download</p>
                            </div>
                        </div>

                        {{-- 5 Credits Banner --}}
                        <div class="mt-4 bg-primary/20 border border-primary/30 p-4 rounded-2xl flex items-center gap-4">
                            <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px] text-on-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">bolt</span>
                            </div>
                            <div>
                                <div class="font-bold text-sm text-on-surface">5 credits free</div>
                                <div class="text-xs text-on-surface-variant">Enough for 5 artworks on signup</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="border-t border-outline-variant">
            <div class="mx-auto max-w-7xl px-stack-lg py-section">
                <div class="text-center mb-16">
                    <span class="inline-flex items-center gap-2 text-primary text-xs font-bold tracking-[0.2em] mb-4 uppercase">
                        <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">auto_awesome</span>
                        {{ __('Simple. Fast. Personal.') }}
                    </span>
                    <h2 class="text-4xl md:text-5xl font-serif text-on-surface">
                        {{ __('How it works') }}
                    </h2>
                    <p class="mt-4 text-on-surface-variant text-lg">{{ __('Three steps from idea to print-ready artwork.') }}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    {{-- Step 01 --}}
                    <div class="glass-card p-10 rounded-[2.5rem] relative overflow-hidden group" data-test="welcome-step-1">
                        <div class="w-10 h-10 bg-primary/40 rounded-full flex items-center justify-center text-sm font-bold absolute top-6 left-6 text-on-surface">01</div>
                        <div class="mt-12 flex flex-col items-start">
                            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:border-primary/50 transition">
                                <span class="material-symbols-outlined text-[32px] text-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">palette</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-on-surface">{{ __('Pick your style') }}</h3>
                            <p class="text-on-surface-variant leading-relaxed">{{ __('Choose from watercolor, cartoon, oil paint, and more — all with curated prompt fragments tuned for print.') }}</p>
                        </div>
                    </div>

                    {{-- Step 02 --}}
                    <div class="glass-card p-10 rounded-[2.5rem] relative overflow-hidden group" data-test="welcome-step-2">
                        <div class="w-10 h-10 bg-primary/40 rounded-full flex items-center justify-center text-sm font-bold absolute top-6 left-6 text-on-surface">02</div>
                        <div class="mt-12 flex flex-col items-start">
                            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:border-primary/50 transition">
                                <span class="material-symbols-outlined text-[32px] text-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">upload</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-on-surface">{{ __('Upload a photo') }}</h3>
                            <p class="text-on-surface-variant leading-relaxed">{{ __('Drop in a portrait, pet, family photo, or couple shot. The AI uses it as inspiration for the composition.') }}</p>
                        </div>
                    </div>

                    {{-- Step 03 --}}
                    <div class="glass-card p-10 rounded-[2.5rem] relative overflow-hidden group" data-test="welcome-step-3">
                        <div class="w-10 h-10 bg-primary/40 rounded-full flex items-center justify-center text-sm font-bold absolute top-6 left-6 text-on-surface">03</div>
                        <div class="mt-12 flex flex-col items-start">
                            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:border-primary/50 transition">
                                <span class="material-symbols-outlined text-[32px] text-primary" style="font-variation-settings: 'FILL' 1, 'wght' 400;">download</span>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-on-surface">{{ __('Download & share') }}</h3>
                            <p class="text-on-surface-variant leading-relaxed">{{ __('Get print-ready files at 300 DPI for mugs, or share the digital version on social.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-outline-variant">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-stack-md px-stack-lg py-12">
                <p class="text-sm text-on-surface-variant">
                    © {{ date('Y') }} {{ config('app.name', 'Kindred Canvas') }}. {{ __('All rights reserved.') }}
                </p>
                <div class="flex items-center gap-8 text-sm text-on-surface-variant">
                    <a href="#" class="hover:text-on-surface transition">{{ __('Privacy Policy') }}</a>
                    <a href="#" class="hover:text-on-surface transition">{{ __('Terms of Service') }}</a>
                    <a href="#" class="hover:text-on-surface transition">{{ __('Contact') }}</a>
                </div>
            </div>
        </footer>
    </body>
</html>
