<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\StripeEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $processed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class StripeEvent extends Model
{
    /** @use HasFactory<StripeEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
