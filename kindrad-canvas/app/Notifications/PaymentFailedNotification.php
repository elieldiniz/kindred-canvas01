<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        $graceDays = (int) config('billing.grace_days', 7);
        $anchor = $this->subscription->ends_at ?? $this->subscription->current_period_end;

        $graceExpiresAt = $anchor?->copy()->addDays($graceDays);

        return [
            'subscription_id' => $this->subscription->id,
            'stripe_id' => $this->subscription->stripe_id,
            'stripe_status' => $this->subscription->stripe_status,
            'status_slug' => $this->subscription->status?->slug,
            'grace_expires_at' => $graceExpiresAt?->toIso8601String(),
            'grace_days' => $graceDays,
            'message' => __('Your subscription payment failed. Update your payment method to keep access.'),
            'cta' => [
                'label' => __('Update payment method'),
                'url' => route('billing.index'),
            ],
        ];
    }
}
