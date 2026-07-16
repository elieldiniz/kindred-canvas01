<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentFailedMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $eventType = 'invoice.payment_failed',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Atualize seu método de pagamento'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payment-failed',
            with: [
                'subscription' => $this->subscription,
                'eventType' => $this->eventType,
                'graceDays' => (int) config('billing.grace_days', 7),
                'billingUrl' => route('billing.index'),
            ],
        );
    }
}
