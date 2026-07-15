<?php

namespace App\Models;

use Database\Factories\SubscriptionStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SubscriptionStatus extends Model
{
    /** @use HasFactory<SubscriptionStatusFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    public static function idFor(string $slug): ?int
    {
        return static::query()->where('slug', $slug)->value('id');
    }
}
