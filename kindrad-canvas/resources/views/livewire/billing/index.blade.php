<div>
    @if ($subscription && $subscription->isPastDue())
        @php
            $graceDays = (int) config('billing.grace_days', 7);
            $anchor = $subscription->ends_at ?? $subscription->current_period_end;
            $graceExpiresAt = $anchor?->copy()->addDays($graceDays);
            $isExpired = $subscription->isPastDueAndExpired($graceDays);
        @endphp
        <flux:callout
            class="mb-6"
            variant="{{ $isExpired ? 'danger' : 'warning' }}"
            icon="exclamation-triangle"
            data-test="billing-dunning-banner"
        >
            <flux:callout.heading>
                {{ $isExpired
                    ? __('Sua assinatura está com pagamento atrasado e o uso foi suspenso.')
                    : __('Detectamos uma falha no pagamento da sua assinatura.') }}
            </flux:callout.heading>
            <flux:callout.text>
                @if ($graceExpiresAt && ! $isExpired)
                    {{ __('Carência até :date para atualizar o método de pagamento.', ['date' => $graceExpiresAt->format('d/m/Y')]) }}
                @elseif ($isExpired)
                    {{ __('O período de carência expirou. Novas gerações estão bloqueadas.') }}
                @elseif ($anchor)
                    {{ __('Próxima tentativa: :date', ['date' => $anchor->format('d/m/Y')]) }}
                @endif
            </flux:callout.text>
            <div class="mt-3">
                <button
                    type="button"
                    wire:click="openPortal"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-[0_0_15px_rgba(99,54,255,0.3)] hover:shadow-[0_0_25px_rgba(99,54,255,0.5)] transition-all"
                    data-test="billing-dunning-banner-cta"
                >
                    {{ __('Atualizar método de pagamento') }}
                </button>
            </div>
        </flux:callout>
    @endif


    @if ($showSuccessBanner)
        <flux:callout variant="success" icon="check-circle" class="mb-6" data-test="billing-success-banner">
            <flux:callout.heading>{{ __('Assinatura confirmada!') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Seus créditos do novo plano já estão na sua conta.') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($subscription === null)
        <div class="space-y-4" data-test="billing-no-subscription">
            <h1 class="text-2xl font-semibold">{{ __('Assinatura') }}</h1>
            <p class="text-zinc-400">{{ __('Você ainda não tem uma assinatura.') }}</p>
            <a href="{{ route('billing.plans.index') }}" class="inline-block rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                {{ __('Ver planos') }}
            </a>
        </div>
    @else
        <div class="space-y-6" data-test="billing-current-subscription">
            <header class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">{{ __('Minha assinatura') }}</h1>
                @if (in_array($subscription->statusSlug(), ['active', 'trialing', 'past_due'], true))
                    <flux:button wire:click="openPortal" variant="primary" data-test="billing-open-portal">
                        {{ __('Gerenciar assinatura') }}
                    </flux:button>
                @endif
            </header>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-white/5 bg-background/50 p-4">
                    <dt class="text-xs uppercase tracking-widest text-zinc-400">{{ __('Plano') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $subscription->subscriptionPlan?->name ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-white/5 bg-background/50 p-4">
                    <dt class="text-xs uppercase tracking-widest text-zinc-400">{{ __('Status') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">
                        {{ $subscription->status?->name ?? ucfirst($subscription->stripe_status) }}
                    </dd>
                </div>
                <div class="rounded-lg border border-white/5 bg-background/50 p-4">
                    <dt class="text-xs uppercase tracking-widest text-zinc-400">{{ __('Créditos') }}</dt>
                    <dd class="mt-1 text-lg font-semibold" data-test="billing-current-balance">{{ $user->credit_balance }}</dd>
                </div>
                <div class="rounded-lg border border-white/5 bg-background/50 p-4">
                    <dt class="text-xs uppercase tracking-widest text-zinc-400">{{ __('Próxima cobrança') }}</dt>
                    <dd class="mt-1 text-lg font-semibold">
                        {{ optional($subscription->current_period_end)->format('d/m/Y') ?? '—' }}
                    </dd>
                </div>
            </dl>

            @if ($subscription->cancel_at_period_end)
                <flux:callout variant="warning" icon="exclamation-triangle" data-test="billing-cancel-banner">
                    <flux:callout.heading>{{ __('Sua assinatura termina em :date', ['date' => optional($subscription->ends_at)->format('d/m/Y') ?? '—']) }}</flux:callout.heading>
                </flux:callout>
            @endif
        </div>
    @endif
</div>