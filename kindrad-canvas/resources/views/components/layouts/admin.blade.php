<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-on-surface antialiased bg-[radial-gradient(ellipse_at_80%_20%,_var(--tw-gradient-stops))] from-primary/15 via-background to-background">
        <div class="flex min-h-screen">
            {{-- Admin sidebar --}}
            <aside class="sticky top-0 hidden h-screen w-64 shrink-0 overflow-y-auto border-r border-white/5 bg-background/50 backdrop-blur-md lg:block custom-scrollbar" data-test="admin-sidebar">
                <div class="flex items-center p-6 border-b border-white/5">
                    <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                    <span class="px-2 py-0.5 rounded-md bg-primary/20 border border-primary/30 text-[10px] font-bold uppercase tracking-widest text-primary shadow-[0_0_8px_rgba(99,54,255,0.2)] ml-auto">
                        {{ __('Admin') }}
                    </span>
                </div>

                <nav class="flex flex-col px-6 pb-6 pt-4">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 mb-2 mt-4 ml-2">
                        {{ __('Overview') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item
                            icon="chart-pie"
                            :href="route('admin.dashboard')"
                            :current="request()->routeIs('admin.dashboard')"
                            wire:navigate
                            data-test="admin-nav-dashboard"
                        >
                            {{ __('Dashboard') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 mb-2 mt-8 ml-2">
                        {{ __('Catalog') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item
                            icon="cube"
                            :href="route('admin.products.index')"
                            :current="request()->routeIs('admin.products.*')"
                            wire:navigate
                            data-test="admin-nav-products"
                        >
                            {{ __('Products') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="tag"
                            :href="route('admin.categories.index')"
                            :current="request()->routeIs('admin.categories.*')"
                            wire:navigate
                            data-test="admin-nav-categories"
                        >
                            {{ __('Categories') }}
                        </flux:navlist.item>
<flux:navlist.item
                            icon="swatch"
                            :href="route('admin.styles.index')"
                            :current="request()->routeIs('admin.styles.*')"
                            wire:navigate
                            data-test="admin-nav-styles"
                        >
                            {{ __('Styles') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="code-bracket-square"
                            :href="route('admin.prompt-templates.index')"
                            :current="request()->routeIs('admin.prompt-templates.*')"
                            wire:navigate
                            data-test="admin-nav-prompt-templates"
                        >
                            {{ __('Prompt templates') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="squares-2x2"
                            :href="route('admin.layouts.index')"
                            :current="request()->routeIs('admin.layouts.*')"
                            wire:navigate
                            data-test="admin-nav-layouts"
                        >
                            {{ __('Layouts') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 mb-2 mt-8 ml-2">
                        {{ __('Billing') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item
                            icon="sparkles"
                            :href="route('admin.plans.index')"
                            :current="request()->routeIs('admin.plans.*')"
                            wire:navigate
                            data-test="admin-nav-plans"
                        >
                            {{ __('Plans') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="credit-card"
                            :href="route('admin.subscriptions.index')"
                            :current="request()->routeIs('admin.subscriptions.*')"
                            wire:navigate
                            data-test="admin-nav-subscriptions"
                        >
                            {{ __('Subscriptions') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 mb-2 mt-8 ml-2">
                        {{ __('People') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item
                            icon="user-group"
                            :href="route('admin.users.index')"
                            :current="request()->routeIs('admin.users.*')"
                            wire:navigate
                            data-test="admin-nav-users"
                        >
                            {{ __('Users') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="document-text"
                            :href="route('admin.audit-log.index')"
                            :current="request()->routeIs('admin.audit-log.*')"
                            wire:navigate
                            data-test="admin-nav-audit-log"
                        >
                            {{ __('Audit log') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="photo"
                            :href="route('admin.gallery.index')"
                            :current="request()->routeIs('admin.gallery.*')"
                            wire:navigate
                            data-test="admin-nav-gallery"
                        >
                            {{ __('Gallery') }}
                        </flux:navlist.item>
                        <flux:navlist.item
                            icon="squares-2x2"
                            :href="route('admin.showcase.index')"
                            :current="request()->routeIs('admin.showcase.*')"
                            wire:navigate
                            data-test="admin-nav-showcase"
                        >
                            {{ __('Showcase') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                </nav>

                <div class="mt-auto border-t border-white/5 p-6 bg-white/[0.01]">
                    <flux:navlist.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                        {{ __('Back to app') }}
                    </flux:navlist.item>
                </div>
            </aside>

            {{-- Main canvas --}}
            <main class="flex-1 h-screen overflow-y-auto relative">
                {{-- Topbar --}}
                <header class="sticky top-0 z-50 flex items-center justify-between border-b border-white/5 bg-background/70 px-8 py-5 backdrop-blur-md" data-test="admin-topbar">
                    <div>
                        <h1 class="font-bold text-2xl text-white">
                            {{ $header ?? __('Admin') }}
                        </h1>
                    </div>

                    <div class="flex items-center gap-6">
                        <span class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-gradient-to-r from-primary/20 to-transparent px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary shadow-[0_0_10px_rgba(99,54,255,0.2)]" data-test="admin-badge">
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">shield</span>
                            {{ __('Admin') }}
                        </span>
                        <div class="h-6 w-px bg-white/10"></div>
                        <x-desktop-user-menu />
                    </div>
                </header>

                <div class="p-margin-page">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>