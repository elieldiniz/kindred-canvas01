<?php

namespace App\Models;

use Database\Factories\PoseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $thumbnail_path
 * @property int $status_id
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Pose extends Model
{
    /** @use HasFactory<PoseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'thumbnail_path',
        'status_id',
        'sort_order',
        'rich_description',
    ];

    /**
     * @return BelongsTo<PoseStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(PoseStatus::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q): Builder => $q->where('slug', 'active'));
    }
}
