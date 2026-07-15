<?php

namespace App\Livewire\Admin\Styles;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Category;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Edit extends Component
{
    use StoresThumbnail;
    use WithFileUploads;

    public Style $styleModel;

    public string $name = '';

    public string $slug = '';

    public ?string $prompt_fragment = null;

    public ?int $status_id = null;

    /** @var array<int> */
    public array $selectedCategories = [];

    /** @var TemporaryUploadedFile|null */
    public $thumbnail = null;

    public bool $removeThumbnail = false;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/styles';
    }

    public function mount(int|Style $style): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $style instanceof Style ? $style : Style::findOrFail($style);
        $this->styleModel = $model;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->prompt_fragment = $model->prompt_fragment;
        $this->status_id = $model->status_id;
        $this->selectedCategories = $model->categories()->pluck('categories.id')->toArray();
    }

    public function removeExistingThumbnail(): void
    {
        $this->removeThumbnail = true;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->removeThumbnail || $this->thumbnail !== null) {
            return null;
        }

        $path = $this->styleModel->thumbnail_path;

        return $path !== null ? Storage::disk($this->disk())->url($path) : null;
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:styles,slug,'.$this->styleModel->id],
            'prompt_fragment' => ['nullable', 'string'],
            'status_id' => ['required', 'exists:style_statuses,id'],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['exists:categories,id'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $tracked = ['name', 'slug', 'prompt_fragment', 'status_id'];
        $categoriesBefore = $this->styleModel->categories()->pluck('categories.id')->all();
        $before = $this->styleModel->only($tracked);

        $this->styleModel->update($this->only($tracked));
        $this->styleModel->categories()->sync($this->selectedCategories);

        $thumbnailChanged = false;
        $oldThumbnail = $this->styleModel->thumbnail_path;

        if ($this->thumbnail !== null) {
            $newPath = $this->storeThumbnail($this->thumbnail);
            $this->styleModel->thumbnail_path = $newPath;
            $this->styleModel->save();
            $thumbnailChanged = true;

            if ($oldThumbnail !== null) {
                $this->deleteThumbnail($oldThumbnail);
            }
        } elseif ($this->removeThumbnail && $oldThumbnail !== null) {
            $this->styleModel->thumbnail_path = null;
            $this->styleModel->save();
            $thumbnailChanged = true;

            $this->deleteThumbnail($oldThumbnail);
        }

        $after = $this->styleModel->fresh()->only($tracked);
        $changedAttrs = array_keys(array_diff_assoc($after, $before));
        $categoriesChanged = $this->selectedCategories !== $categoriesBefore;

        if ($changedAttrs !== [] || $categoriesChanged || $thumbnailChanged) {
            $payload = ['event' => 'updated'];
            if ($changedAttrs !== []) {
                $payload['changed'] = $changedAttrs;
                $payload['before'] = array_intersect_key($before, array_flip($changedAttrs));
                $payload['after'] = array_intersect_key($after, array_flip($changedAttrs));
            }
            if ($categoriesChanged) {
                $payload['categories_before'] = $categoriesBefore;
                $payload['categories_after'] = $this->selectedCategories;
            }
            if ($thumbnailChanged) {
                $payload['thumbnail_before'] = $oldThumbnail;
                $payload['thumbnail_after'] = $this->styleModel->fresh()->thumbnail_path;
            }

            $audit->record(
                actor: auth()->user(),
                actionSlug: 'edit_style',
                target: $this->styleModel,
                payload: $payload,
            );
        }

        $this->redirect(route('admin.styles.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.styles.edit', [
            'statuses' => StyleStatus::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Edit Style'),
        ]);
    }
}
