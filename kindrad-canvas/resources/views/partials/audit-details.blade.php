@php
    /** @var array<string, mixed>|null $payload */
    $payload ??= [];
    $slug = $slug ?? '';

    /**
     * Render a cents value as a localized currency string.
     * Looks up the audit payload's `currency` sibling if present,
     * falling back to BRL for the seeded plans.
     */
    $formatCents = function (int|string $cents, ?string $currency = null): string {
        $code = strtoupper((string) ($currency ?? $payload['currency'] ?? 'BRL'));
        $value = number_format(((int) $cents) / 100, 2, ',', '.');

        return match ($code) {
            'BRL' => "R$ {$value}",
            'USD' => "US$ {$value}",
            'EUR' => "€ {$value}",
            default => "{$code} {$value}",
        };
    };
@endphp

@if (empty($payload))
    <span class="text-xs text-white/30">—</span>
@elseif ($slug === 'password_reset_by_admin')
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[16px] text-amber-400" style="font-variation-settings: 'FILL' 1, 'wght' 400;">key</span>
        <span class="text-xs text-white/70">{{ __('Password rotated') }}</span>
        @if (! empty($payload['reset_at']))
            <span class="text-xs text-white/40">· {{ \Illuminate\Support\Str::after($payload['reset_at'], 'T') }}</span>
        @endif
    </div>
@elseif ($slug === 'toggle_admin')
    @php
        $becameAdmin = (bool) ($payload['after'] ?? false);
    @endphp
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest {{ $becameAdmin ? 'border-primary/40 bg-primary/20 text-primary' : '' }}">
            @if ($becameAdmin)
                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">arrow_upward</span>
                {{ __('Promoted') }}
            @else
                <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">arrow_downward</span>
                {{ __('Demoted') }}
            @endif
        </span>
        <span class="text-xs text-white/40">
            {{ ($payload['before'] ?? false) ? 'admin' : 'user' }}
            →
            <span class="text-white/80">{{ ($payload['after'] ?? false) ? 'admin' : 'user' }}</span>
        </span>
    </div>
@elseif (in_array($slug, ['suspend_user', 'unsuspend_user'], true))
    @php
        $isUnsuspend = $slug === 'unsuspend_user';
    @endphp
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest {{ $isUnsuspend ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-400' : 'border-amber-500/40 bg-amber-500/15 text-amber-400' }}">
            <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">{{ $isUnsuspend ? 'check_circle' : 'block' }}</span>
            {{ $isUnsuspend ? __('Reactivated') : __('Suspended') }}
        </span>
    </div>
@elseif ($slug === 'grant_credits')
    <div class="flex flex-col gap-1">
        <span class="font-bold text-emerald-400">+{{ (int) ($payload['amount'] ?? 0) }} {{ __('credits') }}</span>
        @if (! empty($payload['notes']))
            <span class="text-xs text-white/50 italic break-words">{{ $payload['notes'] }}</span>
        @endif
        @if (isset($payload['balance_after']))
            <span class="text-xs text-white/40">{{ __('Balance after') }}: <span class="text-white/80">{{ (int) $payload['balance_after'] }}</span></span>
        @endif
    </div>
@elseif (\Illuminate\Support\Str::contains($slug, 'subscription_plan') && isset($payload['event']))
    <div class="flex flex-col gap-1">
        <span class="inline-flex w-fit items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest
            {{ ($payload['event'] ?? '') === 'created' ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-400' : 'border-cyan-500/40 bg-cyan-500/15 text-cyan-400' }}">
            {{ ucfirst($payload['event']) }}
        </span>
        @if (! empty($payload['attributes']) && is_array($payload['attributes']))
            <dl class="mt-1 grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 text-xs">
                @foreach ($payload['attributes'] as $attrKey => $attrValue)
                    @if (! in_array($attrKey, ['stripe_product_id', 'stripe_price_id'], true))
                        <dt class="font-semibold text-white/50">{{ \Illuminate\Support\Str::headline($attrKey) }}</dt>
                        <dd class="break-words text-white/80">
                            @if (is_bool($attrValue))
                                <flux:badge size="sm" :variant="$attrValue ? 'success' : 'danger'">
                                    {{ $attrValue ? __('Yes') : __('No') }}
                                </flux:badge>
                            @elseif ($attrValue === null)
                                <span class="text-white/30">—</span>
                            @elseif ($attrKey === 'price_cents' && is_numeric($attrValue))
                                <span class="font-mono text-emerald-400">{{ $formatCents((int) $attrValue, $payload['attributes']['currency'] ?? null) }}</span>
                            @elseif (is_string($attrValue) && \Illuminate\Support\Str::length($attrValue) > 80)
                                <span class="break-all text-white/70">{{ $attrValue }}</span>
                            @else
                                {{ $attrValue }}
                            @endif
                        </dd>
                    @endif
                @endforeach
            </dl>
        @endif
    </div>
@else
    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-xs">
        @foreach ($payload as $key => $value)
            <dt class="font-semibold text-white/50 whitespace-nowrap">{{ \Illuminate\Support\Str::headline($key) }}</dt>
            <dd class="break-words text-white/80">
                @if (is_bool($value))
                    <flux:badge size="sm" :variant="$value ? 'success' : 'danger'">
                        {{ $value ? __('Yes') : __('No') }}
                    </flux:badge>
                @elseif ($value === null)
                    <span class="text-white/30">—</span>
                @elseif (\Illuminate\Support\Str::endsWith($key, '_cents') && is_numeric($value))
                    <span class="font-mono text-emerald-400">{{ $formatCents((int) $value) }}</span>
                @elseif (is_array($value))
                    <span class="font-mono text-white/70">{{ \Illuminate\Support\Str::limit(json_encode($value, JSON_UNESCAPED_UNICODE), 120, '…') }}</span>
                @elseif (is_string($value) && \Illuminate\Support\Str::length($value) > 60)
                    <span class="break-all">{{ $value }}</span>
                @else
                    {{ $value }}
                @endif
            </dd>
        @endforeach
    </dl>
@endif
