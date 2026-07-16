<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SubscriptionFactory as AppSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $subscription_plan_id
 * @property int|null $pending_plan_id
 * @property int|null $status_id
 * @property string $type
 * @property string $stripe_id
 * @property string $stripe_status
 * @property string|null $stripe_price
 * @property int|null $quantity
 * @property CarbonInterface|null $trial_ends_at
 * @property CarbonInterface|null $ends_at
 * @property CarbonInterface|null $current_period_start
 * @property CarbonInterface|null $current_period_end
 * @property bool $cancel_at_period_end
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read SubscriptionPlan|null $subscriptionPlan
 * @property-read SubscriptionPlan|null $scheduledPlan
 * @property-read SubscriptionStatus|null $status
 */
class Subscription extends CashierSubscription
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ]);
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->subscriptionPlan();
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function scheduledPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'pending_plan_id');
    }

    /**
     * @return BelongsTo<SubscriptionStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(SubscriptionStatus::class, 'status_id');
    }

    /**
     * @return HasMany<PaymentFailure, $this>
     */
    public function paymentFailures(): HasMany
    {
        return $this->hasMany(PaymentFailure::class);
    }

    public function statusSlug(): ?string
    {
        return $this->status?->slug;
    }

    /**
     * @return Factory<Subscription>
     */
    protected static function newFactory(): Factory
    {
        return AppSubscriptionFactory::new();
    }

    public function isOpen(): bool
    {
        $slug = $this->statusSlug();

        if ($slug === null) {
            return in_array($this->stripe_status, ['active', 'trialing', 'past_due'], true);
        }

        return in_array($slug, ['active', 'trialing', 'past_due'], true);
    }

    public function isPastDue(): bool
    {
        if ($this->status?->slug === 'past_due') {
            return true;
        }

        return $this->stripe_status === 'past_due';
    }

    public function isPastDueAndExpired(int $graceDays): bool
    {
        if (! $this->isPastDue()) {
            return false;
        }

        $anchor = $this->ends_at ?? $this->current_period_end;

        if ($anchor === null) {
            return false;
        }

        return $anchor->copy()->addDays($graceDays)->isPast();
    }
}
