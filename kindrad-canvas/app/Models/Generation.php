<?php

namespace App\Models;

use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property int $status_id
 * @property int|null $provider_id
 * @property string $prompt_snapshot
 * @property array<string, mixed> $constraints_snapshot
 * @property string $idempotency_key
 * @property string|null $result_path
 * @property string|null $result_mime_type
 * @property int|null $result_width_px
 * @property int|null $result_height_px
 * @property string|null $failure_reason
 * @property int $credits_charged
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'user_id',
        'status_id',
        'provider_id',
        'prompt_snapshot',
        'constraints_snapshot',
        'idempotency_key',
        'result_path',
        'result_mime_type',
        'result_width_px',
        'result_height_px',
        'failure_reason',
        'credits_charged',
        'started_at',
        'completed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'credits_charged' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'constraints_snapshot' => 'array',
            'result_width_px' => 'integer',
            'result_height_px' => 'integer',
            'credits_charged' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<GenerationStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(GenerationStatus::class);
    }

    /**
     * @return BelongsTo<GenerationProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(GenerationProvider::class);
    }

    public function markProcessing(): void
    {
        $processing = GenerationStatus::where('slug', 'processing')->firstOrFail();

        $this->forceFill([
            'status_id' => $processing->id,
            'started_at' => now(),
        ])->save();
    }

    public function markCompleted(string $resultPath, string $mime, int $width, int $height): void
    {
        $completed = GenerationStatus::where('slug', 'completed')->firstOrFail();

        $this->forceFill([
            'status_id' => $completed->id,
            'result_path' => $resultPath,
            'result_mime_type' => $mime,
            'result_width_px' => $width,
            'result_height_px' => $height,
            'completed_at' => now(),
        ])->save();
    }

    public function markFailed(string $reason): void
    {
        $failed = GenerationStatus::where('slug', 'failed')->firstOrFail();

        $this->forceFill([
            'status_id' => $failed->id,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ])->save();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('slug', 'completed'));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('slug', 'failed'));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $statusQuery) => $statusQuery->where('slug', 'processing'));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('id');
    }
}
