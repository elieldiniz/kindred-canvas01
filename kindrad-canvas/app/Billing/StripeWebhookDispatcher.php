<?php

namespace App\Billing;

use App\Actions\Billing\MarkSubscriptionPastDueAction;
use App\Actions\Billing\SubscriptionCreditGrantAction;
use App\Actions\Billing\SyncSubscriptionAction;
use Illuminate\Support\Facades\Log;

class StripeWebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload  Decoded Stripe event.
     */
    public function dispatch(array $payload): void
    {
        $type = $payload['type'] ?? 'unknown';
        $object = $payload['data']['object'] ?? [];

        match (true) {
            $type === 'invoice.payment_succeeded' => app(SubscriptionCreditGrantAction::class)
                ->handle($object),
            $type === 'invoice.payment_failed' => app(MarkSubscriptionPastDueAction::class)
                ->handle((string) ($object['subscription'] ?? ''), $object, 'invoice.payment_failed'),
            $type === 'invoice.payment_action_required' => app(MarkSubscriptionPastDueAction::class)
                ->handle((string) ($object['subscription'] ?? ''), $object, 'invoice.payment_action_required'),
            in_array($type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'], true) => app(SyncSubscriptionAction::class)->handle($object, $type),
            default => Log::info('stripe_webhook_unknown_type', ['type' => $type]),
        };
    }
}
