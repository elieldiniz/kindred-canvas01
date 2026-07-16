@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Kindred Canvas" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('logo1.png') }}" alt="Kindred Canvas" class="h-8 w-auto">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Kindred Canvas" {{ $attributes }}>
        <x-slot name="logo">
            <img src="{{ asset('logo1.png') }}" alt="Kindred Canvas" class="h-8 w-auto">
        </x-slot>
    </flux:brand>
@endif
