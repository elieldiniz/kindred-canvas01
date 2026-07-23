<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'description',
        'thumbnail_path',
        'status_id',
        'sort_order',
        'scene_prompt',
        'emotion_hint',
        'lighting_hint',
        'color_palette',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<CategoryStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CategoryStatus::class);
    }

    /**
     * @return BelongsToMany<Style, $this>
     */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'category_styles');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $q): Builder => $q->where('slug', 'active'));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForProduct(Builder $query, string $slug): Builder
    {
        return $query->whereHas('product', fn (Builder $q): Builder => $q->where('slug', $slug));
    }
}
