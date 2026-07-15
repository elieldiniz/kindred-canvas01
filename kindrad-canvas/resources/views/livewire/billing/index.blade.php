<div>
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