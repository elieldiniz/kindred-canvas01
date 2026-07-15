<?php

namespace App\Livewire\Admin\Categories;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Product;
use App\Models\Style;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Edit extends Component
{
    use StoresThumbnail;
    use WithFileUploads;

    public Category $categoryModel;

    public ?int $product_id = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public int $sort_order = 0;

    public ?int $status_id = null;

    /** @var array<int> */
    public array $selectedStyles = [];

    /** @var TemporaryUploadedFile|null */
    public $thumbnail = null;

    public bool $removeThumbnail = false;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/categories';
    }

    public function mount(int|Category $category): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $category instanceof Category ? $category : Category::findOrFail($category);
        $this->categoryModel = $model;
        $this->product_id = $model->product_id;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->description = $model->description;
        $this->sort_order = $model->sort_order;
        $this->status_id = $model->status_id;
        $this->selectedStyles = $model->styles()->pluck('styles.id')->toArray();
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

        $path = $this->categoryModel->thumbnail_path;

        return $path !== null ? Storage::disk($this->disk())->url($path) : null;
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'slug')
                    ->where('product_id', $this->product_id)
                    ->ignore($this->categoryModel->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer'],
            'status_id' => ['required', 'exists:category_statuses,id'],
            'selectedStyles' => ['array'],
            'selectedStyles.*' => ['exists:styles,id'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $tracked = ['product_id', 'name', 'slug', 'description', 'sort_order', 'status_id'];
        $stylesBefore = $this->categoryModel->styles()->pluck('styles.id')->all();
        $before = $this->categoryModel->only($tracked);

        $this->categoryModel->update($this->only($tracked));
        $this->categoryModel->styles()->sync($this->selectedStyles);

        $thumbnailChanged = false;
        $oldThumbnail = $this->categoryModel->thumbnail_path;

        if ($this->thumbnail !== null) {
            $newPath = $this->storeThumbnail($this->thumbnail);
            $this->categoryModel->thumbnail_path = $newPath;
            $this->categoryModel->save();
            $thumbnailChanged = true;

            if ($oldThumbnail !== null) {
                $this->deleteThumbnail($oldThumbnail);
            }
        } elseif ($this->removeThumbnail && $oldThumbnail !== null) {
            $this->categoryModel->thumbnail_path = null;
            $this->categoryModel->save();
            $thumbnailChanged = true;

            $this->deleteThumbnail($oldThumbnail);
        }

        $after = $this->categoryModel->fresh()->only($tracked);
        $changedAttrs = array_keys(array_diff_assoc($after, $before));
        $stylesChanged = $this->selectedStyles !== $stylesBefore;

        if ($changedAttrs !== [] || $stylesChanged || $thumbnailChanged) {
            $payload = ['event' => 'updated'];
            if ($changedAttrs !== []) {
                $payload['changed'] = $changedAttrs;
                $payload['before'] = array_intersect_key($before, array_flip($changedAttrs));
                $payload['after'] = array_intersect_key($after, array_flip($changedAttrs));
            }
            if ($stylesChanged) {
                $payload['styles_before'] = $stylesBefore;
                $payload['styles_after'] = $this->selectedStyles;
            }
            if ($thumbnailChanged) {
                $payload['thumbnail_before'] = $oldThumbnail;
                $payload['thumbnail_after'] = $this->categoryModel->fresh()->thumbnail_path;
            }

            $audit->record(
                actor: auth()->user(),
                actionSlug: 'edit_category',
                target: $this->categoryModel,
                payload: $payload,
            );
        }

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.categories.edit', [
            'products' => Product::orderBy('name')->get(),
            'statuses' => CategoryStatus::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Edit Category'),
        ]);
    }
}
