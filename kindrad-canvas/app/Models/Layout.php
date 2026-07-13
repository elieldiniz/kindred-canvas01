<?php

namespace App\Models;

use Database\Factories\LayoutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Layout extends Model
{
    /** @use HasFactory<LayoutFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'preview_path',
        'safe_area_overlay',
        'proportion_ratio',
        'status_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'safe_area_overlay' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LayoutStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(LayoutStatus::class);
    }

    /**
     * @return BelongsToMany<Style, $this>
     */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'style_layouts');
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
