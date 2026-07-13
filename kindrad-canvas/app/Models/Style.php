<?php

namespace App\Models;

use Database\Factories\StyleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Style extends Model
{
    /** @use HasFactory<StyleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'prompt_fragment',
        'thumbnail_path',
        'status_id',
    ];

    /**
     * @return BelongsTo<StyleStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(StyleStatus::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_styles');
    }

    /**
     * @return BelongsToMany<Layout, $this>
     */
    public function layouts(): BelongsToMany
    {
        return $this->belongsToMany(Layout::class, 'style_layouts');
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
