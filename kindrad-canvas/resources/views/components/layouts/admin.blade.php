<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-on-surface">
        <div class="flex min-h-screen">
            {{-- Admin sidebar --}}
            <aside class="sticky top-0 hidden h-screen w-64 shrink-0 overflow-y-auto border-r border-outline-variant bg-surface-container lg:block custom-scrollbar" data-test="admin-sidebar">
                <div class="flex items-center gap-3 p-stack-md">
                    <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                    <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-primary">
                        {{ __('Admin') }}
                    </span>
                </div>

                <nav class="flex flex-col gap-stack-sm px-stack-md pb-stack-md">
                    <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
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

                    <p class="mt-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                        {{ __('Catalog') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item icon="cube" disabled>
                            {{ __('Products') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="tag" disabled>
                            {{ __('Categories') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="swatch" disabled>
                            {{ __('Styles') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="squares-2x2" disabled>
                            {{ __('Layouts') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="code-bracket-square" disabled>
                            {{ __('Prompt templates') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <p class="mt-stack-md font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                        {{ __('People') }}
                    </p>
                    <flux:navlist.group>
                        <flux:navlist.item icon="user-group" disabled>
                            {{ __('Users') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="document-text" disabled>
                            {{ __('Audit log') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                </nav>

                <div class="mt-auto border-t border-outline-variant p-stack-md">
                    <flux:navlist.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                        {{ __('Back to app') }}
                    </flux:navlist.item>
                </div>
            </aside>

            {{-- Main canvas --}}
            <main class="flex-1 overflow-x-hidden">
                {{-- Topbar --}}
                <header class="sticky top-0 z-10 flex items-center justify-between border-b border-outline-variant bg-surface-container-low/80 px-stack-lg py-stack-md backdrop-blur-md" data-test="admin-topbar">
                    <div>
                        <h1 class="font-headline-md text-headline-md text-on-surface">
                            {{ $header ?? __('Admin') }}
                        </h1>
                    </div>

                    <div class="flex items-center gap-stack-md">
                        <span class="font-mono-sm text-mono-sm text-on-surface-variant">
                            {{ auth()->user()?->email }}
                        </span>
                        <span class="inline-flex items-center gap-stack-sm rounded-full bg-primary/20 px-stack-md py-1 font-mono-sm text-mono-sm uppercase tracking-widest text-primary" data-test="admin-badge">
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">shield</span>
                            {{ __('Admin') }}
                        </span>
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