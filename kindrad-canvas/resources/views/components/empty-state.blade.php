@props([
    'icon' => 'inbox',
    'title' => '',
    'description' => '',
])

<div class="glass-card mx-auto flex w-full max-w-md flex-col items-center gap-stack-md rounded-2xl p-stack-lg text-center" data-test="empty-state">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0, 'wght' 400;">{{ $icon }}</span>
    </div>

    <h3 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h3>

    <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">{{ $description }}</p>

    @isset($action)
        <div class="mt-2">
            {{ $action }}
        </div>
    @endisset
</div>
