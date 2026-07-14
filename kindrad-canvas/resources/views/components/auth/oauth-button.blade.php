@props([
    'provider' => 'google',
])

<a
    href="{{ route('auth.oauth.redirect', ['provider' => $provider]) }}"
    class="group inline-flex w-full items-center justify-center gap-3 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 font-label-md text-label-md text-on-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-primary hover:bg-primary-container hover:text-on-primary active:translate-y-0 active:scale-[0.99]"
    data-test="oauth-continue-{{ $provider }}"
>
    <svg
        class="size-5"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path
            d="M21.35 11.1H12v2.84h5.35c-.23 1.36-1.66 4-5.35 4-3.22 0-5.85-2.67-5.85-5.95S8.78 6.04 12 6.04c1.83 0 3.06.78 3.76 1.45l2.56-2.47C16.71 3.78 14.55 2.95 12 2.95 6.83 2.95 2.65 7.13 2.65 12.3S6.83 21.65 12 21.65c6.92 0 9.45-4.85 9.45-9.34 0-.63-.07-1.1-.1-1.21z"
            fill="#4285F4"
        />
    </svg>
    <span>{{ __('Continue with :provider', ['provider' => ucfirst($provider)]) }}</span>
</a>