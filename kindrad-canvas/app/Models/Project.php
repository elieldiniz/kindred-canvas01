<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'category_id',
        'style_id',
        'layout_id',
        'mode_id',
        'status_id',
        'title',
        'inputs',
        'subject_type',
        'custom_prompt',
        'pose_id',
        'first_generated_at',
        'is_published',
        'is_in_explore',
        'remixed_from_project_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'first_generated_at' => 'datetime',
            'is_published' => 'boolean',
            'is_in_explore' => 'boolean',
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => ! empty($value) ? $value : __('Artwork #:id', ['id' => $this->id])
        );
    }

    public const SUBJECT_TYPES = [
        'pessoa',
        'casal',
        'familia',
        'pet',
        'outra',
    ];

    public const PHOTO_COUNTS = [
        'pessoa' => 1,
        'pet' => 1,
        'outra' => 1,
        'casal' => 2,
        'familia' => 2,
    ];

    public function needsPose(): bool
    {
        return in_array($this->subject_type, ['casal', 'familia'], true);
    }

    public function expectedPhotoCount(): int
    {
        return self::PHOTO_COUNTS[$this->subject_type] ?? 0;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Style, $this>
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    /**
     * @return BelongsTo<Layout, $this>
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    /**
     * @return BelongsTo<ProjectMode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(ProjectMode::class);
    }

    /**
     * @return BelongsTo<ProjectStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class);
    }

    /**
     * @return BelongsTo<Pose, $this>
     */
    public function pose(): BelongsTo
    {
        return $this->belongsTo(Pose::class);
    }

    /**
     * @return HasMany<ProjectPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class)->ordered();
    }

    /**
     * @return HasMany<Generation, $this>
     */
    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    /**
     * @return HasOne<Generation, $this>
     */
    public function latestGeneration(): HasOne
    {
        return $this->hasOne(Generation::class)->latestOfMany('id');
    }

    public function isModeLocked(): bool
    {
        return $this->first_generated_at !== null;
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function remixedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'remixed_from_project_id');
    }

    /**
     * @return HasMany<GalleryFavorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(GalleryFavorite::class);
    }

    /**
     * URL of the latest completed generation's image, or null when there is no
     * previewable artifact yet (S3 storage; signed URL when needed).
     */
    public function previewImageUrl(): ?string
    {
        $gen = $this->latestGeneration;

        if ($gen === null || $gen->result_path === null) {
            return null;
        }

        try {
            $disk = config('generation.disk');

            return Storage::disk($disk)->url($gen->result_path);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether the author was a free-tier user at the time this project is being
     * considered for the public gallery. Used by the Explore query.
     */
    public function isFromFreeTier(): bool
    {
        if (! $this->relationLoaded('user')) {
            $this->load('user');
        }

        $user = $this->user;

        if ($user === null) {
            return false;
        }

        return $user->isFreeTier();
    }
}
