<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $credits_per_period
 * @property int $price_cents
 * @property string $currency
 * @property int $interval_id
 * @property bool $is_active
 * @property int $sort_order
 * @property string|null $stripe_product_id
 * @property string|null $stripe_price_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionInterval $interval
 * @property-read Collection<int, Subscription> $subscriptions
 */
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'credits_per_period',
        'price_cents',
        'currency',
        'interval_id',
        'is_active',
        'sort_order',
        'stripe_product_id',
        'stripe_price_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits_per_period' => 'integer',
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<SubscriptionPlan>  $query
     * @return Builder<SubscriptionPlan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<SubscriptionPlan>  $query
     * @return Builder<SubscriptionPlan>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<SubscriptionInterval, $this>
     */
    public function interval(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInterval::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function formattedPrice(): string
    {
        $value = number_format($this->price_cents / 100, 2, ',', '.');

        return match ($this->currency) {
            'BRL' => "R$ {$value}",
            'USD' => "US$ {$value}",
            'EUR' => "€ {$value}",
            default => "{$this->currency} {$value}",
        };
    }
}
