<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-on-surface antialiased bg-[radial-gradient(ellipse_at_80%_20%,_var(--tw-gradient-stops))] from-primary/15 via-background to-background">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-white/5 !bg-[#060f1d] text-on-surface">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="pt-2">
                <p class="px-3 text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 mb-1 mt-2">
                    {{ __('Platform') }}
                </p>

                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                    data-test="sidebar-dashboard-link"
                >
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="receipt-percent"
                    :href="route('credits.index')"
                    :current="request()->routeIs('credits.index')"
                    wire:navigate
                    data-test="sidebar-credits-link"
                >
                    {{ __('Credits') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="sparkles"
                    :href="route('billing.plans.index')"
                    :current="request()->routeIs('billing.plans.*')"
                    wire:navigate
                    data-test="sidebar-billing-plans-link"
                >
                    {{ __('Subscription plans') }}
                </flux:sidebar.item>

                <flux:sidebar.item
                    icon="credit-card"
                    :href="route('billing.index')"
                    :current="request()->routeIs('billing.index')"
                    wire:navigate
                    data-test="sidebar-billing-link"
                >
                    {{ __('My subscription') }}
                </flux:sidebar.item>

                @if (auth()->user()?->is_admin)
                    <flux:sidebar.item
                        icon="shield-check"
                        :href="route('admin.dashboard')"
                        :current="request()->routeIs('admin.*')"
                        wire:navigate
                        data-test="sidebar-admin-link"
                    >
                        {{ __('Admin') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>