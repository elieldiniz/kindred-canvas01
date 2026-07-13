<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status_id',
        'print_width_mm',
        'print_height_mm',
        'min_dpi',
        'safe_area_mm',
        'color_mode_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'print_width_mm' => 'decimal:2',
            'print_height_mm' => 'decimal:2',
            'safe_area_mm' => 'decimal:2',
            'min_dpi' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class);
    }

    /**
     * @return BelongsTo<ColorMode, $this>
     */
    public function colorMode(): BelongsTo
    {
        return $this->belongsTo(ColorMode::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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
