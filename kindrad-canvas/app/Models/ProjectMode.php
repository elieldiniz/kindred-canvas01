<?php

namespace App\Models;

use Database\Factories\ProjectModeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMode extends Model
{
    /** @use HasFactory<ProjectModeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'injects_print_specs',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'injects_print_specs' => 'boolean',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @param  array<int, string>  $slugs
     * @return Builder<$this>
     */
    public function scopeWithSlugs(Builder $query, array $slugs): Builder
    {
        return $query->whereIn('slug', $slugs);
    }
}
