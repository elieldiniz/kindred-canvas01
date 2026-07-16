<?php

namespace App\Models;

use Database\Factories\ShowcaseItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $title
 * @property string $image_path
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ShowcaseItem extends Model
{
    /** @use HasFactory<ShowcaseItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'image_path',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Resolve the public URL of the uploaded image through the configured
     * generation disk (S3 in prod, local in dev).
     */
    public function imageUrl(): string
    {
        return Storage::disk(config('generation.disk'))->url($this->image_path);
    }
}
