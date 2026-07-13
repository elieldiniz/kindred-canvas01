@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background text-on-surface antialiased">
        <div class="flex min-h-screen flex-col">
            <x-layout.wizard-topbar />

            <main class="flex-1 overflow-y-auto">
                <div class="mx-auto max-w-5xl px-gutter py-stack-lg">
                    {{ $slot }}
                </div>
            </main>

            <x-layout.wizard-footer>
                <x-slot:back>
                    {{ $back ?? '' }}
                </x-slot:back>
                <x-slot:current>
                    {{ $current ?? '' }}
                </x-slot:current>
                <x-slot:continue>
                    {{ $continue ?? '' }}
                </x-slot:continue>
            </x-layout.wizard-footer>

            {{ $modals ?? '' }}
        </div>

        @fluxScripts
    </body>
</html>
