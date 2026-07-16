@component('mail::message')
# {{ __('Atualize seu método de pagamento') }}

{{ __('Olá! A última tentativa de cobrança da sua assinatura falhou.') }}

{{ __('Para evitar a interrupção dos seus créditos mensais, atualize seu método de pagamento.') }}

@component('mail::panel')
**{{ __('Plano') }}:** {{ $subscription->subscriptionPlan?->name ?? '—' }}
**{{ __('Status') }}:** {{ $subscription->status?->name ?? $subscription->stripe_status }}
@if($graceDays > 0)
{{ __('Você tem :days dias de carência antes que o uso seja bloqueado.', ['days' => $graceDays]) }}
@endif
@endcomponent

@component('mail::button', ['url' => $billingUrl, 'color' => 'primary'])
{{ __('Atualizar método de pagamento') }}
@endcomponent

{{ __('Se você já atualizou, pode ignorar este e-mail.') }}

{{ __('— Equipe Kindred Canvas') }}
@endcomponent
