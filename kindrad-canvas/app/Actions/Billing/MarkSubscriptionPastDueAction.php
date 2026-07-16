<?php

namespace App\Actions\Billing;

use App\Mail\PaymentFailedMail;
use App\Models\PaymentFailure;
use App\Models\Subscription;
use App\Models\SubscriptionStatus;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MarkSubscriptionPastDueAction
{
    /**
     * Mark the local subscription past_due, persist a PaymentFailure audit row,
     * deliver a database notification, and (when mailer is not 'log') queue
     * the dunning email. Idempotent at the row level only: a single invocation
     * always creates one notification + one PaymentFailure + at most one mail.
     *
     * @param  array<string, mixed>  $invoice  Stripe invoice payload (decoded).
     */
    public function handle(string $stripeSubscriptionId, array $invoice = [], string $eventType = 'invoice.payment_failed'): ?Subscription
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if ($subscription === null) {
            Log::info('mark_past_due_skipped_unknown_subscription', ['id' => $stripeSubscriptionId]);

            return null;
        }

        $pastDueId = SubscriptionStatus::where('slug', 'past_due')->value('id');

        if ($pastDueId !== null) {
            $subscription->status_id = $pastDueId;
        }

        $subscription->stripe_status = 'past_due';
        $subscription->save();

        $reason = $this->extractReason($invoice);
        $chargeId = $this->extractChargeId($invoice);
        $invoiceId = isset($invoice['id']) && is_string($invoice['id']) ? $invoice['id'] : null;

        PaymentFailure::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'event_type' => $eventType,
            'stripe_invoice_id' => $invoiceId,
            'stripe_charge_id' => $chargeId,
            'attempted_at' => Carbon::createFromTimestamp($invoice['created'] ?? time()),
            'reason' => $reason,
            'payload' => $invoice,
        ]);

        $user = $subscription->user;

        if ($user !== null) {
            $user->notify(new PaymentFailedNotification($subscription));

            if (config('mail.default') !== 'log') {
                Mail::to($user)->queue(new PaymentFailedMail($subscription, $eventType));
            }
        }

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function extractReason(array $invoice): string
    {
        $code = $invoice['last_payment_error']['code'] ?? null;
        $message = $invoice['last_payment_error']['message'] ?? null;

        if (is_string($code) && $code !== '') {
            return $code;
        }

        if (is_string($message) && $message !== '') {
            return substr($message, 0, 250);
        }

        return 'unknown';
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function extractChargeId(array $invoice): ?string
    {
        $charge = $invoice['charge'] ?? null;

        if (is_string($charge)) {
            return $charge;
        }

        if (is_array($charge) && isset($charge['id']) && is_string($charge['id'])) {
            return $charge['id'];
        }

        return null;
    }
}
