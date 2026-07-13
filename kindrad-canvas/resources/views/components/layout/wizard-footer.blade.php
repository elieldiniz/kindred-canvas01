@props([
    'showBack' => true,
])

<footer class="sticky bottom-0 z-20 border-t border-outline-variant bg-surface-container/30 backdrop-blur-sm">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-gutter py-stack-md">
        <div class="flex w-1/3 items-center justify-start">
            @isset($back)
                {{ $back }}
            @endisset
        </div>

        <div class="flex w-1/3 items-center justify-center text-center">
            @isset($current)
                {{ $current }}
            @endisset
        </div>

        <div class="flex w-1/3 items-center justify-end">
            @isset($continue)
                {{ $continue }}
            @endisset
        </div>
    </div>
</footer>
